<?php
/**
 * @package     Amazon Auto Links
 * @copyright   Copyright (c) 2013, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.0.0
 * @description	Renders the administration pages of the plugin.
*/
class AmazonAutoLinks_Admin_ {
		
	// Properties
	public $classver = 'standard';
	protected $pluginname = 'Amazon Auto Links';
	protected $pluginkey = 'amazonautolinks';
    protected $pageslug = 'amazonautolinks';
    protected $textdomain = 'amazon-auto-links';	// this is not used for the Code Styling Plugin for localization
	protected $oOption = array();
	protected $oAALfuncs = '';	// new AmazonAutoLinks_Helper_Functions;
	protected $oAALforms = '';	// new AmazonAutoLinks_Forms;
	protected $tabcaptions = array();
			
	/*-------------------------------------------------- Initial Settings -----------------------------------------------------*/
	function __construct( &$oOption ) {
	
		// the option array
		$this->oOption = $oOption; // new AmazonAutoLinks_Options($this->pluginkey);
				
		// Include helper classes
		$this->oAALfuncs = new AmazonAutoLinks_Helper_Functions( $this->pluginkey );		
		$this->oUserAd = new AmazonAutoLinks_UserAds( $this->pluginkey, $this->oOption );		
			
		// Include AmazonAutoLinks_Forms class
		$this->oAALforms = new AmazonAutoLinks_Forms( $this->pluginkey, $oOption, $this->oUserAd );		
		$this->oAALforms_selectcategories = new AmazonAutoLinks_Forms_SelectCategories( $this->pluginkey, $oOption );
	
		// cache class
		$this->oAALCatCache = new AmazonAutoLinks_CategoryCache( $this->pluginkey, $oOption );
			
		// properties
		$this->wp_version = & $GLOBALS["wp_version"];
		
		// register hooks
		$this->RegisterHooks();
	}
	function RegisterHooks() {
		
		// since v1.1.3 
		// not sure anymore --> moved from the constructor to instantiate the option class in the very beginning of the plugin.
		
		// localize hook only for admin page (admin_init). if the entire page-load should be hooked, including regular pages, use 'init' instead
		add_action( 'admin_init', array( &$this, 'localize' ) );
		
		// embed Plugin Settings Link in the plugin listing page
		add_filter( "plugin_action_links_" . AMAZONAUTOLINKSPLUGINFILEBASENAME, array( &$this, 'embed_settings_link' ) );

		// embed custom links in the plugin listing page
		add_filter( 'plugin_row_meta', array( &$this, 'EmbedLinks' ), 10, 2 );
		
		// admin menu
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		
		// admin custom CSS
		add_action( 'admin_head', array( &$this, 'admin_custom_css' ) );
				
		// admin footer to add plugin version
		if ( isset( $_GET['page'] ) && $_GET['page'] == $this->pageslug )
			add_filter( 'update_footer', array( $this, 'AddPluginVersionInFooter' ), 11 );
			
		// since v1.2.1		
		$this->ExportUnits();	// Export Units	- this must be done before the header is sent since it handles file download.
	}
	function localize() {

		$loaded = load_plugin_textdomain( 'amazon-auto-links', false, dirname(dirname( plugin_basename( __FILE__ ) )) . '/lang/');
		return;
		// the below is for debugging 
		if ( ! $loaded ) {
			$msg = '
			<div class="error">
				<p>' . $this->pluginname . ': Could not locate the language file.</p>
			</div>';
			add_action( 'admin_notices', create_function( '', 'echo "' . addcslashes( $msg, '"' ) . '";' ) );
		}	
	}
	function embed_settings_link($arrLinks) {
		
		$settings_link = '<a href="options-general.php?page=' . $this->pageslug . '">' . __('Settings', 'amazon-auto-links') . '</a>'; 
		array_unshift($arrLinks, $settings_link); 
		return $arrLinks; 	
	}
	function EmbedLinks( $arrLinks, $strFile ) {
		if ( $strFile == AMAZONAUTOLINKSPLUGINFILEBASENAME ) {
			// add links to the $arrLinks array.
			$arrLinks[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=J4UJHETVAZX34">' . __('Donate', 'amazon-auto-links') . '</a>';
			$arrLinks[] = '<a href="http://en.michaeluno.jp/contact/custom-order/?lang=' . ( WPLANG ? WPLANG : 'en' ) . '">' . __('Order custom plugin', 'amazon-auto-links') . '</a>';
			$arrLinks[] = '<a href="http://en.michaeluno.jp/amazon-auto-links/amazon-auto-links-pro/?lang=' . ( WPLANG ? WPLANG : 'en' ) . '">' . __('Get Pro', 'amazon-auto-links') . '</a>';
			$arrLinks[] = '<a href="http://en.michaeluno.jp/amazon-auto-links/amazon-auto-links-feed-api/?lang=' . ( WPLANG ? WPLANG : 'en' ) . '">' . __('Get Addon', 'amazon-auto-links') . '</a>';
		}
		return $arrLinks;
	}  		
	function AddPluginVersionInFooter( $strText ) {	
		// since v1.2.5
		$strProInfo = isset( $this->oOption->arrPluginDataPro ) ? $this->oOption->arrPluginDataPro['Name'] . ' ' . $this->oOption->arrPluginDataPro['Version'] :'';
		return $strProInfo . ' ' . $this->oOption->arrPluginData['Name'] 
			. ' ' . $this->oOption->arrPluginData['Version'] . ' ' . $strText;
	}			

	/* ------------------------------------------ Admin Menu --------------------------------------------- */
	function admin_menu() {
		add_options_page(
			$this->pluginname,		// page title
			$this->classver == 'pro' ? $this->pluginname . ' Pro' : $this->pluginname,		// menu item name
			isset( $this->oOption->arrOptions['general']['capability'] ) ? $this->oOption->arrOptions['general']['capability'] : 'manage_options',	// capability
			$this->pageslug,		// pageslug
			array( $this, 'adminpage' )
		);
	}
	
	/* ------------------------------------------ Admin Page --------------------------------------------- */
	function admin_custom_css() {
		
		// for the plugin admin panel theming
		if ( !isset( $_GET['page'] ) || $_GET['page'] != AMAZONAUTOLINKSKEY ) return;
			
		// if the option page of this plugin is loaded
		if ( IsSet( $_POST[AMAZONAUTOLINKSKEY]['tab202']['proceedbutton'] ) || IsSet( $_POST[AMAZONAUTOLINKSKEY]['tab100']['proceedbutton']) ) {

			$numTab = isset($_POST[AMAZONAUTOLINKSKEY]['tab202']['proceedbutton']) ? 202 : 100;
			$numImageSize = $_POST[AMAZONAUTOLINKSKEY]['tab' . $numTab]['imagesize'];
			$numIframeWidth =  $numImageSize * 2 + 480;		// $strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][imagesize]'		
		
			if ( version_compare($this->wp_version, '3.1.9', "<" ) )  // if the WordPress version is below 3.2 
				$strIframeWidth = $numIframeWidth < 1180 ? 'width:100%;' : 'width:' . $numIframeWidth . 'px;';		// set the minimum width 
			else 				// if the WordPress version is above 3.2
				$strIframeWidth = $numIframeWidth < 1180 ? 'width:1180px;' : 'width:' . $numIframeWidth . 'px;';		// set the minimum width 

			echo '<style type="text/css">
				#wpcontent {
					height:100%;
					' . $strIframeWidth . '
				}
				#footer {
					' . $strIframeWidth . '
					color: #777;
					border-color: #DFDFDF;
				}    					
				</style>';				

		} else if ( isset( $_GET['tab'] ) && $_GET['tab'] == 400 ) 	// for the upgrading to pro tab; the table needs additional styles
			echo '<link rel="stylesheet" type="text/css" href="' . AMAZONAUTOLINKSPLUGINURL . '/css/amazonautolinks_tab400.css' . '">';
		
		echo '<link rel="stylesheet" type="text/css" href="' . AMAZONAUTOLINKSPLUGINURL . '/css/amazonautolinks_tab200.css'. '">';
			
		// for category selection page
		$numTab = isset( $_GET['tab'] ) ? $_GET['tab'] : '';
		$numTab = isset( $_POST['tab'] ) ? $_POST['tab'] : $numTab;
		if ( in_array( $numTab, array( 203, 101 ) ) ) {
			$cssurl_wpadmin = admin_url( '/css/wp-admin.css?ver=') . get_bloginfo( 'version' ); // get_bloginfo( 'version' ));
			$cssurl_colorsfresh = admin_url('/css/colors-fresh.css') . '?ver=' . get_bloginfo( 'version' );
			$cssurl_catselect = AMAZONAUTOLINKSPLUGINURL . '/css/amazonautolinks_catselect.css';					
			?>
			<link rel="stylesheet" href="<?php echo $cssurl_wpadmin; ?>" type="text/css" media="all" />
			<link rel="stylesheet" id="colors-css" href="<?php echo $cssurl_colorsfresh; ?>" type="text/css" media="all" />
			<link rel="stylesheet" href="<?php echo $cssurl_catselect; ?>" type="text/css" media="all" />	
			<?php
		}
	}
	function GetTabNumber() {
		/*
		 * Since v1.1.3
		 * Determins the loading page tab number
		 * */
// echo '<pre>' . AMAZONAUTOLINKSKEY . '</pre>';		 
// echo '<pre>' . print_r($_POST, True) . '</pre>';		 

		// check if the Create/Save button is pressed
		if ( IsSet( $_POST[AMAZONAUTOLINKSKEY]['newunit']['save'] ) || IsSet( $_POST[AMAZONAUTOLINKSKEY]['editunit']['save'] ) ) {

			if ( IsSet( $_POST[AMAZONAUTOLINKSKEY]['newunit']['save'] ) ) $mode = 'newunit';
			else if ( IsSet( $_POST[AMAZONAUTOLINKSKEY]['editunit']['save'] ) ) $mode = 'editunit';
			
			// insert the options with the key name of the unit label
			if ( empty( $this->oOption->arrOptions[$mode]['id'] ) ) $this->oOption->arrOptions[$mode]['id'] = uniqid();	// sets an id if there isn't --- the check is for backward-compatibility when widget is not supported; widget uses this identifier to declare the class
			$this->oOption->insert_unit($this->oOption->arrOptions[$mode]['id'], $mode); // parameter: id, newunit/editunit
			$this->oOption->UnsetOptionKey($mode);	// removes the temporary unit options
			
			echo '<div class="updated" style="padding: 10px;">'; 
			if ( $mode == 'newunit' ) _e('The unit was successfully created.', 'amazon-auto-links');
			if ( $mode == 'editunit' ) _e('The unit options are updated.', 'amazon-auto-links'); 
			echo '</div>';
			return 200;
		}	
		
		// if the "Proceed" button in the edit unit page is pressed,
		if ( IsSet( $_POST[AMAZONAUTOLINKSKEY]['tab202']['proceedbutton']) ) return 202;	// it still needs to go to admin_tab202() to check errors of submitted data

		if ( IsSet( $_POST[AMAZONAUTOLINKSKEY]['tab'] ) ) return $_POST[AMAZONAUTOLINKSKEY]['tab'];			// set in the category selection page. ( AmazonAutoLinks_Forms_SelectCategories::RenderFormCategorySelectionPreview() )

		$numTab = isset( $_GET['tab'] ) ? $_GET['tab'] : 100;			// retrieve the current tab from the url		
// echo '<pre>$numTab: ' . $numTab . '</pre>';
		return $numTab;
	}
	function adminpage() {

		// define the page name for each tab
		$this->define_tab_captions();		
		?>
		<!-- Start Rendering the Form -->
		<div class="wrap">
			<?php 
			$numCurrentTab = $this->page_header();	// this includes displaying the tabs on top and determins the current tab value.						
			?>						
		
			<table id="aal-admin-container" border="0" style="width:100%">
				<tbody>
					<tr>
						<td valign="top" style="border: 0px;">
						<?php
							$strMethodName = "admin_tab{$numCurrentTab}";	
							$this->$strMethodName();	// e.g. $this->admin_tab101(); 
							flush();
						?>
						</td>
						<td valign="top" style="border: 0px;">
						<?php
							$this->oUserAd->InitializeBannerFeed( 'http://feeds.feedburner.com/GANLinkBanner160x600Random40' );
							$this->oUserAd->ShowBannerAds( !isset( $_GET['tab'] ) || in_array( $_GET['tab'], array( 100, 400, '' ) ) ? 3 : 2 );				
							flush();
						?>
						</td>
					</tr>
				</tbody>
			</table>
			
				
			
		</div> <!-- end the admin page wrapper -->
		<?php 	
	} 	// admin_page() end
	/* ------------------------------------------ Tab 100 : Create Unit --------------------------------------------- */
	function IsReachedLimitNumUnits($num=3) {
		if ( count( $this->oOption->arrOptions['units'] ) >= $num ) return true;
		// else
		return false;	
	}
	function admin_tab100($numTabNum=100) {
	
		/* 
			Check if POST data is sent and determine which page the user is coming from 
			determine whether the user is just landing or caming back from the preview(proceeding) page
		*/
			
		// If the hidden form field value indicates that the user submited POST data into this page from the specified tag number.
		if ($this->IsPostSentFrom(100)) {

			// if the Proceed button is pressed, determine which tab to go next;
			// if invalid form data submitted -> repeat; else -> proceed 
			if (IsSet($_POST[$this->pluginkey]['tab100']['proceedbutton'])) {
				// check how many units exist
				if ($this->IsReachedLimitNumUnits()) {
					$strURLTab400 = $this->change_tabnum_in_url(400);
					echo '<div class="updated" style="padding:10px; margin:10px">' . __('To add more units, please consider upgrading to <a href="' . $strURLTab400 . '">Pro</a>.', 'amazon-auto-links') . '</div>';
				} else 
					$numTabNum = $this->admin_tab100_determine_next_page_to_go();
			}
		} else if ($this->IsPostSentFrom(101)) {
		
			/* Tab 101 - the proceed page for creating a new unit. It is the next page after the tab 1 page. */		
			// if the Go Back button is pressed. ('tab101_submitted' is sent together)
			if (IsSet($_POST[$this->pluginkey]['tab101']['gobackbutton'])) {
				$this->oOption->arrOptions['tab101']['cameback'] = true;	// this flag is used for pseudo session.
				$this->oOption->update();
				$numTabNum = 100;		
			}
		} else {
		
			// no post form data submitted, meaning the user just arrived at this page.
			$this->oOption->set_new_unit();	// sets the default unit options to the 'newunit' array and reset the 'cameback' and 'error' flags to false.
		}

		?>
		<form method="post" action="">	
			<?php
			$this->oAALforms->embednonce($this->pluginkey, 'nonce'); 
			$this->oAALforms->embedhiddenfield($this->pluginkey, $numTabNum); 
			if ($numTabNum == 100) {
				echo '<h3>' . __('Add New Unit', 'amazon-auto-links') . '</h3>';
				$this->oAALforms->form_setunit($numTabNum, $this->oOption->arrOptions['newunit'], $this->oOption->arrOptions['tab100']['errors']); 
				
				// schedule prefetch; the parameter is empty, which means prefetch the root pages.
				$this->oAALCatCache->schedule_prefetch();
				
			} else if ($numTabNum == 101) 
				$this->admin_tab101();
			?>
		</form>
		<?php
		

	}
	function admin_tab100_determine_next_page_to_go() {
	
		// initialize the flag value first. This flag is also used in the form fields to mark red attentions.
		$this->oOption->arrOptions['tab100']['errors'] = False;	
		
		// validate the sent form data 
		$arrSubmittedFormValues = $_POST[$this->pluginkey]['tab100'];	
		$this->oOption->arrOptions['tab100']['errors'] = $this->oAALforms->validate_unitoptions($arrSubmittedFormValues);
		
		// check if a validation error occured
		if ($this->oOption->arrOptions['tab100']['errors']) {
					
			// Show a warning Message
			echo '<div class="error settings-error"><p>' . __('Some form information needs to be corrected.', 'amazon-auto-links') . '</p></div>';
					
			// Update the option values as preview to refill the submitted values
			$arrSubmittedFormValues = $this->oAALforms->clean_unitoptions($arrSubmittedFormValues);	// trying to see if this may fix the <img> breaking issue
			$this->oOption->set_newunit($arrSubmittedFormValues);	// does update_option() 
			
			// set the flag to indicate that repeat the page again. Do not go into the next page.
			return 100;		// returns the tab numeber to go next.
			
		}

		// the submitted options are valid; overwrite the option values so that previous values will be gone.
		
		// needs to merge with the previous ones because if the user comes from the proceeding page and has some seleceted categories,
		// those category info should be preserved so that when the user proceeds the settings again, he/she will have the previously seleceted categories
			
		// if the user is returning from the proceeding page, restore the previous values
		if (!is_array($this->oOption->arrOptions['newunit'])) $this->oOption->arrOptions['newunit'] = array();
		// if ($this->oOption->arrOptions['tab101']['cameback'])	

		$this->oOption->arrOptions['newunit'] = array_merge($this->oOption->arrOptions['newunit'], $arrSubmittedFormValues);
	
		// $this->oOption->arrOptions['newunit'] = $_POST[$this->pluginkey]['tab100'];
		$this->oOption->arrOptions['newunit'] = $this->oAALforms->setup_unitoption($this->oOption->arrOptions['newunit']);
		
		// Update the option values as preview and proceed to the next
		$this->oOption->update();
	
		return 101;	// returns the tab number to go next.
							
	}
	/* ------------------------------------------ Tab 101 : Create Unit 2 --------------------------------------------- */
	function admin_tab101() {
		// $this->oAALforms_selectcategories->form_selectcategories_iframe(101, $this->oOption->arrOptions['newunit']);
		$this->admin_tab_selectcategories( 101 );
	}
	function admin_tab_selectcategories( $numTab ) {

		/* Since v1.1.3
		 * Select Category Page - reached after pressing the Proceed button
		 * similar to admin_tab203()
		 * */
	 
		// declare variables
		$bReachedLimit = false;

		// $this->oOption->arrOptions['newunit'] is used so no need these evaluations actually		
		// determine if it is a new unit or editing an existing unit.
		// $strMode = round( floor( $numTab / 100 ) * 100, -2 ) == 100 ? 'new' : 'edit';
		// $mode = round( floor( $numTab / 100 ) * 100, -2 ) == 100 ? 'newunit' : 'editunit'; // <-- review the variable naming
		$mode = ( $numTab == 203 ) ? 'editunit' : 'newunit'; // or 101 -> newunit

		// for the initial array components - $this->oOption->arrOptions[$mode] must be an array from the previous page (the caller page of the iframe)
		if ( !isset( $this->oOption->arrOptions[$mode]['categories'] ) || !is_array( $this->oOption->arrOptions[$mode]['categories'] ) ) $this->oOption->arrOptions[$mode]['categories'] = array();
	
		/* POST Data */
		// Verify nonce 
		if (IsSet($_POST[AMAZONAUTOLINKSKEY]['submitted']) && !wp_verify_nonce($_POST['nonce'], AMAZONAUTOLINKSKEY)) return;

			
		// check if the "Add Current Category" button is pressed
		if ( IsSet( $_POST[AMAZONAUTOLINKSKEY][$mode]['addcurrentcategory'] ) ) {
			$numSelectedCategories = $this->oOption->add_category(
				$mode,		// NewUnit or EditUnit
				$_POST[AMAZONAUTOLINKSKEY][$mode]['addcategoryname'],	//	$strCatName: the submitted category breadcrumb name
				array(	// $arrCatInfo
					'feedurl' => $_POST[AMAZONAUTOLINKSKEY][$mode]['addcategoryfeedurl'],
					'pageurl' => $_POST[AMAZONAUTOLINKSKEY][$mode]['addcategorypageurl']
				)
			);
			if ( $numSelectedCategories == -1 ) {
				$bReachedLimit = True;
				$numSelectedCategories = 3;
			}
		}
		// check if the "Exclude Current Category" button is pressed
		else if ( IsSet( $_POST[AMAZONAUTOLINKSKEY][$mode]['addcurrentcategory_to_blacklist'] ) ) {
			
			$numBlackListCategories = $this->oOption->add_blacklist_category(
				$mode,
				$_POST[AMAZONAUTOLINKSKEY][$mode]['addcategoryname'],	//	$strCatName: the submitted category breadcrumb name
				array(	// $arrCatInfo
					'feedurl' => $_POST[AMAZONAUTOLINKSKEY][$mode]['addcategoryfeedurl'],
					'pageurl' => $_POST[AMAZONAUTOLINKSKEY][$mode]['addcategorypageurl']
				)			
			);
			if ( $numBlackListCategories == -1 ) {
				$bReachedLimit = True;
				$numBlackListCategories = 3;
				// 	$numBlackListCategories is set and may be used for something but currently not be used by any.
			}	
			$numSelectedCategories = count( $this->oOption->arrOptions[$mode]['categories'] ); 
		}		
		// check if the "Delete Checked Categories" button is pressd
		else if ( IsSet( $_POST[AMAZONAUTOLINKSKEY][$mode]['deletecategories']) ) {
			if ( IsSet( $_POST[AMAZONAUTOLINKSKEY][$mode]['categories'] ) ) {
				$numSelectedCategories = $this->oOption->delete_categories(
					$mode,		// NewUnit or EditUnit
					$_POST[AMAZONAUTOLINKSKEY][$mode]['categories']	//	array holding the category names to delete
				);				
			}
			if ( IsSet( $_POST[AMAZONAUTOLINKSKEY][$mode]['blacklist_categories'] ) ) {
				$numSelectedCategories = $this->oOption->delete_blacklist_categories(
					$mode,		// NewUnit or EditUnit
					$_POST[AMAZONAUTOLINKSKEY][$mode]['blacklist_categories']	//	array holding the category names to delete
				);		
			}
		}

		// new landing, just count the number of categories
		else $numSelectedCategories = count( $this->oOption->arrOptions[$mode]['categories'] ); 
	
		// make sure that if no categories are selected, exclude categories is removed or initialized.
		if ( $numSelectedCategories == 0 ) {
			$this->oOption->arrOptions[$mode]['blacklist_categories'] = array();
			$this->oOption->update();
		} 
	
		// insert the IsPreview flag so that it won't trigger background cache renewal events.
		$this->oOption->arrOptions[$mode]['IsPreview'] = True;	// this won't be saved unless update_option() is used after this line, so the actual unit option won't have this value
			
		/*
		 * Sidebar
		 * */
		
		// Determine the url to fetch
		// first check the $_GET array	
		$strURL = isset($_GET['href']) ? $this->oAALfuncs->urldecrypt($_GET['href']) : $this->oOption->arrOptions[$mode]['countryurl'];				
		
		// adds trailing slash; this is tricky, the uk and ca sites have an issue that they display a not-found page when a trailing slash is missing.
		// e.g. http://www.amazon.ca/Bestsellers-generic/zgbs won't open but http://www.amazon.ca/Bestsellers-generic/zgbs/ does.
		// Note that this problem has started occuring after using wp_remote_get(). So it has something to do with the function. 
		$strURL = preg_replace("/[^\/]$/i", "$0/", $strURL);		// added since v1.0.4

		// create a dom document object			
		if ( ! $doc = $this->oAALforms_selectcategories->load_dom_from_url( $strURL ) ) exit('<div class="error" style="padding:10px; margin:10px;">' . __('Could not load categories. Please consult the plugin developer.', 'amazon-auto-links') . '</div>');
	
		// Edit the href attribute to add the query.	
		// adds a query in the link urls like, ?href=[encrypted_url], so that in a next page load, $_GET['href'] tells where to look up
		$arrGETQuery = array(
			'mode' => $mode,			// newunit or editunit
		  'page' => $_GET['page'],	// &page=amazonautolinks
		  'tab' => $numTab,			// 101 or 203
		);
		
		if ( ! $bModifiedHref = $this->oAALforms_selectcategories->modify_href( $doc, $arrGETQuery ) ) {

			// if the category block could not be read, try renewing the cache 
			$this->oAALCatCache->renew_category_cache( $strURL );
			echo '<!-- ' . __('Warning: renewing category cache.', 'amazon-auto-links') . ' : ' . $strURL . ' -->' . PHP_EOL;
			$doc = $this->oAALforms_selectcategories->load_dom_from_url( $strURL );	
			
			 
			if ( ! $bModifiedHref = $this->oAALforms_selectcategories->modify_href( $doc, $arrGETQuery ) ) {
			
				// try with a R18 confirmation redirect - must use file_get_contents(), not wp_remote()
				$strBlackCurtainURL = $this->oAALCatCache->arrCountryBlackCurtainURLs[$this->oOption->arrOptions[$mode]['country']];
				$strRedirectURL = $strBlackCurtainURL . '?redirect=true&redirectUrl=' . urlencode( $strURL );
				$doc = $this->oAALforms_selectcategories->load_dom_from_HTML( file_get_contents( $strRedirectURL ), $this->oAALforms_selectcategories->detect_lang( $strURL ) );
				// $strURL	= $strRedirectURL;
// print '<pre>' . print_r( $this->oOption->arrOptions[$mode], true ). '</pre>' ;
				
				if ( ! $bModifiedHref = $this->oAALforms_selectcategories->modify_href( $doc, $arrGETQuery ) ) {
					echo '<div class="error" style="padding:10px; margin:10px;">' . __('Error: Links could not be modified in this url. Please consult the plugin developer.', 'amazon-auto-links') . ' : ' . $strURL . '<br />' . $strRedirectURL . '</div>';
					echo htmlspecialchars( $doc->saveXML( $doc->getElementsByTagName('body')->item(0) ), ENT_COMPAT | ENT_HTML401, get_bloginfo( 'charset' ) );
					Exit;
				}
			}
		}	
	
		// get the sidebar code
		$strSidebarHTML = $this->oAALforms_selectcategories->GetCategoryListSidebar($doc);

		// Create Breadcrumb 
		$strBreadcrumb = $this->oAALforms_selectcategories->breadcrumb($doc, $this->oOption->arrOptions[$mode]['country']);

		// extract the rss for the category
		$strRssLink = $this->oAALforms_selectcategories->get_rss_link($doc);	// echo '<pre>RSS: ' . $strRssLink . '</pre>';

		// the unit preview & selection form
		$this->oAALforms_selectcategories->RenderFormCategorySelectionPreview(	
				$mode
			,	$numSelectedCategories
			,	$strRssLink
			,	$strBreadcrumb
			,	$strURL
			,	$this->IsReachedLimitAddedCats( $bReachedLimit )
			,	$strSidebarHTML
			,	$numTab 
		);
												
		// schedule pre-fetch sub-category links
		$this->oAALCatCache->schedule_prefetch($strURL);												
			
	} // end of tab101
	function IsReachedLimitAddedCats( $num ) {
		// for the Pro version
		return $num;		
	}
	/* ------------------------------------------ Tab 200 : Manage Units --------------------------------------------- */
	function admin_tab200($numTabNum=200) {
		global $table_prefix;
		/* POST Data : Delete Units */
		// verify nonce
		if (!$this->oAALforms->verifynonce_in_tab($numTabNum, $this->pluginkey, 'nonce')) return;	// do nothing	
		
		// Clear Cache
		if (isset($_POST[$this->pluginkey]['tab200']['tab200_submitted']) && isset($_POST[$this->pluginkey]['tab200']['clearcache'])) {
		
			// remove feed caches
			add_filter( 'wp_feed_cache_transient_lifetime', create_function( '$a', 'return 0;' ) );	
			global $wpdb;
			$wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ('_transient%_feed_%')" );
			
			// remove category caches
			$wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ('_transient%_aal_%')" );
			$wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ('_transient_timeout%_aal_%')" );
			
			// remove events
			delete_option('amazonautolinks_catcache_events');
			
			echo '<div class="updated"><p>' . __('Caches were cleared. Please make sure the browser caches were also cleared if the unit items are still shown.', 'amazon-auto-links') . '</p></div>';
		}
		
		// Delete Units
		if ( isset( $_POST[$this->pluginkey]['tab200']['tab200_submitted'] ) && isset( $_POST[$this->pluginkey]['tab200']['deleteselectedunits'] ) ) {

			// Delete units of the submitted unit keys
			if ( isset( $_POST[$this->pluginkey]['tab200']['delete'] ) && $this->oOption->delete_units( $_POST[$this->pluginkey]['tab200']['delete'] ) ) 
				echo '<div class="updated"><p>' . __( 'Deleted the selected units.', 'amazon-auto-links' ) . '</p></div>';
			
			// also clean broken units (remove unlabeled units)
			if ( $this->oOption->clean_unlabeled_units() ) 
				echo '<div class="error settings-error"><p>' . __( 'There was a broken unit and deleted.', 'amazon-auto-links' ) . '</p></div>';

		}
		
		// Import Units	
		if ( $strMsg = $this->ImportUnits() ) echo $strMsg;
		
		// Export Units - this is done in the RegisterHooks() method since it has to be done before the header is sent.
		
		?>
		<h3><?php echo $this->tabcaptions[2]; ?></h3>		
		
		<!-- Create New Unit Button -->
		<div align="right" style="clear: left; margin-bottom: 20px;" >
			<?php $this->oAALforms->form_submitbutton(100, 'editunit', __('Create New Unit', 'amazon-auto-links')); ?>
		</div>
		
		<!-- Unit Table -->
		<?php 
		// Once it occured that pressing the delete button redirected the page to the edit unit page. 
		// So speficy where to go after submitting the form.
		$strAction = '?page=' . $this->pageslug . '&tab=' . $numTabNum ;  
		?>
		<form method="post" action="<?php echo $strAction;?>" enctype="multipart/form-data" >
			<?php $this->admin_tab200_unittable(); ?>				
			<div style="float:right; margin-top:20px; clear:right;">
				<!-- Clear Unit Cache and Delete Selected buttons -->
				<?php 
					$this->oAALforms->form_submitbutton( 200, 'clearcache', __( 'Clear Unit Cache', 'amazon-auto-links' ), 'nonce', False ); // the last parameter specifies that the form tag is not included 
					$this->oAALforms->form_submitbutton( 200, 'deleteselectedunits', __( 'Delete Selected Units', 'amazon-auto-links' ), 'nonce', False ); // the last parameter specifies that the form tag is not included
				?>
			</div>
			<?php $this->oAALforms->form_import_export_buttons(); ?>
		</form>
		<?php
	}
	function ImportUnits() {
		// since v1.2.1
		return null;
		
		// define messages for the localization.
		__( 'The file is bigger than this PHP installation allows.', 'amazon-auto-links' );
		__( 'The file is bigger than this form allows.', 'amazon-auto-links' );
		__( 'Only part of the file was uploaded.', 'amazon-auto-links' );
		__( 'No file was uploaded.', 'amazon-auto-links' );
		__( 'Import Error: Wrong file type.', 'amazon-auto-links' );
		__( 'Import Error: Wrong file type.', 'amazon-auto-links' );
		__( 'Import Error: Wrong text format.', 'amazon-auto-links' );
		__( 'Options were imported.', 'amazon-auto-links' );		
		__( 'The following unit was inserted:', 'amazon-auto-links' );
		__( 'The following unit option is corrupted:', 'amazon-auto-links' );
		
	}
	function ExportUnits() {
		// since v1.2.1
	}
	function admin_tab200_unittable() {
	
		// in case unnamed unit is injected in a process of misoperations or whatever, delete it. 
		// This should not happen but it occured once while debugging.
		$this->oOption->delete_unnamed_key('units');	// does update_option()

		// check if the number of units is valid
		// there could be a case that the user downgrades the version from pro. So leave them as they are.
		/* 		
		if ($this->IsReachedLimitNumUnits(4)) {
			do {
				array_pop($this->oOption->arrOptions['units']);
			} While (count($stack) > 3);
			update_option($this->pluginkey, $this->oOption->arrOptions);
		} */

		?>
		<table class="wp-list-table widefat fixed posts amazon-auto-links-admin" cellspacing="0" style="clear:none; width:auto;">
			<thead><?php $this->manage_units_table_header();?></thead>
			<tfoot><?php $this->manage_units_table_header();?></tfoot>
			<tbody id="the-list">
				<?php 

				$numUnit = count($this->oOption->arrOptions['units']);
				foreach( $this->oOption->arrOptions['units'] as $strUnitID => $unit ) {
					if (!$strUnitID) continue;	// this happened somehow when debugging. It shouldn't happen though.
					echo '<tr>';
					for ($i=0; $i <= 11; $i++) {
						if ($i==0) 
							echo '<td align="center" class="check-column first-col" style="" >' . '<input type="checkbox" name="' . $this->pluginkey . '[tab200][delete][' . $strUnitID . ']" value="1" ></td>';
						else if ($i==1)
							echo '<td>' . $unit['unitlabel'] . '</td>';		//. ': ' . $unit['id']
						else if ($i==2)
							echo '<td>' . $unit['associateid'] . '</td>';
						else if ($i==3)
							echo '<td>' . $unit['imagesize'] . '</td>';
						else if ($i==4) {
							$strSortOrder =  ( $unit['sortorder'] == 'title' ) ? 'Title Ascending' : $unit['sortorder'];
							echo '<td>' . ucfirst( $strSortOrder ) . '</td>';
						}
						else if ($i==5) {
							echo '<td>';	
							if (is_array($unit['feedtypes'])) {
								echo '<ul>';
								ForEach($unit['feedtypes'] as $type => $check) {
									if ($check) 
										echo '<li>' . $this->readable_feedtypes($type) . '</li>';
								}
								echo '</ul>';
							}
							echo '</td>';	
						}
						else if ($i==6)
							echo '<td>' . $unit['numitems'] . '</td>';
						else if ($i==7) {
							echo '<td>';
							echo $unit['nosim'] ? __('On', 'amazon-auto-links') : __('Off', 'amazon-auto-links');
							echo '</td>';			
						}							
						else if ($i==8) {	// insertions
							echo '<td>';
							if (is_array($unit['insert'])) {
								echo '<ul>';
								ForEach( $unit['insert'] as $key => $value ) 
									if ($value)	echo '<li>'. $this->readable_insertplace( $key ) . '</li>';
								
								echo '</ul>';
							}
							echo '</td>';
						}						
						else if ($i==9) {
							echo '<td>' 
								. '<ul>'
								. '<li>' . '[amazonautolinks label="' . $unit['unitlabel'] . '"]' . '</li>' 
								. '<li>' . '&lt;?php AmazonAutoLinks("' . $unit['unitlabel'] . '"); ?&gt;' . '</li>'
								. '</ul>'
								. '</td>';
						}
						else if ($i==10) {
							echo '<td>';
							if (is_array($unit['categories'])) {
								echo '<ul>';
								ForEach($unit['categories'] as $catname => $catinfo) {
									echo '<li>' . $catname . '</li>';
								}
								echo '</ul>';
							}
							echo '</td>';
						}
						else if ($i==11) {
							$strUnitLabel = $unit['unitlabel'];
							$strCryptedUnitLabel = $this->oAALfuncs->urlencrypt( $strUnitLabel );
							$strOperationLinks =  $this->custom_a_tag( '<img class="icon" src="' . plugins_url( 'img/edit16x16.gif' , AMAZONAUTOLINKSPLUGINFILE ) . '" title="' . __('Edit', 'amazon-auto-links') . '" alt="' . __('Edit', 'amazon-auto-links') . '" style="" />'
													, 202
													, array( 'edit' => $strCryptedUnitLabel ) )
												. $this->custom_a_tag( '<img class="icon" src="' . plugins_url( 'img/view16x16.gif' , AMAZONAUTOLINKSPLUGINFILE ) . '" title="' . __('View', 'amazon-auto-links') . '" alt="&nbsp;|&nbsp;' . __('View', 'amazon-auto-links') . '" style="" />' 
													, 201
													, array( 'view' => $strCryptedUnitLabel ) );
							$strRSSLink = '<img class="icon" src="' . plugins_url( 'img/rss_inactive16x16.gif' , AMAZONAUTOLINKSPLUGINFILE ) . '" title="' . __('Get the Feed API extension!', 'amazon-auto-links') . '" alt="&nbsp;|&nbsp;' . __('Feed', 'amazon-auto-links') . '" />';
							echo '<td>'
								. $strOperationLinks
								. '<a href="http://en.michaeluno.jp/amazon-auto-links/amazon-auto-links-feed-api/?lang=' . ( WPLANG ? WPLANG : 'en' ) . '">'
								. apply_filters( 'aalhook_admin_operation_rss_link',  $strRSSLink, $strUnitLabel )		// since v1.1.8
								. '</a>'
								. '</td>';
						}
					}
					echo '</tr>';
				} ?>
			</tbody>
		</table>
		<?php
	}
	function readable_feedtypes($strFeedType) {

		// converts an option element of feedtype to a readable string
		// e.g. bestsellers -> Best Sellers
		switch ($strFeedType) {
			case "bestsellers":
				return ucwords(__("Best Sellers", 'amazon-auto-links'));
				break;
			case "hotnewreleases":
				return ucwords(__("Hot New Releases", 'amazon-auto-links'));
				break;
			case "moverandshakers":
				return ucwords(__("Mover & Shakers", 'amazon-auto-links'));
				break;
			case "toprated":
				return ucwords(__("Top Rated", 'amazon-auto-links'));
				break;
			case "mostwishedfor":
				return ucwords(__("Most Wished For", 'amazon-auto-links'));
				break;
			case "giftideas":
				return ucwords(__("Gift Ideas", 'amazon-auto-links'));
				break;				
		}
	}	
	function readable_insertplace($key) {
		switch ($key) {
			case "postabove_static":
				return ucwords(__("Above Post on Publish", 'amazon-auto-links'));
				break;
			case "postbelow_static":
				return ucwords(__("Below Post on Publish", 'amazon-auto-links'));
				break;		
			case "postabove":
				return ucwords(__("Above Post", 'amazon-auto-links'));
				break;
			case "postbelow":
				return ucwords(__("Below Post", 'amazon-auto-links'));
				break;
			case "excerptabove":
				return ucwords(__("Above Excerpt", 'amazon-auto-links'));
				break;
			case "excerptbelow":
				return ucwords(__("Below Excerpt", 'amazon-auto-links'));
				break;
			case "feedabove":
				return ucwords(__("Above Feed Item", 'amazon-auto-links'));
				break;
			case "feedbelow":
				return ucwords(__("Below Feed Item", 'amazon-auto-links'));
				break;				
			case "feedexcerptabove":
				return ucwords(__("Above Feed Excerpt", 'amazon-auto-links'));
				break;				
			case "feedexcerptbelow":
				return ucwords(__("Below Feed Excerpt", 'amazon-auto-links'));
				break;								
		}	
	}
	function custom_a_tag($strText, $numTab, $arrQueries="", $strStyle="") {
	
		// creates a custom <a> tag with a modified href attribute. 
		// the href link url is converted to the self url with specified queries 
		$strQueries = '';
		if (Is_Array($arrQueries)) {
			foreach($arrQueries as $key => $value) {
				if (!empty($value))
					$strQueries .= '&' . $key . '=' . $value;
			}
		}
		return '<a href="' . $this->change_tabnum_in_url($numTab) . $strQueries . '" style="' . $strStyle . '">' . $strText . '</a>' ;
	}
	function change_tabnum_in_url($changeto) {
	
		// changes the current url's tab number
		return preg_replace('/(?<=tab=)\d+/i', $changeto, $this->oAALfuncs->selfURL() );		// '/tab\=\K\d+/i' can be used above PHP v5.2.4
	}
	
	/* ------------------------------------------ Tab 201 : Unit Preview --------------------------------------------- */
	function admin_tab201() {
		
		/* Preview Page from Manage Units */
		// this page is directed by url, meaning $_GET determins the tab number and this methods is called.
		// if the view element is not set, go to tab 200
		if (!IsSet($_GET['view'])) {
			$this->admin_tab200();
			return;	// do not continue 
		}	
		$strUnitLabel = $this->oAALfuncs->urldecrypt($_GET['view']);
		?>
		<h3><?php echo $this->tabcaptions[2]; ?></h3>
		<div style="float:right; margin-bottom: 20px;" >
			<?php $this->oAALforms->form_submitbutton(200, 'preview', __('Go Back', 'amazon-auto-links')); ?>
		</div>		
		<h4><?php _e('Preview', 'amazon-auto-links'); ?>: <?php echo $strUnitLabel; ?></h4>
		<div style="padding: 2em 3em 2em 3em;">
			<?php		
			$numMemoryUsageBefore = memory_get_peak_usage();
			$oAAL = new AmazonAutoLinks_Core( $strUnitLabel, $this->oOption );
			echo $oAAL->fetch();
			$numMemoryUsageAfter = memory_get_peak_usage();
			?>
			
			<div style="padding-top: 10px; margin-top:50px; color: #777 ;clear:both; border-top-width: 1px; border-top-style: solid; border-color: #DFDFDF;">
				<?php _e('Memory Usage by this unit: ', 'amazon-auto-links'); ?>
				<?php echo $this->oAALfuncs->FormatBytes($numMemoryUsageAfter - $numMemoryUsageBefore, 0); ?>
			</div>
		</div>
		<div style="float:right; margin-bottom: 20px;" >
			<?php $this->oAALforms->form_submitbutton(200, 'preview', __('Go Back', 'amazon-auto-links')); ?>
		</div>		
	<?php
	}
	
	/* ------------------------------------------ Tab 202 : Edit Units --------------------------------------------- */
	function admin_tab202($numTabNum=202) {

		// This page is for editing existing unit options. The components are similar to tab 100, creating a new unit.
		// this page is directed by the tab number in the url, in other words, the $_GET array
		// it does not bypass the method, admin_tab200()
	
		/* $_GET & $_POST */
		// if the edit query is not set, go to tab 200
		// if neither arrived by clicking the edit link nor by pressing the proceed button of the setting,
		if (!IsSet($_GET['edit']) && !IsSet($_POST[$this->pluginkey]['tab' . $numTabNum]['proceedbutton'])) {	
			$this->admin_tab200();			
			return;	// do not continue 
		}	
	
		// if the 'Proceed' button is pressed
		if ( IsSet( $_POST[$this->pluginkey]['tab202']['proceedbutton'] ) ) {
	
			// validate the submitted values
			$arrSubmittedFormValues = $_POST[$this->pluginkey]['tab202'];	
			$this->oOption->arrOptions['tab202']['errors'] = $this->oAALforms->validate_unitoptions($arrSubmittedFormValues, 'edit');
			if ($this->oOption->arrOptions['tab202']['errors']) {	// if it's invalid
				
				// Show a warning Message
				echo '<div class="error settings-error"><p>' . __('Some form information needs to be corrected.', 'amazon-auto-links') . '</p></div>';
						
				// Update the option values as preview to refill the submitted values
				// It has to merge with the previous options because they have predefined options which submitted ones don't have, such as categories
				$arrSubmittedFormValues = $this->oAALforms->clean_unitoptions($arrSubmittedFormValues);	// trying to see if this may fix the <img> breaking issue
				$this->oOption->arrOptions['editunit'] = array_merge($this->oOption->arrOptions['editunit'], $arrSubmittedFormValues);
				$this->oOption->update();
				
				// do it again, redirect to this page 
				$numTabNum = 202;	
				
			} else {	// if the submitted option values are valid

				// okey, save options and go to the next page, category selection.
				// It has to merge with the previous options because they have predefined options which submitted ones don't have, such as categories			 
				$this->oOption->arrOptions['editunit'] = $this->oAALforms->setup_unitoption(array_merge($this->oOption->arrOptions['editunit'], $arrSubmittedFormValues));
				$this->oOption->update();

				// go to the next page, which is the page to select categories
				$numTabNum = 203;						
			}					
			
		}
		// if the save button is pressed
		else if (IsSet($_POST[$this->pluginkey]['tab202']['savebutton'])) {
									
			$arrSubmittedFormValues = $_POST[$this->pluginkey]['tab202'];
	
			// validate the sumitted values and if it's okey, save the options to the database and go to Tab 200.
			$this->oOption->arrOptions['tab202']['errors'] = $this->oAALforms->validate_unitoptions($arrSubmittedFormValues, 'edit');
			if ($this->oOption->arrOptions['tab202']['errors']) {	// if it's invalid
			
				// Show a warning Message
				echo '<div class="error settings-error"><p>' . __('Some form information needs to be corrected.', 'amazon-auto-links') . '</p></div>';
						
				// Update the option values as preview to refill the submitted values
				$arrSubmittedFormValues = $this->oAALforms->clean_unitoptions($arrSubmittedFormValues);	// trying to see if this may fix the <img> breaking issue
				$this->oOption->update_editunit($arrSubmittedFormValues);	// does update_option()
				
				// do it again
				$numTabNum = 202;	
			} else {
			
				// okey, all done. Save options and go back to Manage Unit
				$arrSubmittedFormValues = $this->oAALforms->setup_unitoption($arrSubmittedFormValues);
				$this->oOption->save_submitted_unitoption_edit($arrSubmittedFormValues);	// this method include update_option()	
				echo '<div class="updated"><p>' . __('Updated the options.', 'amazon-auto-links') . '</p></div>';
				$this->admin_tab200(200);
				return; // do not continue			
			}
		} else {
							
			// no button is pressed, meaning new landing
			$strUnitLabel = $this->oAALfuncs->urldecrypt($_GET['edit']);	// note that the unit label is passed , not ID
			
			// this stores the temporary unit option in 'editunit' option key; the user modifies it and it will be used to update the unit option
			// $this->oOption->arrOptions['editunit'] will be assigned the unit array.
			$this->oOption->store_temporary_editunit_option( $strUnitLabel );	// this method includes update_option()
			
			// schedule prefetch; the parameter is empty, which means prefetch the root pages.
			$this->oAALCatCache->schedule_prefetch();
		}		
		?>
		
		<!-- Go Back Button -->
		<?php if ($numTabNum == 202) { ?>
		<div style="float:right; margin: 20px;" ><?php $this->oAALforms->form_submitbutton(200, 'goback', 'Go Back'); ?></div>
		<?php } ?>
		
		<!-- Edit Unit Form  -->
		<form method="post" name="tab202" action="">	
			<?php
			$this->oAALforms->embednonce($this->pluginkey, 'nonce'); 
			$this->oAALforms->embedhiddenfield($this->pluginkey, $numTabNum); 
			if ($numTabNum == 202) {
				echo '<h3>' . __('Edit Unit', 'amazon-auto-links') . '</h3>';	
				$this->oAALforms->form_setunit( $numTabNum, 
					$this->oOption->arrOptions['editunit'], 
					isset( $this->oOption->arrOptions['tab202']['errors'] ) ? $this->oOption->arrOptions['tab202']['errors'] : array() 
				); 
			} else if ( $numTabNum == 203 )  {
				$this->admin_tab203( $numTabNum ); // got to the category selection page
			}
					
			?>
		</form>
		<?php	
		
		// delete the unnecessry data
		$this->oOption->unset_error_flags(202);	// uses update_option()
		
	}
	function admin_tab203($numTab=203) {
		// similar to admin_tab101		
		$this->admin_tab_selectcategories($numTab);
	
	}
	/* ------------------------------------------ Tab 300: General Settings --------------------------------------------- */
	function admin_tab300($numTabNum=300) {
		
		/* Check GET and POST arrays */
		$bResult = $this->IsPostSentFrom( $numTabNum );
		if ( is_null( $bResult ) ) {
			echo '<div class="error settings-error"><p>' . __( 'Nonce verification failed.', 'amazon-auto-links' ) . '</p></div>'; // passed validation
			return;	// do not continue 
		}
		if ( $bResult ) {		// means there are some data sent
			if ( $this->savesubmittion( 300, "savebutton", "general" ) )
				echo '<div class="updated"><p>' . __('Options were saved.', 'amazon-auto-links') . '</p></div>'; // passed validation
			else
				echo '<div class="error settings-error"><p>' . __('Some form information needs to be corrected.', 'amazon-auto-links') . '</p></div>'; // failed validation
		} 
		// else no data submitted, meaning the user just arrived at this page.

		?>
		<h3><?php echo $this->tabcaptions[3]; ?></h3>		
		<form method="post" action="">	
			<?php
			$this->oAALforms->embednonce( $this->pluginkey, 'nonce' ); 
			$this->oAALforms->embedhiddenfield( $this->pluginkey, $numTabNum ); 
			$this->oAALforms->form_generaloptions( $numTabNum, 
				isset( $this->oOption->arrOptions['general'] ) ? $this->oOption->arrOptions['general'] : array(), 
				isset( $this->oOption->arrOptions['tab300']['errors'] ) ? $this->oOption->arrOptions['tab300']['errors'] : array()
				); 
			?>
		</form>				
	<?php	
	}	// end of tab300 ---------------------------------------------------------------------
	
	/* ------------------------------------------ Tab 400: Introducing Pro version --------------------------------------------- */
	function buynowbutton( $strFloat='right', $strPadding='10px 5em 20px', $type=1, $strLink='http://en.michaeluno.jp/amazon-auto-links/amazon-auto-links-pro' ) {
		$strImgBuyNow = AMAZONAUTOLINKSPLUGINURL . '/img/' . ( ( $type == 1 ) ? 'buynowbutton.gif' : 'buynowbutton-blue.gif' );
	?>
		<div style="padding:<?php echo $strPadding; ?>;">
			<div style="float:<?php echo $strFloat; ?>;">
				<a href="<?php echo $strLink; ?>?lang=<?php echo ( WPLANG ? WPLANG : 'en' ); ?>" title="<?php _e('Get Now!', 'amazon-auto-links') ?>">
					<img src="<?php echo $strImgBuyNow; ?>" />
				</a>
			</div>
		</div>	
	<?php
	}
	function admin_tab400($numTab=400) {
		$strCheckMark = plugins_url( 'img/checkmark.gif', dirname( __FILE__ ) );
		$strDeclineMark = plugins_url( 'img/declinedmark.gif', dirname( __FILE__ ) );
	?>
		<h3><?php _e('Get Pro Now!', 'amazon-auto-links'); ?></h3>
		<p><?php _e('Please consider upgrading to the pro version if you like the plugin and want more useful features, which include the ability of item formatting, unlimited numbers of categories, units, and items, and more!', 'amazon-auto-links'); ?></p>
		<?php $this->buynowbutton(); ?>
		<h3><?php _e('Supported Features', 'amazon-auto-links'); ?></h3>
		<div align="center" style="margin-top:30px;">
			<table class="aal-table" cellspacing="0" cellpadding="10" width="600" align="center">
				<tbody>
					<tr>
						<th>&nbsp;</th>
						<th>
							<?php _e('Standard', 'amazon-auto-links'); ?>
						</th>
						<th>
							<?php _e('Pro', 'amazon-auto-links'); ?>
						</th>
					</tr>
					<tr>
						<td><?php _e('Image Size', 'amazon-auto-links'); ?></td>
						<td align="center"><img title="<?php _e('Available', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Available', 'amazon-auto-links'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
						<td align="center"><img title="<?php _e('Available', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Available', 'amazon-auto-links'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
					</tr>
					<tr>
						<td><?php _e('Black List', 'amazon-auto-links'); ?></td>
						<td align="center"><img title="<?php _e('Available', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Available', 'amazon-auto-links'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
						<td align="center"><img title="<?php _e('Available', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Available', 'amazon-auto-links'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
					</tr>
					<tr>
						<td><?php _e('Sort Order', 'amazon-auto-links'); ?></td>
						<td align="center"><img title="<?php _e('Available', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Available', 'amazon-auto-links'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
						<td align="center"><img title="<?php _e('Available', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Available', 'amazon-auto-links'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
					</tr>
					<tr>
						<td><?php _e('Direct Link Bonus', 'amazon-auto-links'); ?></td>
						<td align="center"><img title="<?php _e('Available', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Available', 'amazon-auto-links'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
						<td align="center"><img title="<?php _e('Available', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Available', 'amazon-auto-links'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
					</tr>
					<tr>
						<td><?php _e('Insert in Posts and Feeds', 'amazon-auto-links'); ?></td>
						<td align="center"><img title="<?php _e('Available', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Available', 'amazon-auto-links'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
						<td align="center"><img title="<?php _e('Available', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Available', 'amazon-auto-links'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
					</tr>
					<tr>
						<td><?php _e('Widget', 'amazon-auto-links'); ?></td>
						<td align="center"><img title="<?php _e('Available', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Available', 'amazon-auto-links'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
						<td align="center"><img title="<?php _e('Available', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Available', 'amazon-auto-links'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
					</tr>
					<tr>
						<td><?php _e('No Ads in Admin Panel', 'amazon-auto-links'); ?></td>
						<td align="center"><img title="<?php _e('Unavailable', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Unavailable', 'amazon-auto-links'); ?>" src="<?php  echo $strDeclineMark; ?>" width="32" height="32"> </td>
						<td align="center"><img title="<?php _e('Available', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Available', 'amazon-auto-links'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
					</tr>					
					<tr>
						<td><?php _e('HTML Formatting', 'amazon-auto-links'); ?></td>
						<td align="center"><img title="<?php _e('Unavailable', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Unavailable', 'amazon-auto-links'); ?>" src="<?php  echo $strDeclineMark; ?>" width="32" height="32"> </td>
						<td align="center"><img title="<?php _e('Available', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Available', 'amazon-auto-links'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
					</tr>
					<tr>
						<td><?php _e('Cache Expiration Time', 'amazon-auto-links'); ?></td>
						<td align="center"><img title="<?php _e('Unavailable', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Unavailable', 'amazon-auto-links'); ?>" src="<?php  echo $strDeclineMark; ?>" width="32" height="32"> </td>
						<td align="center"><img title="<?php _e('Available', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Available', 'amazon-auto-links'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
					</tr>
					<tr>
						<td><?php _e('Max Number of Items to Show', 'amazon-auto-links'); ?></td>
						<td align="center">10</td>
						<td align="center"><strong><?php _e('Unlimited', 'amazon-auto-links'); ?></strong></td>
					</tr>
					<tr>
						<td><?php _e('Max Number of Categories Per Unit', 'amazon-auto-links'); ?></td>
						<td align="center">3</td>
						<td align="center"><strong><?php _e('Unlimited', 'amazon-auto-links'); ?></strong></td>
					</tr>
					<tr>
						<td><?php _e('Max Number of Units', 'amazon-auto-links'); ?></td>
						<td align="center">3</td>
						<td align="center"><strong><?php _e('Unlimited', 'amazon-auto-links'); ?></strong></td>
					</tr>		
					<tr>
						<td><?php _e( 'Export and Import Units', 'amazon-auto-links' ); ?></td>
						<td align="center"><img title="<?php _e('Unavailable', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Unavailable', 'amazon-auto-links'); ?>" src="<?php  echo $strDeclineMark; ?>" width="32" height="32"> </td>
						<td align="center"><img title="<?php _e('Available', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Available', 'amazon-auto-links'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
					</tr>						
					<tr>
						<td><?php _e( 'Exclude Sub Categories', 'amazon-auto-links' ); ?></td>
						<td align="center"><img title="<?php _e('Unavailable', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Unavailable', 'amazon-auto-links'); ?>" src="<?php  echo $strDeclineMark; ?>" width="32" height="32"> </td>
						<td align="center"><img title="<?php _e('Available', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Available', 'amazon-auto-links'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
					</tr>					
					<tr>
						<td><?php _e( 'Multiple Columns', 'amazon-auto-links' ); ?></td>
						<td align="center"><img title="<?php _e('Unavailable', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Unavailable', 'amazon-auto-links'); ?>" src="<?php  echo $strDeclineMark; ?>" width="32" height="32"> </td>
						<td align="center"><img title="<?php _e('Available', 'amazon-auto-links'); ?>" border="0" alt="<?php _e('Available', 'amazon-auto-links'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
					</tr>						
				</tbody>
			</table>
		</div>
		<h4><?php _e('HTML Formating', 'amazon-auto-links'); ?></h4>
		<p><?php _e('This gives you more freedom to stylize how the items are displayed by formatting the html tags.', 'amazon-auto-links'); ?></p>
		<h4><?php _e('Cacue Expiration Time', 'amazon-auto-links'); ?></h4>
		<p><?php _e('You can set more flexible timeout for the cached files.', 'amazon-auto-links'); ?></p>		
		<h4><?php _e('Max Number of Items to Show', 'amazon-auto-links'); ?></h4>
		<p><?php _e('Get pro for unlimited items to show.', 'amazon-auto-links'); ?></p>		
		<h4><?php _e('Max Number of Categories Per Unit', 'amazon-auto-links'); ?></h4>
		<p><?php _e('Get pro for unlimited categories to set up!', 'amazon-auto-links'); ?></p>		
		<h4><?php _e('Max Number of Units', 'amazon-auto-links'); ?></h4>
		<p><?php _e('Get pro for unlimited units so that you can put ads as many as you want.', 'amazon-auto-links'); ?></p>		
		
		<?php $this->buynowbutton('right', '20px 5em 20px'); ?>
		
		<h3><?php _e( 'Get Feed API Extension!', 'amazon-auto-links'); ?></h3>
		<?php $strCheckMark = AMAZONAUTOLINKSPLUGINURL . '/img/amazon-auto-links-feed-api-banner-772x250.jpg'; ?>
		<div style="text-align:center"><img title="Amazon Auto Links Feed API" src="<?php echo $strCheckMark;?>" width="500px" /></div>
		<p><?php _e( 'Create a back-end Amazon Associates ad server by enabling unit feeds so that you can import them into your other sites. If you are a developer, implement ads in your applications easily.', 'amazon-auto-links'); ?></p>
		<?php $this->buynowbutton('right', '20px 5em 20px', 2, 'http://en.michaeluno.jp/amazon-auto-links/amazon-auto-links-feed-api/'); ?>
	<?php
	}
	function admin_tab500($numTab=500) {
	?>
		<h3><?php _e('Translators' , 'amazon-auto-links'); ?></h3>
		<p><?php _e('Bilinguals or anyone fluent in a different language can submit a translation file and the pro version will be rewarded if the language file has not been translated.' , 'amazon-auto-links'); ?></p>
		<p><?php _e('To create a language file, with a plugin called, <a href="http://wordpress.org/extend/plugins/codestyling-localization/">Codestyling Localization</a>, no programming skill is required. You just edit the messages it displays. Then send the .mo file to <a href="mailto:miunosoft@michaeluno.jp">miunosoft@michaeluno.jp</a> via E-mail.' , 'amazon-auto-links'); ?></p>
		<h3><?php _e('Web Masters and Bloggers' , 'amazon-auto-links'); ?></h3>
		
		<p><?php _e('A web site owner with <a href="http://en.wikipedia.org/wiki/PageRank">Google PageRank</a> 3 or higher can recieve the pro version by writing an article about this plugin. It should have a link to the <a href="http://michaeluno.jp/en/amazon-auto-links/">product page</a> and some opinion or information about the plugin. It should be constructive. It could be about how to use it or how useful it is or how it could be improved or anything helpful to people and the developer. If the article is published, send the notification with the page url to <a href="mailto:miunosoft@michaeluno.jp">miunosoft@michaeluno.jp</a>.' , 'amazon-auto-links'); ?></p>

		<h3><?php _e('Bug Report' , 'amazon-auto-links'); ?></h3>
		<p><?php _e('If you find the plugin not working or having issues, please report it via the <a href="http://michaeluno.jp/en/bug-report">bug report form</a>.' , 'amazon-auto-links'); ?></p>
		
		<?php $this->donation_info(); ?>
		
		<h3><?php _e('Order a custom plugin or theme' , 'amazon-auto-links'); ?></h3>
		<p><?php _e('The developer of this plugin, Michael Uno, may be available to write a custom plugin for you. Please ask! <a href="mailto:michaeluno@michaeluno.jp">michaeluno@michaeluno.jp</a>.' , 'amazon-auto-links'); ?></p>

	<?php
	}
	function admin_tab600( $numTab=600 ) {
		// for Log page.
		// since v1.2.2
// not finished yet
// $this->oOption->oLog->Append( 'this is a test!' );
// $arrOldLogs = ( array ) get_option( 'amazonautolinks_logs' );
// echo 'Direct Access To the Option DB: <pre>' . print_r( $arrOldLogs, true ) . '</pre>';
// echo 'oLog: <pre>' . print_r( $this->oOption->oLog->arrLogs, true ) . '</pre>';
// echo '<pre>' . print_r( $_POST, true ) . '</pre>';
		if ( !$this->oAALforms->verifynonce_in_tab( $numTab ) ) return;

		if ( isset( $_POST['amazonautolinks']['tab600']['clear_debuglog'] ) )
			$this->oOption->ClearDebugLog();
		
		?>
		<form method="post" action="">	
			<?php
			$this->oAALforms->embednonce( $this->pluginkey, 'nonce' ); 
			$this->oAALforms->embedhiddenfield( $this->pluginkey, $numTab ); 
			echo '<div style="float:right; margin: 20px 0 10px 20px;">';
			$this->oAALforms->form_submitbutton( 600, 'clear_debuglog', __( 'Clear Log', 'amazon-auto-links' ), 'nonce', False, True, 'button-secondary' );
			echo '</div>';
			?>
		</form>			
		<?php
		echo '<h3>';
		echo isset( $this->tabcaptions[6] ) ? $this->tabcaptions[6] : __( 'Debug Log', 'amazon-auto-links' );
		echo '</h3>';
		/* Available Keys
			[message] => ''
			[time] => 2013-02-12 07:52:12 AM
			[color]
			[file] => ***.php
			[line] => 6
			[function] => Info
			[class] => Debug
			[type] =>	
		*/
		$i = 0;
		foreach ( $this->oOption->GetDebugLogs() as $id => $arrLog ) {
		
			if ( empty( $arrLog ) ) continue;
			++$i;
			$arrLog['file'] = basename( $arrLog['file'] );
			echo "<div>"
				."<div style='margin-right: 10px; width: 20px; text-align:right; display:inline; float:left;'>{$i}.</div>" 
				."<div style='margin: 0 10px 0 0; text-align:left; display:inline; float:left;'><strong>{$arrLog['time']}:</strong></div>"
				."<div style='float:left;'>Line:</div><div style='display:inline; width: 30px; text-align:right; margin-right: 10px; float:left;'>{$arrLog['line']}</div>"
				."{$arrLog['file']} {$arrLog['function']} "
				."{$arrLog['message']}</div>";
				
			if ( $i > 300 ) break;
		}
	}
	function donation_info() {
	
		// since v1.0.7
		$donate_link = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=J4UJHETVAZX34">' . __('Paypal', 'amazon-auto-links') . '</a>';
		?>
		<h3><?php _e('Donation' , 'amazon-auto-links'); ?></h3>
		<p><?php _e('Donations are always appreciated. If you like to donate to the developer, please do so via ' . $donate_link , 'amazon-auto-links'); ?>.</p>
		<?php
	
	}
	/* Methods for $this->admin_page() */
	function define_tab_captions() {
	
		// called from $this->admin_page()
		$this->tabcaptions = array(					// must set the key number so that the other extended class can remove an element without affecting other elements 
			1 => __( 'New Unit', 'amazon-auto-links' ),				// 1, for tab 100 - 199
			2 => __( 'Manage Units', 'amazon-auto-links' ),			// 2, for tab 200 - 299
			3 => __( 'General Settings', 'amazon-auto-links' ),		// 3, for tab 300 - 399
			4 => __( 'Upgrade to Pro', 'amazon-auto-links' ),		// 4, for tab 400 - 499
			5 => __( 'Information', 'amazon-auto-links' ),			// 5, for tab 500 - 599
		);

		// since v1.2.2
		// the options are updated after rendering the heading tabs, so it needs to check the submitted value before that.
		if ( isset( $this->oOption->arrOptions['general']['enablelog'] ) && !empty(	$this->oOption->arrOptions['general']['enablelog'] ) )
			$this->tabcaptions[6] = __( 'Debug Log', 'amazon-auto-links' );
		if ( isset( $_POST[$this->pluginkey]['tab300']['savebutton'] ) ) {
			if ( empty( $_POST[$this->pluginkey]['tab300']['enablelog'] ) )	
				unset( $this->tabcaptions[6] );
			else 
				$this->tabcaptions[6] = __( 'Debug Log', 'amazon-auto-links' );
		}
	}
	function page_header() {
	
		// called from $this->admin_page()
				
		// icon
		$this->screen_icon();

		// user ad
		echo '<div>';	// style fixer for v3.5 or above
		$this->oUserAd->InitializeTopBannerFeed( 'http://feeds.feedburner.com/GANBanner60x468' );
		$this->oUserAd->ShowTopBannerAds();
		echo '</div>'; // style fixer for v3.5 or above
		flush();
		
		// page header title
		$strClassVer = $this->classver == 'pro' ? ' Pro' : '';
		echo '<h2 style="height:1.8em; margin-bottom:16px;">' . $this->pluginname . $strClassVer . '</h2>';
				
		// tab menu
		$this->tab_menu( $numCurrentTab = $this->GetTabNumber() );	
		
		// text
		$this->oUserAd->InitializeTextFeed( 'http://feeds.feedburner.com/GANLinkTextRandom40' );
		$this->oUserAd->ShowTextAd();
		flush();
		
		return $numCurrentTab;
	}
	function screen_icon() {
	
		// called from $this->admin_page() but can be independently used
		echo '<div class="icon32" style="background-image: url(' . plugins_url( 'img/logo_AmazonAutoLinks36.gif' , dirname(__FILE__ )) . ');"><br /></div>';
	}	
	function tab_menu( $numCurrentTab ) {
	
		// called from $this->admin_page(), dependant on admin_page() 
		// This method should be called from the administration page. It displays the tabbed menu placed on top of the page.
		
		$numFlooredTabNum = round( floor( $numCurrentTab / 100 ) * 100, -2 );		// this converts tab numbers to the floored rounded number. e.g. 399 becomes 300 
		$numTabs = count( $this->tabcaptions );		// stores how many tabs are available
		echo '<h2 class="nav-tab-wrapper">';
		foreach( $this->tabcaptions as $numTab => $strTabCaption ) {
			if ($numTab == 0) continue;
			$strActive = ( $numFlooredTabNum == $numTab * 100) ? 'nav-tab-active' : '';		// checks if the current tab number matches the iteration number. If not matchi, then assign blank; otherwise put the active class name.
			echo '<a class="nav-tab ' . $strActive . '" href="?page=' . $this->pageslug . '&tab=' . $numTab * 100 . '">' . $strTabCaption . '</a>';
		}
		echo '</h2>';	
	}	// end of tab menu

	
	/* Methods for $this->admin_tab200() */ 
	function manage_units_table_header() {
		// called from and dependant on admin_tab200()
		// this is used for a table header and footer
	?>
		<tr style="">
			<th scope="col" id="cb" class="manage-column column-cb check-column" style="vertical-align:middle; padding-left:4px;" valign="middle">
				<input type="checkbox">
			</th>
			<th scope="col" id="unitlabel" class="manage-column column-label asc desc sortable" style="">
				<span><?php _e('Unit Label', 'amazon-auto-links'); ?></span><span class="sorting-indicator"></span>
			</th>
			<th scope="col" id="associateid" class="manage-column column-label asc sortable" style="">
				<span><?php _e('Associate ID', 'amazon-auto-links'); ?></span><span class="sorting-indicator"></span>
			</th>
			<th scope="col" id="imagesize" class="manage-column column-imagesize num " style="">
				<?php _e('Image Size', 'amazon-auto-links'); ?>
			</th>				
			<th scope="col" id="sortorder" class="manage-column column-sort" style="">
				<?php _e('Sort Order', 'amazon-auto-links'); ?>
			</th>
			<th scope="col" id="types" class="manage-column column-type" style="">
				<?php _e('Types', 'amazon-auto-links'); ?>
			</th>
			<th scope="col" id="numitems" class="manage-column column-comments num" style="">
				<?php _e('Items to Show', 'amazon-auto-links'); ?>
			</th>
			<th scope="col" id="refnosim" class="manage-column column-comments num " style="">
				<?php _e('Nosim', 'amazon-auto-links'); ?>
			</th>								
			<th scope="col" id="insertion" class="manage-column column-date" style="">
				<?php _e('Insertions', 'amazon-auto-links'); ?>
			</th>			
			<th scope="col" id="code" class="manage-column column-code" style="">
				<?php _e('Shortcode', 'amazon-auto-links'); ?> / <?php _e('PHP Code', 'amazon-auto-links'); ?>
			</th>
			<th scope="col" id="categories" class="manage-column column-category desc" style="">
				<?php _e('Categories', 'amazon-auto-links'); ?>
			</th>				
			<th scope="col" id="operation" class="manage-column column-comments num " style="padding-right:12px;">
				<?php _e('Operation', 'amazon-auto-links'); ?>
			</th>					
		</tr>	
	<?php
	}

	function IsPostSentFrom($tabnumber) {
	
		// checks if the form date is sent from the specified tab number 
		// in order to use this method, the formfield named [$this->pluginkey]['tabNNN']['tabNNN_submitted'] must be embedded in the form
		// where NNN is the tab number.
		if(isset($_POST[$this->pluginkey]['tab' . $tabnumber ]['tab' . $tabnumber . '_submitted']) && $_POST[$this->pluginkey]['tab' . $tabnumber ]['tab' . $tabnumber . '_submitted']) {
			// verify nonce
			if (!$this->oAALforms->verifynonce_in_tab($tabnumber, $this->pluginkey, 'nonce'))
				return null;	// do nothing.		
			else 
				return true;
		}
		else 
			return false;
	}	
	function savesubmittion( $numTab, $strButton, $strOption ) {
		
		// $numTab: indicates which page tab number to deal with
		// $strButton: indicates what form button is pressed
		// $strOption: indicates what option name to save in the array of '$options[$this->pluginkey][$strOption]'
		// returns false if the validation fails; returns true if the opsions are updated.
		
		// if the button is not pressed, go back
		if ( !IsSet($_POST[$this->pluginkey]['tab' . $numTab][$strButton] ) ) return;
			
		// validate the submitted data
		$arrSubmittedOptions = $_POST[$this->pluginkey]['tab' . $numTab];
		
		// currently only the general option page uses this method
		if ($strOption == 'general') {
			$arrSubmittedOptions = $this->oAALforms->clean_generaloptions($arrSubmittedOptions);
			$this->oOption->arrOptions[$numTab]['errors'] = $this->oAALforms->validate_generaloptions($arrSubmittedOptions);
			if ($this->oOption->arrOptions[$numTab]['errors'])
				return false;
		}
// print_r($arrSubmittedOptions);		
		// save the data
		$this->oOption->arrOptions[$strOption] = $arrSubmittedOptions;	//$_POST[$this->pluginkey]['tab' . $numTab];
		$this->oOption->update();
		return true;
	}
	function validate_options($numTab) {
	/*
		// creates an error array in $this->$options[$numTab]['errors']
		// currently only tab 300 uses this method and tab 300 options don't have fields to validate
		// so nothing to do so far.
		$arrOptionsToValidate = $_POST[$this->pluginkey]['tab' . $numTab];
	*/
	}
}
?>