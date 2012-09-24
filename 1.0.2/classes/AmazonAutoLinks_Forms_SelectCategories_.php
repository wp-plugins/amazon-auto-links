<?php
class AmazonAutoLinks_Forms_SelectCategories_ {

	/*  
		Warning: Never use update_option() in this class.
		this class is to just display form elements, not manipulating option values.
	*/
	
	public $classver = 'standard';
	function __construct($pluginkey) {
	
		// Include helper classes
		// require_once(dirname(__FILE__) . '/amazonautolinks_helperclass.php');
		$this->oAALfuncs = new AmazonAutoLinks_Helper_Functions($pluginkey);
		
		$this->pluginkey = $pluginkey;
		$this->textdomain = $pluginkey;	
	}
	function _e($translatingtext) {
		_e($translatingtext, $this->textdomain);
	}
	function __($translatingtext) {
		return __($translatingtext, $this->textdomain);
	}	
	function form_selectcategories($numTab, $arrOptions) {
	
		// change the height of iframe by calculating the imagesize and the number of items.
		// it's premised that this method is called inside a form tag. e.g. <form> ..  $oClass->form_selectcategories() .. </form>
		$numIframeHeight = $arrOptions['numitems'] * (150 + $arrOptions['imagesize']);
		$numIframeHeight = $this->oAALfuncs->fixnum($numIframeHeight, 1200, 1200);	// set the minimum height for cases that the user sets a few items to show, which the height becomes short.
		$numIframeWidth = $arrOptions['imagesize'] * 2 + 200;	
		
		// determin whether it is a new unit or editing an existin unit for the query parameter from the tab number.
		// 100-199 -> new, 200 -> 200 -> edit
		$strMode = round(floor($numTab / 100 ) * 100, -2) == 100 ? 'new' : 'edit';		
		?>
		<!-- Display the proceeding page for creating a new unit -->
		<table width="100%" style="padding:0; margin:0;">
			<tr style="padding:0; margin:0;">
				<td width="12%" style="padding:0; margin:0;"><h4 style="font-weight: bold; padding:0; margin:0; color:#5E5E5E; font-size: 1em; font-family: sans-serif;"><?php _e('Unit Label', 'amazonautolinks'); ?></h4></td>
				<td width="8%" style="padding:0; margin:0;"><?php echo $arrOptions['unitlabel'];?></td>
				<td width="80%" style="padding:0; margin:0;">
					<div class="submit" style="float:right; margin: 0 22px 5px; padding:20px 0 0;" >
						<input style="margin:0; paddding:0" type="submit" class="button-primary" name="<?php echo $this->pluginkey ;?>[tab<?php echo $numTab;?>][gobackbutton]" value="<?php $this->_e('Go Back', 'amazonautolinks'); ?>" />
					</div>
				</td>
			</tr>
		</table>
		<iframe name="inlineframe" src="<?php echo plugins_url('amazonautolinks_selectcategory.php', dirname(__FILE__)) . '?abspath=' . $this->oAALfuncs->urlencrypt(ABSPATH) ;?>&mode=<?php echo $strMode; ?>" noresize frameborder="0" scrolling="no" width="100%" height="<?php echo $numIframeHeight; ?>" style="margin: 0; padding 0;"></iframe>
		<div class="widthfixer" style="width:<?php echo $numIframeWidth; ?>px;  border-bottom:1px solid #FFF;"></div>
		<?php		
	}
	
	/* methods for inline frame page */
	function load_dom($strURL, $lang) {
	
		// create dom document object
		if (!$lang) 
			$lang = 'uni';
		mb_language($lang); // <-- without this, the characters get broken
		$html = @mb_convert_encoding($this->get_html($strURL), 'HTML-ENTITIES', 'AUTO');
		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;
		@$doc->loadHTML($html);	
		return $doc;
	}
	function iscurlon() {
		if  (in_array  ('curl', get_loaded_extensions()))
			return true;
		else
			return false;
	}	
	function get_html($strURL) {
		if ($this->iscurlon())
			return $this->file_get_contents_curl($strURL);
		else
			return file_get_contents($strURL);
	}
	function file_get_contents_curl($url) {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);       

		$data = curl_exec($ch);
		curl_close($ch);

		return $data;
	}
	
	function get_rss_link($doc) {
		
		// the parameter must be a dom object
		// extract the rss for the category
		$strRssLink = '';
		$id_rss = 'zg_rssLinks';
		$domRssLinks = $doc->getElementById($id_rss);
		if ($domRssLinks) {		// the root category does not provide a rss link
	
			// remove the first h3 tag
			$nodeH3 = $domRssLinks->getElementsByTagName('h3')->item(0);
			$domRssLinks->removeChild($nodeH3);
			$nodeA1 = $domRssLinks->getElementsByTagName('a')->item(0);
			$strRssLink = $nodeA1->getAttribute('href');
			$arrURL = explode("ref=", $strRssLink, 2);
			$strRssLink = $arrURL[0];			
		}	
		return $strRssLink;
	}
	function modify_href($doc, $arrQueries="") {	
	
		// this method converts href urls into a url with query which contains the original url
		// e.g. <a href="http://amazon.com/something"> -> <a href="localhost/me.php?href=http://amazon.com/something"
		// and the href value beceomes encrypted.
		// the parameter must be a dom object
		
		if (!Is_Array($arrQueries)) {
			$arrQueries = array();
		}
		$strQueries = "";
		ForEach ($arrQueries as $key => $value) {
			$strQueries .= '&' . $key . '=' . $value;		//'&abspath=' . $this->oAALfuncs->urlencrypt($abspath)
		}
		$id_sidebar = 'zg_browseRoot';
		$domleftCol = $doc->getElementById($id_sidebar);
		ForEach( $domleftCol->getElementsByTagName('a') as $nodeA) {
			$href = $nodeA->getAttribute('href');
			$nodeA->removeAttribute('href');
			
			// strip the string after 'ref=' in the url
			// e.g. http://amazon.com/ref=zg_bs_123/324-5242552 -> http://amazon.com
			$arrURL = explode("ref=", $href, 2);
			$href = $arrURL[0];
			
			// get the current self-url. needs to exclude the query part 
			// e.g. http://localhost/me.php?href=http://....  -> http://localhost/me.php
			$strSelfURL = $this->oAALfuncs->selfURLwithoutQuery();
			$strNewLink = $strSelfURL . '?href=' . $this->oAALfuncs->urlencrypt($href) . $strQueries;	
			$nodeA->setAttribute('href', $strNewLink);
		}	
	}
	function set_attributes_by_tagname($oNode, $strTagName, $strAtr, $strNewValue) {
		Foreach( $oNode->getElementsByTagName($strTagName) as $node) { 
			$node->setAttribute($strAtr, $strNewValue);
		}
	}
	function breadcrumb($doc, $strRoot) {
	
		// creates a breadcrumb of the Amazon page sidebar
		// this is specific to Amazon page so if the page design changes, it won't work
		// especially it uses unique id and class names including zg_browseRoot, zg_selected
		// the sidebar which lists categories uses ul and li tags
		
		$arrBreadcrumb = array();
		
		// extract the current selecting category with xpath
		$xpath = new DomXpath($doc);
		$nodeZg_Selected = $xpath->query("//*[@id='zg_browseRoot']//*[@class='zg_selected']"); 
		$strCurrentCategory = trim($nodeZg_Selected->item(0)->nodeValue);
		array_push($arrBreadcrumb, $strCurrentCategory);
		
		// climb the node
		$nodeClimb = $nodeZg_Selected->item(0)->parentNode;		// this is the weird part that item() method is required. once the parent node is retrieved, it's no more needed.
		Do {	
			if ($nodeClimb->nodeName == 'ul') {
				$nodeUpperUl = $nodeClimb->parentNode;
				$strUpperCategory = $nodeUpperUl->getElementsByTagName('li')->item(0)->nodeValue;
				array_push($arrBreadcrumb, trim(preg_replace('/^.+\s?/', '', $strUpperCategory)));
			}
			$nodeClimb = $nodeClimb->parentNode;	
		} While ( $nodeClimb && $nodeClimb->getAttribute('id') != 'zg_browseRoot' );
		array_pop($arrBreadcrumb);	// remove the last element
		array_push($arrBreadcrumb, strtoupper($strRoot));	// set the last element to the country code
		$arrBreadcrumb = array_reverse($arrBreadcrumb);
		$strBreadcrumb = implode(" > ", $arrBreadcrumb);
		return $strBreadcrumb;
	}
}
?>