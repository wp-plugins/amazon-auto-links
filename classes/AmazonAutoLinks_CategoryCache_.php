<?php
class AmazonAutoLinks_CategoryCache_ {
/*
	Since: v1.0.3
	Description:
		This class is used to build category caches for the category selection page.
		The methods are called in a run-once scheduled event prior to user's opening the page.
		It is like link prefetching functionality.
		
		This class uses the 'amazonautolinks_catcache_events' key for the option which is separated from
		the plugin main option key, 'amazon-auto-links' because cron tasks constantly updates the 
		option in the background, it should not affect other processes using the main option and
		vice versa.
*/
	protected $pluginname = 'Amazon Auto Links';
	protected $pluginkey = 'amazon-auto-links';
	protected $eventoptionkey = 'amazonautolinks_catcache_events';
	protected $pageslug = 'amazon-auto-links';
	protected $textdomain = 'amazon-auto-links';	
	protected $arrCountryURLs = array(
		'AT'	=> 'http://www.amazon.de/gp/bestsellers/',
		'CA'	=> 'http://www.amazon.ca/gp/bestsellers/',
		'CN'	=> 'http://www.amazon.cn/gp/bestsellers/',
		'FR'	=> 'http://www.amazon.fr/gp/bestsellers/',
		'DE'	=> 'http://www.amazon.de/gp/bestsellers/',
		'IT'	=> 'http://www.amazon.it/gp/bestsellers/',
		'JP'	=> 'http://www.amazon.co.jp/gp/bestsellers/',
		'UK'	=> 'http://www.amazon.co.uk/gp/bestsellers/',
		'ES'	=> 'http://www.amazon.es/gp/bestsellers/',
		'US'	=> 'http://www.amazon.com/gp/bestsellers/',
	);
	public $arrCountryBlackCurtainURLs = array(
		'AT'	=> 'http://www.amazon.de/gp/product/black-curtain-redirect.html',
		'CA'	=> 'http://www.amazon.ca/gp/product/black-curtain-redirect.html',
		'CN'	=> 'http://www.amazon.cn/gp/product/black-curtain-redirect.html',
		'FR'	=> 'http://www.amazon.fr/gp/product/black-curtain-redirect.html',
		'DE'	=> 'http://www.amazon.de/gp/product/black-curtain-redirect.html',
		'IT'	=> 'http://www.amazon.it/gp/product/black-curtain-redirect.html',
		'JP'	=> 'http://www.amazon.co.jp/gp/product/black-curtain-redirect.html',
		'UK'	=> 'http://www.amazon.co.uk/gp/product/black-curtain-redirect.html',
		'ES'	=> 'http://www.amazon.es/gp/product/black-curtain-redirect.html',
		'US'	=> 'http://www.amazon.com/gp/product/black-curtain-redirect.html',	
	);
	public $arrCountryLang = array(
		'AT'	=> 'uni',
		'CA'	=> 'uni',
		'CN'	=> 'uni',
		'FR'	=> 'uni',
		'DE'	=> 'uni',
		'IT'	=> 'uni',
		'JP'	=> 'ja',
		'UK'	=> 'en',
		'ES'	=> 'uni',
		'US'	=> 'en',	
	);	
	function __construct( $pluginkey, &$oOption="") {
			
		// set up properties
		$this->pluginkey = $pluginkey;
		// $this->eventoptionkey = $pluginkey . 'catcache_events';
		// set up classes
		$this->oAALfuncs = new AmazonAutoLinks_Helper_Functions($pluginkey);
	
		// store the options 
		// since v1.1.1 - added the option to switch on/off the prefetch functionality
		$this->oOption = $oOption;
	
		// format the event option
		$this->formatoption();
	}
	function formatoption() {
		$arrCatCacheEvents = get_option('amazonautolinks_catcache_events');
		$arrCatCacheEvents = array_merge(array(), is_array($arrCatCacheEvents) ? $arrCatCacheEvents : array());
		update_option('amazonautolinks_catcache_events', $arrCatCacheEvents);
	}	
	function eventkey($strURL) {
		return 'aal_' . sha1($strURL);
	}
	function schedule_prefetch($strURL='') {

		if (is_array($this->oOption->arrOptions) && empty($this->oOption->arrOptions['general']['prefetch'])) {
			echo '<!-- Amazon Auto Links: The prefetch function is disabled.  -->';		
			return;
		}
	
		echo '<!-- Amazon Auto Links: The prefetch function is called. Deciding whether prefetch tasks should be created. -->';		
		// save urls in the option key with the key name of 'aal_' . sha1($strURL), 
		// which is used as the event action name
		// if the $strURL is empty, it means county urls (the root page) will be inserted
	
		// to check if the url is already scheduled
		$arrCatCacheEvents = get_option('amazonautolinks_catcache_events');
	
		// if $strURL has a value, it means the url is specified, then pre-fetch only the url contents.
		if ($strURL != '') {	
			$strURL = preg_replace('/ref=.+$/i', '', $strURL);	// http://amazon.com/..../ref=nnn/1832-39087  to http://amazon.com/..../
			$this->schedule_prefetch_from_url_array($this->get_subcategories_from_url($strURL), $arrCatCacheEvents);
			return;
		} 
	
		// otherwise, fetch root pages.
		$this->schedule_prefetch_from_url_array($this->arrCountryURLs, $arrCatCacheEvents);		
	}
	function run_in_background($strMsg='called a php process in the background') {
	
		// this triggers the WP Cron API and runs the scheduled task
		$cron_url = site_url('?amazonautolinks_cache=category');	// $cron_url = site_url('wp-cron.php?doing_wp_cron=0');
        wp_remote_post( $cron_url, array( 'timeout' => 0.01, 'blocking' => false, 'sslverify' => apply_filters( 'https_local_ssl_verify', true ) ) );
		echo '<!-- ' . __METHOD__ . ': ' . $strMsg . ' -->';
		AmazonAutoLinks_Log($strMsg, __METHOD__ );	
		
	}	
	function schedule_prefetch_from_url_array($arrURLs, $arrEvents) {
		$i = 0;
		foreach($arrURLs as $strURL) {
		
			// make sure the urls ends with a trailing slash
			$strURL = preg_replace("/[^\/]$/i", "$0/", $strURL);
		
			// if the transient for this url already exists, skip it			
			$html = get_transient( $this->eventkey($strURL) ) ;
			if ( $html ) {
			// if( false !== $html ) {		// if ( true === ( $value = get_transient( $this->eventkey($strURL) ) ) ) <-- not sure this doesn't work
				echo '<!-- The transient for the url already exists, not scheduling prefetch. : ' . $this->eventkey($strURL) . ' : ' .  $strURL . ' -->';
				// AmazonAutoLinks_Log('Transient already exists, not scheduling prefetch : ' . $this->eventkey($strURL) . ' : ' .  $strURL, __METHOD__ );
				continue;
			}
		
			// if the url is being scheduled now, skip it 
			if (in_array($strURL, $arrEvents)) {
				echo '<!-- The transient is in the event que. : ' . $this->eventkey($strURL) . ' : ' .  $strURL . ' -->';
				// AmazonAutoLinks_Log('transient is in the event que. : ' . $this->eventkey($strURL) . ' : ' .  $strURL, __METHOD__ );
				continue;
			}
				
			// go for it
			$this->schedule_prefetch_event_now($strURL);
			
			// count
			$i++;
		}
		
		echo '<!-- ' . $i . ' url(s) are scheduled to pre-fetch. -->';
		AmazonAutoLinks_Log($i . ' url(s) are scheduled to pre-fetch.', __METHOD__ );
		if ($i == 0) {
			AmazonAutoLinks_Log('Not calling a background process because 0 event is scheduled.', __METHOD__ );
			return;
		}

		// run the WordPress Cron silently before the user loads another page.
		AmazonAutoLinks_Log('Line befeore run_in_background.', __METHOD__ );
		$this->run_in_background('run the WordPress Cron silently before the user loads another page.', __METHOD__);
		AmazonAutoLinks_Log('Line after run_in_background.', __METHOD__ );		
	}
	function schedule_prefetch_event_now($strURL) {
		
		$this->set_catcache_url($strURL);		// save the action name in the option.
		$strKey = $this->eventkey($strURL);		// define the action name
		
		// Without this add_action(), the event won't fire even though this plugin loasds saved action names at the beginning. 
		// $strKey holds the action name for the event. The second paramer is the function name defined in amazonautolinks_initial_load.php
		add_action($strKey, 'AmazonAutoLinks_CacheCategory');	
		
		// the value in the first parameter, time(), means do it right away. But actually it will be executed in the next page load.
		wp_schedule_single_event(time(), $strKey);	
		AmazonAutoLinks_Log('prefetch task scheduled: ' . $strKey . ': ' . $strURL, __METHOD__ );
		echo '<!-- Amazon Auto Links: the prefetch task is scheduled just now: ' . $strKey . ': ' . $strURL . ' -->'; 
	}
	function set_catcache_url($strURL) {
	
		// saves the event option. The event option is managed separately from the other option
		// since events occur sort of asyncronomously (pseudo-async)
		$arrCatCacheEvents = get_option('amazonautolinks_catcache_events');
		$arrCatCacheEvents[$this->eventkey($strURL)] = $strURL;	// add the new item to the event option array
		update_option('amazonautolinks_catcache_events', $arrCatCacheEvents);	
	}
	function cache_category($strURL) {

		// this method is similar to cahce_html() expect this caches modified html code while the other one cache the entire code 
		// this is for displaying category and leave the feed url to be retrieved. Other parts of the page will be discarded.

		// make sure the urls ends with trailing slash
		$strURL = preg_replace("/[^\/]$/i", "$0/", $strURL);
		
		$strTransient = $this->eventkey($strURL); // the char lengths for the transient key must be within 45, which is the limitation set by WordPress.
		$html = get_transient($strTransient);	
		if( $html ) { // if cache is available, do nothing
			AmazonAutoLinks_Log('page cache already exists: ' . $strURL . ' : ' . $strTransient, __METHOD__);
			$this->unset_event($strURL);
			return false;
		}		
		
		// if the cache is empty
		$html = $this->extract_necessary_parts_for_category($strURL);
		set_transient($strTransient, $this->oAALfuncs->encrypt($html), 60*60*48 );	// 2 day lifetime
		AmazonAutoLinks_Log('page is cached. Unsetting the event.: ' . $strURL . ' : ' . $strTransient, __METHOD__);
		$this->unset_event($strURL);
		return true;		
	}
	function extract_necessary_parts_for_category($strURL) {
		$doc = $this->load_dom_from_url($strURL);
		$html = $this->get_htmltext_from_id($doc, 'zg_browseRoot');	// zg_browseRoot is the id attribute for categories
		$html .= $this->get_htmltext_from_id($doc, 'zg_rssLinks');	// zg_rssLinks is the id attribute for the rss url
		return $html;
	}
	function load_dom_from_url($strURL) {
	
		// loads the dom object from a given url.
		mb_language($this->detect_lang($strURL)); 
		// $html = $this->oAALfuncs->get_html($strURL);	//<-- not sure why it was using non caching method
		$html = $this->get_html($strURL);
		$html = @mb_convert_encoding($html, 'HTML-ENTITIES', 'AUTO');
		$doc = new DOMDocument();	
		@$doc->loadHTML($html);		
		return $doc;
	}	
	function get_htmltext_from_id($doc, $strID) {
		$xPath = new DOMXPath($doc); 	// since getElementByID constantly returned false for unknow reason, use xpath
		$nodeID = $xPath->query("//*[@id='" . $strID . "']")->item(0);
		// $nodeID = $doc->getElementById($strID);
		if (!$nodeID) {
			AmazonAutoLinks_Log('ERROR: ' . $strID . ' node cannot be created.', __METHOD__);
			return;				
		}
		return trim($doc->saveXML($nodeID));
	}
	function renew_category_cache($strURL) {
	
		// called from amazonautolinks_selectcategory.php to renew the cache when it fails to load the category block
		AmazonAutoLinks_Log('renewing cache: ' . $strURL , __METHOD__);
		$strTransient = $this->eventkey($strURL);
		delete_transient($strTransient);
		$html = $this->extract_necessary_parts_for_category($strURL);
		set_transient($strTransient, $this->oAALfuncs->encrypt($html), 60*60*48 );	// 2 day lifetime
	}
	function cache_html($strURL) {
	
		// as of v1.0.4 this method is not used but leave it there in case there is a change to use it.
		$strTransient = $this->eventkey($strURL); // the char lengths for the transient key must be within 45. 			
		$html = get_transient($strTransient);	
		if( $html ) { // if cache is available, do nothing
			// echo '<!-- ' . __METHOD__ . ': page cache already exists: ' . $strTransient . ' : ' . $strURL . ' -->';
			AmazonAutoLinks_Log('page cache already exists: ' . $strURL . ' : ' . $strTransient, __METHOD__);
			$this->unset_event($strURL);
			return false;
		}
		
		// if the cache is empty
		$html = $this->oAALfuncs->get_html($strURL);
		set_transient($strTransient, $this->oAALfuncs->encrypt($html), 60*60*48 );	// 2 day lifetime
		echo '<!-- ' . __METHOD__ . ': page is cached: ' . $strTransient . ' : ' . $strURL . ' -->';
		AmazonAutoLinks_Log('page is cached. Unsetting the event.: ' . $strURL . ' : ' . $strTransient, __METHOD__);
		$this->unset_event($strURL);
		return true;
	} 
	function unset_event($strURL) {
		$arrCatCacheEvents = get_option('amazonautolinks_catcache_events');
		unset($arrCatCacheEvents[$this->eventkey($strURL)]);
		unset($arrCatCacheEvents[$this->eventkey(rtrim($strURL,"/"))]);	// for backward compatibility v1.0.6 or below
		update_option('amazonautolinks_catcache_events', $arrCatCacheEvents);
	}
	function get_html($strURL) {
	
		// retrieve web contents from the cache if available; otherwise, retrieve them from the web.
		$strTransient = $this->eventkey($strURL);	// the char lengths for the transient key must be within 45. sha1() generates 40 length caracters.
		$html = get_transient($strTransient);		// retrieve the data from cache
		if( false === $html ) {	// if the cache is empty
			echo '<!-- ' . __METHOD__ . ' : transient is not used: ' . $strTransient . ': ' . $strURL . ' -->';	
			
			/*
				wp_remote_get() somehow breaks the href strings passed as a url query. 
				Probably it's an encoding issue but I could not find the exact problem.
				If the problem is found, the web grabbing method should be switched to the WordPress HTTP API
				since currently it has only two options cURL or file_get_contents().
			*/
			
			$html = $this->oAALfuncs->get_html($strURL);
			set_transient($strTransient, $this->oAALfuncs->encrypt($html), 60*60*48 );	// the storing data must be encrypted; otherwise, the data gets sanitized by WordPress and crrupts the cache
			echo '<!-- Transient is now saved: ' . $strTransient . ' -->' ;
			AmazonAutoLinks_Log('Transient is now saved: ' . $strTransient , __METHOD__ );
			return $html;
		}
		echo '<!-- Amazon Auto Links : transient is used: ' . $strTransient . ' : ' . $strURL . ' -->';	
		AmazonAutoLinks_Log('Transient is used: ' . $strURL , __METHOD__ );		
		return $this->oAALfuncs->decrypt($html);	
	}
	function get_subcategories_from_url($strURL) {
	
		// retrieves subcategory urls from the given url and returns an array containing the subcategory urls
		
		// Without spcifying the encoding with mb_language(), the characters get broken.
		// I don't know if there are other workarounds. Let me know if anybody finds a solution for this, please contact to Michael Uno: michael@michaeluno.jp
		mb_language($this->detect_lang($strURL)); 
		// $html = $this->oAALfuncs->get_html($strURL);	//<-- not sure why it was using the non-caching method
		$html = $this->get_html($strURL);
		$html = @mb_convert_encoding($html, 'HTML-ENTITIES', 'AUTO');
		$doc = new DOMDocument();	
		@$doc->loadHTML($html);	
		return $this->get_subcategories_from_dom($doc);
	}
	function get_subcategories_from_dom($doc) {
		$xPath = new DOMXPath($doc); 	// since getElementByID constantly returned false for unknow reason, use xpath
		$domleftCol = $xPath->query("//*[@id='zg_browseRoot']")->item(0);
		// $domleftCol = $doc->getElementById('zg_browseRoot');	// zg_browseRoot is the id of the tag containing the list of sub categories
		if (!$domleftCol)
			return;
		$arrURLs = array();
		ForEach( $domleftCol->getElementsByTagName('a') as $nodeA) {
			$strURL = preg_replace('/ref=.+$/i', '', $nodeA->getAttribute('href'));	// http://amazon.com/..../ref=nnn/1832-39087  to http://amazon.com/..../
			array_push($arrURLs, $strURL);
		}
		return array_unique($arrURLs);	
	}
	function detect_lang($strURL) {
	
		// store the checking domain into $arrThisURL['host']
		$arrThisURL = parse_url($strURL);
		
		// parse through the county urls stored in this class property to find the match
		foreach($this->arrCountryURLs as $strID => $strCountryURL) {
			$arrCountryURL = parse_url($strCountryURL);
			if ($arrThisURL['host'] == $arrCountryURL['host']) 	// matched
				return $this->arrCountryLang[$strID];
		}
		
		// this line should not be reached because it means it did not match any but just in case
		return 'uni'; // let's set the default to uni, which likely work in most cases
	}
	/* Added in v1.0.7 */
	function get_rsslink_from_urls($arrURLs) {
		
		// since v1.0.7
		// creates and returns an array containing feed urls retrieved from the given category urls passed as an array.
		$arrFeedURLs = array();
		foreach($arrURLs as $strURL) {
			$strRssURL = $this->get_rsslink_from_url($strURL);
			if ($strRssURL) array_push($arrFeedURLs, $strRssURL);
		}
		if (count($arrFeedURLs) > 0) return $arrFeedURLs;
		return;
	}
	function get_rsslink_from_url($strURL) {
		
		// since v1.0.7
		// finds the feed url from the given category url. 
		$doc = $this->load_dom_from_url($strURL);
		if (!$doc) return;
		$nodeRssLinks = $this->get_node_from_id($doc, 'zg_rssLinks');	// zg_rssLinks is the id attribute for the rss url
		if (!$nodeRssLinks) return;
		return $this->get_feed_url_from_dom($nodeRssLinks);	// if failed, returns nothing.
	}
	function get_node_from_id($doc, $strID) {
		
		// sinve v1.0.7
		// since getElementByID constantly returned false for unknow reason, use xpath
		$xPath = new DOMXPath($doc); 	
		$nodeID = $xPath->query("//*[@id='" . $strID . "']")->item(0);
		if (!$nodeID) {
			AmazonAutoLinks_Log('ERROR: ' . $strID . ' node cannot be created.', __METHOD__);
			return;				
		}
		return $nodeID;
	}
	function get_feed_url_from_dom($domRssLinks) {

		// sinve v1.0.7
		// this method is similar to the get_rss_link() method defined in AmazonAutoLinks_Forms_SelectCategories
		
		$nodeH3 = $domRssLinks->getElementsByTagName('h3')->item(0); // remove the first h3 tag
		if (!$nodeH3) return;
		$domRssLinks->removeChild($nodeH3);
		$nodeA1 = $domRssLinks->getElementsByTagName('a')->item(0);
		if (!$nodeA1) return;
		$strRssLink = $nodeA1->getAttribute('href');
		$arrURL = explode("ref=", $strRssLink, 2);
		$strRssLink = $arrURL[0];
		return $strRssLink;
	}	
}
?>