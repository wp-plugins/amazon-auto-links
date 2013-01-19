<?php
class AmazonAutoLinks_Forms_SelectCategories_ {

	/*  
		Warning: Never use update_option() in this class.
		this class is to just display form elements, not manipulating option values.
	*/
	
	public $classver = 'standard';
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
	function __construct($pluginkey, &$oOption) {
	
		// Include helper classes
		// require_once(dirname(__FILE__) . '/amazonautolinks_helperclass.php');
		$this->oAALfuncs = new AmazonAutoLinks_Helper_Functions($pluginkey);
		$this->oAALCatCache = new AmazonAutoLinks_CategoryCache($pluginkey, $oOption);
		
		
		$this->pluginkey = $pluginkey;
		$this->textdomain = $pluginkey;	
		
		$this->oOption = $oOption;
	}
	function _e($translatingtext) {
		_e($translatingtext, $this->textdomain);
	}
	function __($translatingtext) {
		return __($translatingtext, $this->textdomain);
	}	

	function GetCategoryListSidebar($doc) {
		
		// Sidebar - category list
		// Since v1.1.3
	
		/* Stylize the list (WordPress Admin CSS forces the list to have no left margin ) */
		$xPath = new DOMXPath($doc); 	// since getElementByID constantly returned false for unknown reasons, use DOMXPath
		$domleftCol = $xPath->query("//*[@id='zg_browseRoot']")->item(0);			
		// $domleftCol = $doc->getElementById('zg_browseRoot'); // this has started not working after using wp_remote_get() or removing unnecessary cache elements.
		$this->set_attributes_by_tagname($domleftCol, 'ul', 'style', 'margin-left:1em; list-style-type: none;');
		$this->set_attributes_by_tagname($domleftCol, 'li', 'style', 'margin-left:1em; list-style-type: none;');

		// get the sidebar html code
		return $strSidebarHTML =  $this->oAALfuncs->RemoveLineFeeds($doc->saveXML($domleftCol));
	}	
		
	function RenderFormCategorySelectionPreview($mode, $numSelectedCategories, $strRssLink, $strBreadcrumb, $strURL, $bReachedLimit, $strSidebarHTML, $numTab) {
		
		/*
		 * Renders the form fields for the category selection preview page.
		 * Todo: move this method to the appropriate class.
		 * */
	 
		$arrLinks = $this->oOption->get_category_links($mode);	// the actual feed urls to fetch
		$numImageWidth = $this->oOption->arrOptions[$mode]['imagesize'];		 
		$abspath = ABSPATH;
		
		// instantiate the core object
		$oAALCatPreview = new AmazonAutoLinks_Core($this->oOption->arrOptions[$mode], $this->oOption->arrOptions['general']);		// instantiate after setting the $mode variable
		$oAALUnitPreview = new AmazonAutoLinks_Core($this->oOption->arrOptions[$mode], $this->oOption->arrOptions['general']);
		
		?>		
		<form action="" method="post">
			<input type="hidden" name="<?php echo AMAZONAUTOLINKSKEY; ?>[submitted]" value="1" />
			<input type="hidden" name="<?php echo AMAZONAUTOLINKSKEY; ?>[tab]" value="<?php echo $numTab ?>" />
			<?php if ( function_exists('wp_nonce_field') ) { wp_nonce_field(AMAZONAUTOLINKSKEY, 'nonce'); }  // embed a nonce field ?>
			<table border="0" cellspacing="0" cellpadding="2" width="auto" style="margin-top:20px; padding:0; font-family: sans-serif; font-size: 12px; line-height: 1.4em;">
				<tbody>
					<tr>
						<td align="left" valign="top" width="20%" style="padding-right: 3em; border-right:1px solid #CCC;">
							<div style="width:200px; height:0px; border:1px; border-style:solid; border-color: rgba(0,0,0,0);"></div>
							<h4 style="margin-top:0; padding-top:0"><?php _e('Current Selection', 'amazonautolinks'); ?></h4>
						</td>
						<td align="left" valign="top" width="40%" style="padding-left: 3em;">		
							<h4 style="margin-top:0; padding-top:0"><?php _e('Added Categories', 'amazonautolinks'); ?></h4>
						</td>
						<td width="40%" rowspan="2" style="font-size:8px; line-height:1em; padding:0;">		
							<div align="right" class="submit" style="float:right; margin: -1em 3em 0 0; padding-right:0">
								<?php $strRightArrow = ($numSelectedCategories > 0 &&  count($this->oOption->arrOptions['units']) == 0) ? 'background: url(' . plugins_url('/img/rightarrow_attention.gif', AMAZONAUTOLINKSPLUGINFILE ) . ') no-repeat left;' : '';?>
								<div style="padding-left: 60px; <?php echo $strRightArrow; ?>">
									<input style="margin: 0 0 10px" type="submit" name="<?php echo AMAZONAUTOLINKSKEY; ?>[<?php echo $mode; ?>][save]" class="button-primary" <?php echo $numSelectedCategories > 0 ? '' : 'Disabled'; ?>  value="<?php echo $mode == 'newunit' ? __('Create', 'amazonautolinks') : __('Save', 'amazonautolinks'); ?>" />
									<br />
								</div>
								<?php $strRightArrow = ($strRssLink && $numSelectedCategories == 0 &&  count($this->oOption->arrOptions['units']) == 0 ) ? 'background: url(' . plugins_url('/img/rightarrow_attention.gif', AMAZONAUTOLINKSPLUGINFILE ) . ') no-repeat left;' : '';?>
								<input type="hidden" name="<?php echo AMAZONAUTOLINKSKEY; ?>[<?php echo $mode; ?>][addcategoryname]" value="<?php echo $strBreadcrumb ;?>" />
								<input type="hidden" name="<?php echo AMAZONAUTOLINKSKEY; ?>[<?php echo $mode; ?>][addcategoryfeedurl]" value="<?php echo $strRssLink ;?>" />
								<input type="hidden" name="<?php echo AMAZONAUTOLINKSKEY; ?>[<?php echo $mode; ?>][addcategorypageurl]" value="<?php echo $strURL ;?>" />
								<div style="padding-left: 60px; <?php echo $strRightArrow; ?>">
									<input style="margin: 0 0 10px;" type="submit" name="<?php echo AMAZONAUTOLINKSKEY; ?>[<?php echo $mode; ?>][addcurrentcategory]" class="button-primary" <?php echo $strRssLink && (!array_key_exists(trim($strBreadcrumb), $this->oOption->arrOptions[$mode]['categories'])) ? '' : 'Disabled' ?> value="<?php _e('Add Current Category', 'amazonautolinks'); ?>" />
									<br />
								</div>
								<div style="padding-left: 60px;"><!-- this is necessary for IE -->
									<input style="margin: 0 0 10px;" type="submit" name="<?php echo AMAZONAUTOLINKSKEY; ?>[<?php echo $mode; ?>][deletecategories]" class="button-primary" <?php echo $numSelectedCategories > 0 ? '' : 'Disabled'; ?> value="<?php _e('Delete Selected', 'amazonautolinks'); ?>" />
									<br />
								</div>
							</div>						
						</td>
					</tr>
					<tr>
						<td align="left" valign="top" width="20%" style="padding: 0 3em 0 1em; border-right:1px solid #CCC;"><?php if ($strRssLink) { echo $strBreadcrumb; } else { _e('None', 'amazonautolinks'); }?></h4></td>
						<td align="left" valign="top" width="40%" style="padding-left: 3em;">	
							<?php 
								if ( count( $this->oOption->arrOptions[$mode]['categories'] ) ) {								
									// list added categories with a check box form field
									foreach ( $this->oOption->arrOptions[$mode]['categories'] as $catname => $catinfo ) { ?>
										<input type="checkbox" name="<?php echo AMAZONAUTOLINKSKEY; ?>[<?php echo $mode; ?>][categories][<?php echo $catname; ?>]" value="1">
										&nbsp;&nbsp;
										<a style="text-decoration:none" href="<?php echo $this->oAALfuncs->selfURLwithoutQuery() . '?href=' .  $this->oAALfuncs->urlencrypt($catinfo['pageurl']) . '&page=' . $_GET['page'] . '&tab=' . $numTab . '&mode=' . $mode; ?>">
											<?php echo $catname; ?>
										</a>
										<br />
							<?php 	}
									if ( $bReachedLimit ) {
										echo '<div class="updated" style="padding:10px; margin:10px;">' 
											. __('To add more categories, upgrade to the <a href="http://michaeluno.jp/en/amazon-auto-links/amazon-auto-links-pro" target="_blank">pro version</a>.', 'amazonautolinks') 
											. '</div>';					
									}
								} else _e('No categories added.', 'amazonautolinks'); 							
							?>
						</td>
					</tr>				
					<tr>
						<td align="left" valign="top" width="20%" style="padding-right: 3em; border-right:1px solid #CCC;">
							<h4>
								<?php _e('Select Category', 'amazonautolinks'); ?>
							</h4>
						</td>
						<td align="left" valign="top" width="40%" style="padding-left: 3em; border-right:1px solid #CCC;">
							<h4>
								<?php echo $strRssLink ? __('Preview of This Category', 'amazonautolinks') : __('No Preview', 'amazonautolinks'); ?>
							</h4>
						</td>
						<td align="left" valign="top" width="40%" style="padding: 0 3em 0 3em;">
							<h4>
								<?php _e('Unit Preview', 'amazonautolinks'); ?>
							</h4>
						</td>
					</tr>
					<tr>
						<?php $strLeftDownArrow = (!$strRssLink && $numSelectedCategories == 0 &&  count($this->oOption->arrOptions['units']) == 0) ? 'background: url(' . plugins_url('/img/leftdownarrow_attention.gif', AMAZONAUTOLINKSPLUGINFILE ) . ') no-repeat right top;' : '' ;?>
						<td align="left" valign="top" width="20%" style="padding-right: 3em; border-right:1px solid #CCC; <?php echo $strLeftDownArrow; ?>">
							<?php echo $this->oAALfuncs->RemoveLineFeeds( $strSidebarHTML );?>
						</td>
						<td align="left" valign="top" width="40%" style="padding: 0 3em 0 3em; border-right:1px solid #CCC;">
							<div class="widthfixer" style="width:<?php echo $numImageWidth; ?>px;  border-bottom:1px solid #FFF;">
							</div>	
							<?php 
							if ( $strRssLink ) {
								flush(); 
								echo $oAALCatPreview->fetch(array($strRssLink)); 
							} else _e('Please select a category from the list on the left.', 'amazonautolinks');  
							?>
						</td>
						<td align="left" valign="top" width="40%" style="padding: 0 3em 0 3em">
							<div class="widthfixer" style="width:<?php echo $numImageWidth; ?>px;  border-bottom:1px solid #FFF;">
							</div>	
							<?php 
							if (count($arrLinks) > 0) { 
								flush(); 
								echo $oAALUnitPreview->fetch($arrLinks); 
							} else _e('Please add a category from the list after selecting it.', 'amazonautolinks');
							?>
						</td>
					</tr>
				</tbody>
			</table>
		</form>
		<?php
		flush();
	}	
	
	function form_selectcategories_iframe($numTab, $arrOptions) {	// as of v1.1.1 changed the name to form_selectcategories_iframe from form_selectcategories
	
		/*
		 * Deprecated as of v1.1.3
		 * */
		// change the height of iframe by calculating the imagesize and the number of items.
		// it's premised that this method is called inside a form tag. e.g. <form> ..  $oClass->form_selectcategories() .. </form>
		$numIframeHeight = $arrOptions['numitems'] * (150 + $arrOptions['imagesize']);
		$numIframeHeight = $this->oAALfuncs->fixnum($numIframeHeight, 1200, 1200);	// set the minimum height for cases that the user sets a few items to show, which the height becomes short.
		$numIframeWidth = $arrOptions['imagesize'] * 2 + 200;	
		
		// determine whether it is a new unit or editing an existin unit for the query parameter from the tab number.
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
		<iframe name="inlineframe" src="<?php echo AMAZONAUTOLINKSPLUGINURL . '/inc/amazonautolinks_selectcategory.php?abspath=' . $this->oAALfuncs->urlencrypt(ABSPATH) ;?>&mode=<?php echo $strMode; ?>" noresize frameborder="0" scrolling="no" width="100%" height="<?php echo $numIframeHeight; ?>" style="margin: 0; padding 0;"></iframe>
		<div class="widthfixer" style="width:<?php echo $numIframeWidth; ?>px;  border-bottom:1px solid #FFF;"></div>
		<?php		
	}

	/* methods for inline frame page */
	function load_dom_from_HTML( $strHTML, $strMbLang='uni' ) {
		// since v1.1.8 - created for the R18 redirect pages
		mb_language( $strMbLang ); 
		$strHTML = @mb_convert_encoding( $strHTML , 'HTML-ENTITIES', 'AUTO');
		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;
		@$doc->loadHTML( $strHTML );	
		return $doc;
		
	}
	function load_dom_from_url($strURL) {
	
		// create a dom document object
		mb_language($this->detect_lang($strURL)); // <-- without this, the characters get broken	
		$html = $this->oAALCatCache->get_html($strURL);
		$html = @mb_convert_encoding($html, 'HTML-ENTITIES', 'AUTO');		
		$doc = new DOMDocument();
		// $dom->validateOnParse = true;
		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;
		@$doc->loadHTML($html);	
		return $doc;
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
	function get_rss_link($doc) {
		
		// the parameter must be a dom object
		// extract the rss for the category
		$strRssLink = '';
		$id_rss = 'zg_rssLinks';
		$domRssLinks = $doc->getElementById($id_rss);
		if (!$domRssLinks) {
			
			// the root category does not provide a rss link, so return silently
			echo '<!-- ' . __METHOD__ . ': ' . $id_rss . ' ID could not be found. -->';
			return;
		}

		// remove the first h3 tag
		$nodeH3 = $domRssLinks->getElementsByTagName('h3')->item(0);
		$domRssLinks->removeChild($nodeH3);
		$nodeA1 = $domRssLinks->getElementsByTagName('a')->item(0);
		$strRssLink = $nodeA1->getAttribute('href');
		$arrURL = explode("ref=", $strRssLink, 2);
		$strRssLink = $arrURL[0];
		return $strRssLink;
	}
	function modify_href($doc, $arrQueries="") {	
	
		// this method converts href urls into a url with query which contains the original url
		// e.g. <a href="http://amazon.com/something"> -> <a href="localhost/me.php?href=http://amazon.com/something"
		// and the href value beceomes encrypted.
		// the parameter must be a dom object
		
		if ( !Is_Array( $arrQueries ) ) $arrQueries = array();	
		
		$strQueries = "";
		ForEach ($arrQueries as $key => $value) $strQueries .= '&' . $key . '=' . $value;		
		
		$xPath = new DOMXPath($doc); 	// since getElementByID constantly returned false for unknow reason, use xpath
		$domleftCol = $xPath->query("//*[@id='zg_browseRoot']")->item(0);		
		// $domleftCol = $doc->getElementById('zg_browseRoot');
		if (!$domleftCol) {
			echo '<!-- ' . __('Categories not found. Plaese consult the plugin developer.', 'amazonautolinks') . ' -->' . PHP_EOL;
			return false;
		}
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
		return true;
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