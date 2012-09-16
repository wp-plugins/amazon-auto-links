<?php
// make sure that SimplePie has been already loaded
if (defined('ABSPATH') && defined('WPINC')) {
	require_once (ABSPATH . WPINC . '/class-simplepie.php');
}

class AmazonAutoLinks_Core_
{
	/* Used Constants 
		ABSPATH			// this means instanciation of this class must be after including WordPress admin.php 
	*/
	
	/* Properties */
	public $classver = 'standard';
	public $feed = '';
	protected $pluginname = 'Amazon Auto Links';
	protected $pluginkey = 'amazonautolinks';
    protected $pageslug = 'amazonautolinks';
    protected $textdomain = 'amazonautolinks';
	protected $oAALOptions = array();
	
	/* Constructor */
	function __construct($arrUnitOptions, $arrGeneralOptions) {
		$this->feed = new AmazonAutoLinks_SimplePie();		// this means class-simplepie.php must be included prior to instantiating this class
	
		// Setup Caches
		$this->feed->enable_cache(true);
		$this->feed->set_cache_class('WP_Feed_Cache');
		$this->feed->set_file_class('WP_SimplePie_File');

		$this->feed->enable_order_by_date(true);			// Making sure that it works with the defult setting. This does not affect the sortorder set by the option, $option['sortorder']
		$this->arrUnitOptions = $arrUnitOptions;
		$this->arrGeneralOptions = $arrGeneralOptions;
		
		// todo: use this option class instead of the parameters so that the method can be called by only specifying the unit label.
		// $this->oAALfuncs = new AmazonAutoLinks_Helper_Functions($this->pluginkey);
		$this->oAALOptions = new AmazonAutoLinks_Options($this->pluginkey);
	}
	/* Method Reset */
	function reset() {
		// when using the same class instance and re-fetch from other RSS sources, use this method; otherwise, the previous SimplePie instance is alive
		// that means the fetched items become together with the previously fetched items.
		$this->feed = new AmazonAutoLinks_SimplePie();
		// $this->feed->set_cache_location(ABSPATH . '/cache');
		$this->feed->enable_order_by_date(true);			// Making sure that it works with the defult setting. This does not affect the sortorder set by the option, $option['sortorder']
	}
	/* Method Fetch */
    function fetch($arrRssUrls) {

		// Verify parameters
		if (!(is_array($arrRssUrls) && is_array($this->arrUnitOptions))) {
			echo $this->pluginname . ": " . __('the plugin expects the option to be an array', 'amazonautolinks');
			return;
		}
		
		/* Used Options 
			$this->arrUnitOptions['adtypes']
			$this->arrUnitOptions['associateid']
			$this->arrUnitOptions['itemlimit']
			$this->arrUnitOptions['cacheexpiration']
			$this->arrUnitOptions['numitems']
			$this->arrUnitOptions['mblang']
			$this->arrUnitOptions['country']
			$this->arrUnitOptions['imagesize']
			$this->arrUnitOptions['nosim']
			$this->arrUnitOptions['itemformat']
			$this->arrUnitOptions['sortorder']
			$this->arrUnitOptions['containerformat']
		*/
		
		try {

			/* Setup urls */
			$urls = $this->set_urls($arrRssUrls);
			
			/* Setup SimplePie instance */
			$this->set_feed($urls);

			/* Prepare blacklis */
			$arrASINs = $this->blacklist();	// for checking duplicated items
			
			/* Fetch */
			$output = '';
			$this->i = 0;
			foreach ($this->feed->get_items(0, 0) as $item) {
									
				/* DOM Object for description */
				$dom = $this->load_dom($item->get_description(), $this->arrUnitOptions['mblang']);

				/* Div Node */
				$nodeDiv = $dom->getElementsByTagName('div')->item(0);		// the first depth div tag. If SimplePie is used outside of WordPress it should be the second depth which contains the description including images
				if (!$nodeDiv) 
					continue;		// sometimes this happens when unavailable feed is passed, such as Top Rated, which is not supported in some countries.
	
				/* Image */
				$strImgURL = $this->get_image($dom, $this->arrUnitOptions['imagesize']);
	
				/* Link (hyperlinked url) */  // + ref=nosim
				$lnk = $this->modify_url($item->get_permalink());

				/* ASIN - required for detecting duplicate items and for ref=nosim */
				$strASIN = $this->get_ASIN($lnk);
						
				/* Remove Duplicates with ASIN -- $arrASINs should be merged with black list array prior to it */
				if (in_array($strASIN, $arrASINs))
					continue;	// if the parsing item has been already processed, skip it.
				else 
					array_push($arrASINs, $strASIN);				
			
				/* Title */
				$title = $this->fix_title($item->get_title());
				if (!$title)
					continue;		//occasionally this happens that empty title is given. 	
				
				/* Description (creates $htmldescription and $textdescription) */ 
				$this->removeNodeByTagAndClass($nodeDiv, 'span', 'riRssTitle');
	
				// $textdescription -- although $htmldescription has the same routine, the below <a> tag modification needs text description for the title attribute
				$textdescription = $this->get_textdescription($nodeDiv);		// needs to be done before modifying links
				
				// Modify links in descriptions -- sets attribute and inserts ref=nosim
				$this->modify_links($nodeDiv, $title . ': ' . $textdescription);

				// $htmldescription  - this needs to be done again since it's modified
				$htmldescription = $this->get_htmldescription($nodeDiv);
							
				// format image -- if the image size is set to 0, $strImgURL is empty.
				$strImgTag = $strImgURL ? $this->format_image(array($lnk, $strImgURL, $title, $textdescription)) : "";
// echo htmlspecialchars($strImgTag);	
				// item format
				$output .= $this->format_item(array($lnk, $title, $htmldescription, $textdescription, $strImgTag));
// echo htmlspecialchars($output);								
				// Max Number of Items 
				if (++$this->i >= $this->arrUnitOptions['numitems']) break;
			} 	
		} catch (Exception $e) { $this->i = 0; }
		return $this->format_output($output);
    }
	function set_urls($arrRssUrls) {
		$urls = array();
		foreach ($arrRssUrls as $i => $strRssUrl) {			
			foreach ($this->arrUnitOptions['adtypes'] as $adtype) {
				if ($adtype['check']) {
					// http://www.amazon.co.jp/gp/rss/bestsellers/sports/ -> http://www.amazon.co.jp/gp/rss/bestsellers/sports/?tag=michaeluno-22
					array_push($urls, str_replace("/gp/rss/bestsellers/", "/gp/rss/" . $adtype['slug'] . "/", $strRssUrl . '?tag=' . $this->arrUnitOptions['associateid'] ));
				}
			}	
		}
		$numRssUrls = count($arrRssUrls);
		if ($numRssUrls == 0)
			throw new Exception("");	// get out of there
			
		// set the `itemlimit` option
		$this->arrUnitOptions['itemlimit'] = ceil($this->arrUnitOptions['numitems'] / $numRssUrls);
		return $urls;
	}	
	function blacklist() {
		$strBlacklist = trim($this->arrGeneralOptions['blacklist']);
		$arrBlacklist = explode(",", $strBlacklist);
		return $arrBlacklist;
	}
	function set_feed($urls) {
	
		// Set Sort Order
		$this->feed->set_sortorder($this->arrUnitOptions['sortorder']);
		
		// Set urls
		$this->feed->set_feed_url($urls);
		
		// Set the number of items to display per feed
		if (isset($this->arrUnitOptions['itemlimit'])) 
			$this->feed->set_item_limit($this->arrUnitOptions['itemlimit']);
			
		// Set Cache Duration
		// these methods are defined by WordPress. 
		// WordPress has an excellent caching system called tansient and let's use it
		// $this->feed->set_cache_class('WP_Feed_Cache');
		// $this->feed->set_file_class('WP_SimplePie_File');
		
		// this should be set after defineing $urls
		$this->feed->set_cache_duration(apply_filters('wp_feed_cache_transient_lifetime', $this->arrUnitOptions['cacheexpiration'], $urls));
	
		// Optimize it
		
		$this->feed->set_stupidly_fast(true);
		// $feed->force_feed(true);
		// $feed->remove_div(true);
		// $feed->force_fsockopen(true);
		// $feed->strip_htmltags(false);
		$this->feed->init();
			
		// Character Encodings etc.
		// $this->feed->handle_content_type();		// <-- this breaks XML validation when the feed items are fetched and displayed in a feed such as used in the the_content_feed filter.
			
	}	
	function load_dom($rawdescription, $lang) {
		$dom = new DOMDocument();		// $dom = new DOMDocument('1.0', 'utf-8');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		mb_language($lang); // <-- without this, the characters get broken
		$description = @mb_convert_encoding($rawdescription, 'HTML-ENTITIES', 'AUTO');	
		$description = '<div>' . $description . '</div>';		// this prevents later when using saveXML() from inserting the comment <!-- xml version .... -->
		@$dom->loadhtml( $description );
		return $dom;
	}	
	function get_image($dom, $numImageSize) {
		$strImgURL =""; // this line is necessary since some item don't have images so the domnode cannot be retrieved.	
		if ($numImageSize > 0) {			
			$nodeImg = $dom->getElementsByTagName('img')->item(0);
			if ($nodeImg) {
				$strImgURL = $nodeImg->attributes->getNamedItem("src")->value;
				$strImgURL = preg_replace('/_SL(\d+){3}_/i', '_SL'. $numImageSize . '_', $strImgURL);  // adjust the image size. _SL160_
			} 
		}
		// removes the div tag containing the image
		foreach ($dom->getElementsByTagName('div') as $nodeDivFloat) {
			if (stripos($nodeDivFloat->getAttribute('style'), 'float') !== false) {		// if the string 'float' is found 
				$nodeDivFloat->parentNode->removeChild($nodeDivFloat);
				break;
			}
		}
		return $strImgURL;
	}	
	function get_ASIN($lnk)	{
		preg_match('/dp\/(.\w+)\//i', $lnk, $matches);
		return IsSet($matches[1]) ? $matches[1] : "";
	}	
	function fix_title($title) {
		$title = strip_tags($title);
		// removes the heading numbering. e.g. #3: Product Name -> Product Name
		// Do not use "substr($title, strpos($title, ' '))" since some title contains double-quotes and they mess up html formats
		$title = trim(preg_replace('/#\d+?:\s?/i', '', $title));
		return $title;
	}
	function removeNodeByTagAndClass($node, $tagname, $className) {
		// remove the span tag containing the title
		$nodeSpanTitle = $node->getElementsByTagName($tagname)->item(0);
		if ($nodeSpanTitle) {		
			if (stripos($nodeSpanTitle->getAttribute('class'), $className) !== false) {		// if the string 'riRssTitle' is found 
				$nodeSpanTitle->parentNode->removeChild($nodeSpanTitle);
			}
		}	 
	}		
	function get_textdescription($node) {
		$arrDescription = preg_split('/<br.*?\/?>/i', $this->DOMInnerHTML($node));		// devide the string into arrays by <br> or <br />
		array_splice($arrDescription, -2);		// remove the last two elements	
		$htmldescription = implode("&nbsp;", $arrDescription);
		return html_entity_decode(trim(strip_tags($htmldescription)), ENT_QUOTES, 'UTF-8');		// not sure about the encoding
	}	
	function modify_links($node, $titleattribute) {
		foreach ($node->getElementsByTagName('a') as $nodeA) {
			$strHref = $nodeA->getAttribute('href');
			$strHref = $this->modify_url($strHref);
			if ($strHref) $nodeA->setAttribute('href', $strHref);
			$nodeA->setAttribute('title', $titleattribute);
		}
	}	
	function modify_url($strURL) {
		
		// ref=nosim
		if (!empty($this->arrUnitOptions['nosim'])) 
			$strURL = preg_replace('/ref\=(.+?)(\?|$)/i', 'ref=nosim$2', $strURL);		
		
		// tag
		$strTag = 'tag=' . $this->arrUnitOptions['associateid'];
		if (stripos($strURL, $strTag) === false) 	// if the associate id is not found, add it
			$strURL .= '?' . $strTag;
		
		return $this->alter_tag($strURL);
	}
	function insert_ref_nosim($strURL)  {
		return preg_replace('/ref\=(.+?)(\?|$)/i', 'ref=nosim$2', $strURL);
	}
	function alter_tag($strURL) {
		if (isset($this->arrGeneralOptions['supportrate']) && $this->does_occur_in($this->arrGeneralOptions['supportrate'])) {
			$strToken = $this->oAALOptions->get_token($this->arrUnitOptions['country']);
			$strURL = preg_replace('/(?<=tag=)(.+?-\d{2,})?/i', $strToken, $strURL);	// the pattern is replaced from '/tag\=\K(.+?-\d{2,})?/i' since \K is avaiable above PHP 5.2.4
		}
		return $strURL;
	}
	function does_occur_in($numPercentage) {
		if (mt_rand(1, 100) <= $numPercentage)
			return true;
		else
			return false;
	}		
	function get_htmldescription($node) {
		// Add markings to text node which later contet to a whitespace because by itself elements don't have white spaces between each other.
		foreach( $node->childNodes as $_node ) {
			if ($_node->nodeType == 3) {		// nodeType:3 TEXT_NODE
				$_node->nodeValue = '[identical_replacement_string]' . $_node->nodeValue . '[identical_replacement_string]';
			}
		}
		// AAL_DOMInnerHTML extracts intter html code, meaning the outer div tag won't be with it
		$strDescription = $this->DOMInnerHTML($node);
		$strDescription = str_replace('[identical_replacement_string]', '<br>', $strDescription);
		// omit the text 'visit blah blah blah for more information'
		if (preg_match('/<span.+class=["\']price["\'].+span>/i', $strDescription)) {
			// $arrDescription = preg_split('/<span.+class=["\']price["\'].+span>\K/i', $strDescription);  // this works above PHP v5.2.4
			$arrDescription = preg_split('/(<span.+class=["\']price["\'].+span>)\${0}/i', $strDescription, null, PREG_SPLIT_DELIM_CAPTURE);
			
		} else {
			// $arrDescription = preg_split('/<font.+color=["\']#990000["\'].+font>\K/i', $strDescription);	 // this works above PHP v5.2.4
			$arrDescription = preg_split('/(<font.+color=["\']#990000["\'].+font>)\${0}/i', $strDescription, null, PREG_SPLIT_DELIM_CAPTURE);
		}	
		$strDescription = $arrDescription[0] . $arrDescription[1];
		$arrDescription = preg_split('/<br.*?\/?>/i', $strDescription);		// devide the string into arrays by <br> or <br />
		return trim(implode(" ", $arrDescription));	// return them back to html text
	}
	
	function format_image($arrReplacementsForImg) {
		$arrRefVarsForImg = array("%link%", "%imgurl%", "%title%", "%textdescription%");
		return str_replace($arrRefVarsForImg, $arrReplacementsForImg, $this->arrUnitOptions['imgformat']);
	}	
	function format_item($arrReplacements)	{
		if (count(array("%link%", "%imgurl%", "%title%", "%url%", "%title%", "%htmldescription%", "%textdescription%", "%img%", "%items%")) < $this->i ) throw new Exception("");
		$arrRefVars = array("%link%", "%title%", "%htmldescription%", "%textdescription%", "%img%");
		return str_replace($arrRefVars, $arrReplacements, $this->arrUnitOptions['itemformat']);
	}				
	function format_output($output) {
		$strOutput = str_replace("%items%", $output, $this->arrUnitOptions['containerformat'])
		 . '<!-- generated by Amazon Auto Links powered by michaelunosoft. http://michaeluno.jp -->';
		// $strOutput = $this->arrUnitOptions['containerformat'];
// $strOutput = 'TEST OUTPUT';
		return $strOutput;
		// return wp_filter_post_kses( $strOutput );
		// return stripslashes(wp_filter_post_kses(addslashes($strOutput)));
		// sanitize the output
		// $dom = new DOMDocument();		// $dom = new DOMDocument('1.0', 'utf-8');
		// @$dom->loadhtml( $strOutput );
		
	}	
	function UrlsFromUnitLabel($unitlabel, $options=False) {
		if (!$options || !is_array($options))
			$options = get_option($this->pluginkey);
		$arrLinks = array();	
		foreach($options['units'][$unitlabel]['categories'] as $catname => $catinfo) {
			array_push($arrLinks, $catinfo['feedurl']);
		}
		return $arrLinks;
	}
	function DOMInnerHTML($element) {
		$innerHTML = ""; 
		$children = $element->childNodes; 
		foreach ($children as $child) { 
			$tmp_dom = new DOMDocument(); 
			$tmp_dom->appendChild($tmp_dom->importNode($child, true)); 
			$innerHTML.=trim($tmp_dom->saveHTML()); 
		} 
		return $innerHTML; 	
	}
	function formatvalidation($output, $i) {
		if (count(array("%link%", "%imgurl%", "%title%", "%link%", "%title%", "%htmldescription%", "%textdescription%", "%img%", "%items%")) < $i ) throw new Exception("");
	}		
}
?>