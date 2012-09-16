<?php
	// define constants
	define("AMAZONAUTOLINKSKEY", "amazonautolinks");
	define("AMAZONAUTOLINKSPAGESLUG", "amazonautolinks");	//basename(__FILE__, ".php")  );
	define("AMAZONAUTOLINKSTEXTDOMAIN", "amazonautolinks");		
	
	// declare variable
	$bReachedLimit = false;
	
	// Include helper classes. This must be done before anything else
	require_once(dirname(__FILE__) . '/classes/AmazonAutoLinks_Helper_Functions_.php');
	$oAALfuncs = new AmazonAutoLinks_Helper_Functions_(AMAZONAUTOLINKSKEY);

	// Load as a WordPress Plugin
	$file_admin = "";
	if (IsSet($_GET["abspath"])) {
		$abspath = preg_replace('/(\\\\){2,}/', '$1', $oAALfuncs->urldecrypt($_GET["abspath"]));
		$file_admin = $abspath . 'wp-admin/admin.php' ;
		if (!file_exists($file_admin)) {
			echo 'Could not locate wp-admin.php. Please consult the administrator.';
			exit;
		} 
		require_once( $file_admin ); 

	} else {
		echo 'Could not load wp-admin.php. Please consult the administrator.';
		exit;
	}
	// for style sheet
	$cssurl_wpadmin = admin_url( '/css/wp-admin.css?ver=') . get_bloginfo( 'version' ); // get_bloginfo( 'version' ));
	$cssurl_colorsfresh = admin_url('/css/colors-fresh.css') . '?ver=' . get_bloginfo( 'version' );
	$cssurl_catselect = plugins_url('/css/amazonautolinks_catselect.css', __FILE__);
		
	// Load SimplePie in the WordPress  // be aware that WordPress SimplePie works differently than the original one
	// require_once (ABSPATH . WPINC . '/class-simplepie.php');
		 
	// AmazonAutoLinks Admin Class 
	$oAALSelectCategories = new AmazonAutoLinks_Forms_SelectCategories(AMAZONAUTOLINKSKEY);

	// AmazonAutoLinks Option Class
	$oAALOptions = new AmazonAutoLinks_Options(AMAZONAUTOLINKSKEY);

	// AmazonAutoLinks Category Cache Class
	$oAALCatCache = new AmazonAutoLinks_CategoryCache(AMAZONAUTOLINKSKEY);
	
	/* Retrieve Options from Database*/
	// $oAALOptions->arrOptions = get_option(AMAZONAUTOLINKSKEY);

	// check the $_GET array to determine if it is a new unit or editing an existing unit.
	if (IsSet($_GET['mode']) && $_GET['mode'] == 'new' ) {		
		$mode = 'newunit';
	} else if (IsSet($_GET['mode']) && $_GET['mode'] == 'edit' ) {
		$mode = 'editunit';
	} else {
		// should return; do nothing.
		echo 'The page is loaded in the wrong way.';
		return;
	} 
	
	// Amazon Auto Links Class
	// require_once(dirname(__FILE__) . '/amazonautolinks_classes.php');
	$oAALCatPreview = new AmazonAutoLinks_Core($oAALOptions->arrOptions[$mode], $oAALOptions->arrOptions['general']);		// instantiate after setting the $mode variable
	$oAALUnitPreview = new AmazonAutoLinks_Core($oAALOptions->arrOptions[$mode], $oAALOptions->arrOptions['general']);
	
	// for the initial array components
	// $oAALOptions->arrOptions[$mode] must be an array from the previous page (the caller page of the iframe)
	if (!is_array($oAALOptions->arrOptions[$mode]['categories'])) 
		$oAALOptions->arrOptions[$mode]['categories'] = array();

// $oAALfuncs->print_r($oAALOptions->arrOptions[$mode]);		
		
	/* POST Data */
	// Verify nonce 
	if (IsSet($_POST[AMAZONAUTOLINKSKEY]['submitted']) && !wp_verify_nonce($_POST['nonce'], AMAZONAUTOLINKSKEY)) 
		return;

	// check if the Create/Save button is pressed
	if (IsSet($_POST[AMAZONAUTOLINKSKEY][$mode]['save'])) {
	
		// if the unit label is changed, delete the old unit label options and save the submitted data to a new label element
		if ($mode == 'editunit' && $oAALOptions->arrOptions['editunit']['prior_unitlabel'] != $oAALOptions->arrOptions['editunit']['unitlabel'])
			unset($oAALOptions->arrOptions['units'][$oAALOptions->arrOptions['editunit']['prior_unitlabel']]);
		
		// insert the options with the key name of the unit label
		if (empty($oAALOptions->arrOptions[$mode]['unitlabel']['id']))
			$oAALOptions->arrOptions[$mode]['unitlabel']['id'] = uniqid();	// sets an id if there isn't --- the check is for backward-compatibility when widget is not supported; widget uses this identifier to declare the class
		$oAALOptions->update_unit($oAALOptions->arrOptions[$mode]['unitlabel'], $mode);
		$oAALOptions->arrOptions['tab1']['creatednewunit'] = true;		// set a flag value	//<-- needs a comment which indicates where this flag is used
		$fCreatedNewUnit = True;		
	}
	// check if the "Add Current Category" button is pressed
	else if (IsSet($_POST[AMAZONAUTOLINKSKEY][$mode]['addcurrentcategory'])) {
		$numSelectedCategories = $oAALOptions->add_category(
			$mode,		// NewUnit or EditUnit
			$_POST[AMAZONAUTOLINKSKEY][$mode]['addcategoryname'],	//	$strCatName: the submitted category breadcrumb name
			array(	// $arrCatInfo
				'feedurl' => $_POST[AMAZONAUTOLINKSKEY][$mode]['addcategoryfeedurl'],
				'pageurl' => $_POST[AMAZONAUTOLINKSKEY][$mode]['addcategorypageurl'])
		);
		if ($numSelectedCategories == -1) {
			$bReachedLimit = True;
			$numSelectedCategories = 3;
		}
	}
	// check if the "Delete Checked Categories" button is pressd
	else if (IsSet($_POST[AMAZONAUTOLINKSKEY][$mode]['deletecategories']) && IsSet($_POST[AMAZONAUTOLINKSKEY][$mode]['categories'])) {
		$numSelectedCategories = $oAALOptions->delete_categories(
			$mode,		// NewUnit or EditUnit
			$_POST[AMAZONAUTOLINKSKEY][$mode]['categories']	//	array holding the category names to delete
		);
	}
	else {
		
		// new landing, do nothing
		// just count the number of categories
		$numSelectedCategories = count($oAALOptions->arrOptions[$mode]['categories']);
	}
	$arrLinks = $oAALOptions->get_category_links($mode);
	$numImageWidth = $oAALOptions->arrOptions[$mode]['imagesize'];
	
// print_r(get_declared_classes());	
// $oAALfuncs->print_r($oAALOptions->arrOptions['events']);	<-- 'events' is moved to $option['amazonautolinks_events']
// AmazonAutoLinks_CacheCategory();

?>
<html>
	<head>
		<link rel="stylesheet" href="<?php echo $cssurl_wpadmin; ?>" type="text/css" media="all" />
		<link rel="stylesheet" id="colors-css" href="<?php echo $cssurl_colorsfresh; ?>" type="text/css" media="all" />
		<link rel="stylesheet" href="<?php echo $cssurl_catselect; ?>" type="text/css" media="all" />
	</head>
	<body>
	<?php if (IsSet($fCreatedNewUnit) && $fCreatedNewUnit == True) { 	// The new unit was created ?>	

		<div class="updated" style="padding: 10px;"><?php if ($mode=='newunit') { _e('The unit was successfully created. Go to the Manage Units page from the upper tab.', 'amazonautolinks'); } else { _e('The unit options are edited.', 'amazonautolinks');} ?></div>
	</body></html>
	<?php return; ?>
	<?php
	} else { 

		/* ---------------------- Create the Category List Sidebar --------------------------------- */

		// first check the $_GET array
		$url = isset($_GET['href']) ? $oAALfuncs->urldecrypt($_GET['href']) : $oAALOptions->arrOptions[$mode]['countryurl'];
				
		// create dom document object
		$doc = $oAALSelectCategories->load_dom($url, $oAALOptions->arrOptions[$mode]['mblang']);
		if (!doc) {
			echo '<div class="error" style="padding:10px; margin:10px;">' . __('Could not load categories. Please consult the plugin developer.', 'amazonautolinks') . '</div>';
		}
		
		// extract the rss for the category
		$strRssLink = $oAALSelectCategories->get_rss_link($doc);

		/* Edit the href attribute to add the query */
		$oAALSelectCategories->modify_href($doc, array('abspath' => $oAALfuncs->urlencrypt($abspath), 'mode' => $_GET['mode']));
			
		/* Stylize the list (WordPress Admin CSS forces the list to have no left margin ) */
		$domleftCol = $doc->getElementById('zg_browseRoot');
		$oAALSelectCategories->set_attributes_by_tagname($domleftCol, 'ul', 'style', 'margin-left:1em; list-style-type: none;');
		$oAALSelectCategories->set_attributes_by_tagname($domleftCol, 'li', 'style', 'margin-left:1em; list-style-type: none;');

		/* Create Breadcrumb */
		$strBreadcrumb = $oAALSelectCategories->breadcrumb($doc, $oAALOptions->arrOptions[$mode]['country']);

		// end of Sidebar ---------------------------------------------------------------------------------------
	}
	?>
		
	<form action="" method="post">
		<input type="hidden" name="<?php echo AMAZONAUTOLINKSKEY; ?>[submitted]" value="1" />
		<?php if ( function_exists('wp_nonce_field') ) { wp_nonce_field(AMAZONAUTOLINKSKEY, 'nonce'); }  // embed a nonce field ?>
		<table border="0" cellspacing="0" cellpadding="2" width="100%" style="margin-top:0px; padding:0; font-family: sans-serif; font-size: 12px; line-height: 1.4em;">
			<tbody>
				<tr>
					<td align="left" valign="top" width="20%" style="padding-right: 3em; border-right:1px solid #CCC;"><h4 style="margin-top:0; padding-top:0"><?php _e('Current Selection', 'amazonautolinks'); ?></h4></td>
					<td align="left" valign="top" width="40%" style="padding-left: 3em;">		
						<h4 style="margin-top:0; padding-top:0"><?php _e('Added Categories', 'amazonautolinks'); ?></h4>
					</td>
					<td width="40%" rowspan="2" style="font-size:8px; line-height:1em; padding:0;">		
						<div align="right" class="submit" style="float:right; margin: -1em 3em 0 0; padding-right:0">
							<?php $strRightArrow = ($numSelectedCategories > 0 &&  count($oAALOptions->arrOptions['units']) == 0) ? 'background: url(./img/rightarrow_attention.gif) no-repeat left;' : '';?>
							<div style="padding-left: 60px; <?php echo $strRightArrow; ?>">
								<input style="margin: 0 0 10px" type="submit" name="<?php echo AMAZONAUTOLINKSKEY; ?>[<?php echo $mode; ?>][save]" class="button-primary" <?php echo $numSelectedCategories > 0 ? '' : 'Disabled'; ?>  value="<?php echo $mode == 'newunit' ? __('Create', 'amazonautolinks') : __('Save', 'amazonautolinks'); ?>" /><br />
							</div>
							<?php $strRightArrow = ($strRssLink && $numSelectedCategories == 0 &&  count($oAALOptions->arrOptions['units']) == 0 ) ? 'background: url(./img/rightarrow_attention.gif) no-repeat left;' : '';?>
							<input type="hidden" name="<?php echo AMAZONAUTOLINKSKEY; ?>[<?php echo $mode; ?>][addcategoryname]" value="<?php echo $strBreadcrumb ;?>" />
							<input type="hidden" name="<?php echo AMAZONAUTOLINKSKEY; ?>[<?php echo $mode; ?>][addcategoryfeedurl]" value="<?php echo $strRssLink ;?>" />
							<input type="hidden" name="<?php echo AMAZONAUTOLINKSKEY; ?>[<?php echo $mode; ?>][addcategorypageurl]" value="<?php echo $url ;?>" />
							<div style="padding-left: 60px; <?php echo $strRightArrow; ?>">
								<input style="margin: 0 0 10px;" type="submit" name="<?php echo AMAZONAUTOLINKSKEY; ?>[<?php echo $mode; ?>][addcurrentcategory]" class="button-primary" <?php echo $strRssLink && (!array_key_exists(trim($strBreadcrumb), $oAALOptions->arrOptions[$mode]['categories'])) ? '' : 'Disabled' ?> value="<?php _e('Add Current Category', 'amazonautolinks'); ?>" /><br />
							</div>
							<div style="padding-left: 60px;"><!-- this is necessary for IE -->
								<input style="margin: 0 0 10px;" type="submit" name="<?php echo AMAZONAUTOLINKSKEY; ?>[<?php echo $mode; ?>][deletecategories]" class="button-primary" <?php echo $numSelectedCategories > 0 ? '' : 'Disabled'; ?> value="<?php _e('Delete Selected', 'amazonautolinks'); ?>" /><br />
							</div>
						</div>						
					</td>
				</tr>
				<tr>
					<td align="left" valign="top" width="20%" style="padding: 0 3em 0 1em; border-right:1px solid #CCC;"><?php if ($strRssLink) { echo $strBreadcrumb; } else { _e('None', 'amazonautolinks'); }?></h4></td>
					<td align="left" valign="top" width="40%" style="padding-left: 3em;">	
						<?php 
							if (count($oAALOptions->arrOptions[$mode]['categories'])) {								
								// list added categories with a check box form field
								foreach ( $oAALOptions->arrOptions[$mode]['categories'] as $catname => $catinfo ) { ?>
									<input type="checkbox" name="<?php echo AMAZONAUTOLINKSKEY; ?>[<?php echo $mode; ?>][categories][<?php echo $catname; ?>]" value="1">&nbsp;&nbsp;<a style="text-decoration:none" href="<?php echo $oAALfuncs->selfURLwithoutQuery() . '?href=' .  $oAALfuncs->urlencrypt($catinfo['pageurl']) . '&abspath=' . $oAALfuncs->urlencrypt($abspath) .'&mode=' . $_GET['mode']; ?>"><?php echo $catname; ?></a><br />
						<?php 	}
								if ($bReachedLimit) {
									echo '<div class="updated" style="padding:10px; margin:10px;">' . __('To add more categories, upgrade to the <a href="http://michaeluno.jp/en/amazon-auto-links/amazon-auto-links-pro" target="_blank">pro version</a>.', 'amazonautolinks') . '</div>';
								}
							} else 
								_e('No categories added.', 'amazonautolinks'); 
						?>
					</td>
				</tr>				
				<tr>
					<td align="left" valign="top" width="20%" style="padding-right: 3em; border-right:1px solid #CCC;"><h4><?php _e('Select Category', 'amazonautolinks'); ?></h4></td>
					<td align="left" valign="top" width="40%" style="padding-left: 3em; border-right:1px solid #CCC;"><h4><?php echo $strRssLink ? __('Preview of This Category', 'amazonautolinks') : __('No Preview', 'amazonautolinks'); ?></h4></td>
					<td align="left" valign="top" width="40%" style="padding: 0 3em 0 3em;"><h4><?php _e('Unit Preview', 'amazonautolinks'); ?></h4></td>
				</tr>
				<tr>
					<?php $strLeftDownArrow = (!$strRssLink && $numSelectedCategories == 0 &&  count($oAALOptions->arrOptions['units']) == 0) ? 'background: url(./img/leftdownarrow_attention.gif) no-repeat right top;' : '' ;?>
					<td align="left" valign="top" width="20%" style="padding-right: 3em; border-right:1px solid #CCC; <?php echo $strLeftDownArrow; ?>"><?php echo $oAALfuncs->RemoveLineFeeds($doc->saveXML($domleftCol));?></td>
					<td align="left" valign="top" width="40%" style="padding: 0 3em 0 3em; border-right:1px solid #CCC;">
						<div class="widthfixer" style="width:<?php echo $numImageWidth; ?>px;  border-bottom:1px solid #FFF;"></div>	
						<?php if ($strRssLink) { flush(); echo $oAALCatPreview->fetch(array($strRssLink)); } else { _e('Please select a category from the list on the left.', 'amazonautolinks'); } ?>
					</td>
					<td align="left" valign="top" width="40%" style="padding: 0 3em 0 3em">
						<div class="widthfixer" style="width:<?php echo $numImageWidth; ?>px;  border-bottom:1px solid #FFF;"></div>	
						<?php if (count($arrLinks) > 0) { flush(); echo $oAALUnitPreview->fetch($arrLinks); } else { _e('Please add a category from the list after selecting it.', 'amazonautolinks');}?>
					</td>
				</tr>
			</tbody>
		</table>
	</form>
	</body>
	</html>
<?php
	flush();
	// schedule pre-fetch sub-category links
	$oAALCatCache->schedule_prefetch($url);
	
?>	