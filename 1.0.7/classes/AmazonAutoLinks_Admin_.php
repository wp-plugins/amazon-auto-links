<?php
class AmazonAutoLinks_Admin_ {
	
	/*
		Todo: to seperate the option manipulation ability to the AmazonAutoLinks_Options class. 
			currently options are updated within this class.
	*/
	
	// Properties
	public $classver = 'standard';
	protected $pluginname = 'Amazon Auto Links';
	protected $pluginkey = 'amazonautolinks';
    protected $pageslug = 'amazonautolinks';
    protected $textdomain = 'amazonautolinks';
	// protected $options = array();
	protected $oAALOptions = array();
	protected $oAALfuncs = '';	// new AmazonAutoLinks_Helper_Functions;
	protected $oAALforms = '';	// new AmazonAutoLinks_Forms;
	protected $tabcaptions = array();
	
	// Flags
	// private $fComingBack = false;
	
	/*-------------------------------------------------- Initial Settings -----------------------------------------------------*/
	function __construct() {
	
		// Create Option Class
		$this->oAALOptions = new AmazonAutoLinks_Options($this->pluginkey);
		// retrieve options
		// $this->load_settings();
		// $this->oAALOptions->arrOptions = get_option( $this->pluginkey );
		
		// localize hook only for admin page (admin_init). if all page load should be hooked, use 'init' instead
		add_action('admin_init', array(&$this, 'localize'));
		
		// embed Plugin Settings Link in the plugin listing page
		add_filter("plugin_action_links_" . AMAZONAUTOLINKSPLUGINFILEBASENAME, array(&$this, 'embed_settings_link') );

		// embed donation link lin the plugin listing page
		add_filter('plugin_row_meta', array(&$this, 'embed_donate_link'), 10, 2);
		
		// admin menu
		add_action('admin_menu', array(&$this, 'admin_menu'));
		
		// admin custom CSS
		add_action('admin_head', array(&$this, 'admin_custom_css'));
		
		// Create Shortcode
		add_shortcode($this->pluginkey, array(&$this, 'shortcode'));
		
		// Hook post & RSS contents
		add_filter('the_content', array(&$this, 'insertinpost'));
		add_filter('the_excerpt', array(&$this, 'insertinexcerpt'));
		add_filter('the_content_feed', array(&$this, 'insertincontentfeed'));
		add_filter('the_excerpt_rss', array(&$this, 'insertinexcerptrss'));
		
		// Include helper classes
		$this->oAALfuncs = new AmazonAutoLinks_Helper_Functions($this->pluginkey);		
				
		// Include AmazonAutoLinks_Forms class
		$this->oAALforms = new AmazonAutoLinks_Forms($this->pluginkey);		
		$this->oAALforms_selectcategories = new AmazonAutoLinks_Forms_SelectCategories($this->pluginkey);
	
		// cache class
		$this->oAALCatCache = new AmazonAutoLinks_CategoryCache($this->pluginkey);
		
		// properties
		$this->wp_version = & $GLOBALS["wp_version"];
	}
	function localize() {
		// $loaded = load_plugin_textdomain( $this->textdomain, false, dirname(  __FILE__  ) . '/lang/');		// modified the last parameter <-- needs to examin if it works
		$loaded = load_plugin_textdomain( 'amazonautolinks', false, dirname(dirname( plugin_basename( __FILE__ ) )) . '/lang/');
		return;
		if ( ! $loaded ) {
			$msg = '
			<div class="error">
				<p>' . $this->pluginname . ': Could not locate the language file.</p>
			</div>';
			add_action( 'admin_notices', create_function( '', 'echo "' . addcslashes( $msg, '"' ) . '";' ) );
		}	
	}
	function embed_settings_link($arrLinks) {
		$settings_link = '<a href="options-general.php?page=' . $this->pageslug . '">' . __('Settings', 'amazonautolinks') . '</a>'; 
		array_unshift($arrLinks, $settings_link); 
		return $arrLinks; 	
	}
	function embed_donate_link($links, $file) {
		if ($file == AMAZONAUTOLINKSPLUGINFILEBASENAME) {
			$donate_link = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=J4UJHETVAZX34">' . __('Donate', 'amazonautolinks') . '</a>';
			$links[] = $donate_link;
		}
		return $links;
	}  		
	function shortcode($atts) {
	
		// reload the option since the timing of this function call depends and the options can have not be updated
		extract(shortcode_atts(array(
			'label' => '',
			// 'numitems' => 10,
		), $atts));
		$strUnitID = $this->oAALOptions->get_unitid_from_unitlabel($label, $arrOptions='');
		if (!$strUnitID) {
			echo $this->pluginname . ' ';
			_e('Error: No such unit label exists.', 'amazonautolinks');
			return;		
		}
		
		$oAAL = new AmazonAutoLinks_Core($this->oAALOptions->arrOptions['units'][$label]);
		return $oAAL->fetch();			
	}
	function insertinpost($content) {
		foreach($this->oAALOptions->arrOptions['units'] as $arrUnitOptions) {
			if ($arrUnitOptions['insert']['postabove']) {			
				$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $oAAL->fetch() . $content;
			}
			if ($arrUnitOptions['insert']['postbelow']) {
				$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $content . $oAAL->fetch();
			}
		}
		return trim($content);
	}
	function insertinexcerpt($content){
		foreach($this->oAALOptions->arrOptions['units'] as $arrUnitOptions) {
			if ($arrUnitOptions['insert']['excerptabove']) {
				$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $oAAL->fetch() . $content;
			}
			if ($arrUnitOptions['insert']['excerptbelow']) {
				$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $content . $oAAL->fetch();
			}
		}	
		return trim($content);
	}
	function insertincontentfeed($content) {
		foreach($this->oAALOptions->arrOptions['units'] as $arrUnitOptions) {
			if ($arrUnitOptions['insert']['feedabove']) {
				$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $oAAL->fetch() . $content;
			}
			if ($arrUnitOptions['insert']['feedbelow']) {
				$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $content . $oAAL->fetch();
			}
		}	
		return trim($content);
	}
	function insertinexcerptrss($content) {

		foreach($this->oAALOptions->arrOptions['units'] as $arrUnitOptions) {
			if ($arrUnitOptions['insert']['feedexcerptabove']) {
				$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $oAAL->fetch() . $content;
			}
			if ($arrUnitOptions['insert']['feedexcerptbelow']) {
				$oAAL = new AmazonAutoLinks_Core($arrUnitOptions);
				$content = $content . $oAAL->fetch();
			}
		}	
		return trim($content);
	}
	
	/* ------------------------------------------ Admin Menu --------------------------------------------- */
	function admin_menu() {
		add_options_page(
			$this->pluginname,		// page title
			$this->classver == 'pro' ? $this->pluginname . ' Pro' : $this->pluginname,		// menu item name
			'manage_options',		// privilege
			$this->pageslug,		// pageslug
			array($this, 'adminpage')
		);
	}
	
	/* ------------------------------------------ Admin Page --------------------------------------------- */
	function admin_custom_css() {
		
		// for the plugin admin panel theming
		if ($_GET['page'] != AMAZONAUTOLINKSKEY) return;
			
		// if the option page of this plugin is loaded
		if (IsSet($_POST[AMAZONAUTOLINKSKEY]['tab202']['proceedbutton']) || IsSet($_POST[AMAZONAUTOLINKSKEY]['tab100']['proceedbutton'])) {

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

		} else if ($_GET['tab'] == 400) 	// for the upgrading to pro tab; the table needs additional styles
			echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('/css/amazonautolinks_tab400.css', __FILE__). '">';
			
	}
	function adminpage() {

		// define the page name for each tab
		$this->define_tab_captions();		
		?>
		<!-- Start Rendering the Form -->
		<div class="wrap">
			<?php 
			$this->page_header();	// this includs displaying the tabs on top and determins the $this->current_tab value.						
			switch ($this->current_tab) {
				case 100: 	// create a new unit
					$this->admin_tab100();
					break;				
				case 200:	// manage units
					$this->admin_tab200();
					break;
				case 201;	// preview the unit
					$this->admin_tab201();
					break;
				case 202;	// edit the slected unit
					$this->admin_tab202();
					break;				
				case 300:	// general settings
					$this->admin_tab300();
					break;
				case 400:
					$this->admin_tab400();
					break;
				case 500:
					$this->admin_tab500();
					break;
			} // end switch for tabs		
			?>
		</div> <!-- end the admin page wrapper -->
		<?php 	
	} 	// admin_page() end
	/* ------------------------------------------ Tab 100 : Create Unit --------------------------------------------- */
	function IsReachedLimitNumUnits($num=3) {
		if (count($this->oAALOptions->arrOptions['units']) >= $num) return true;
		
		// else
		return false;	
	}
	function admin_tab100($numTabNum=100) {
	
		/* 
			Check if POST data is sent and determine which page the user is coming from 
			determine whether the user is just landing or caming back from the preview(proceeding) page
		*/
			
		// If the hidden form field value indicates that the user sumited POST data into this page from the specified tag number.
		if ($this->IsPostSentFrom(100)) {

			// if the Proceed button is pressed, determine which tab to go next;
			// if invalid form data submitted -> repeat; else -> proceed 
			if (IsSet($_POST[$this->pluginkey]['tab100']['proceedbutton'])) {
				// check how many units exist
				if ($this->IsReachedLimitNumUnits()) {
					$strURLTab400 = $this->change_tabnum_in_url(400);
					echo '<div class="updated" style="padding:10px; margin:10px">' . __('To add more units, please consider upgrading to <a href="' . $strURLTab400 . '">Pro</a>.', 'amazonautolinks') . '</div>';
				} else 
					$numTabNum = $this->admin_tab100_determine_next_page_to_go();
			}
		} else if ($this->IsPostSentFrom(101)) {
		
			/* Tab 101 - the proceed page for creating a new unit. It is the next page after the tab 1 page. */		
			// if the Go Back button is pressed. ('tab101_submitted' is sent together)
			if (IsSet($_POST[$this->pluginkey]['tab101']['gobackbutton'])) {
				$this->oAALOptions->arrOptions['tab101']['cameback'] = true;	// this flag is used for pseudo session.
				$this->oAALOptions->update();
				$numTabNum = 100;		
			}
		} else {
		
			// no post form data submitted, meaning the user just arrived at this page.
			$this->oAALOptions->set_new_unit();	// sets the default unit options to the 'newunit' array and reset the 'cameback' and 'error' flags to false.
		}

		?>
		<form method="post" action="">	
			<?php
			$this->oAALforms->embednonce($this->pluginkey, 'nonce'); 
			$this->oAALforms->embedhiddenfield($this->pluginkey, $numTabNum); 
			if ($numTabNum == 100) {
				echo '<h3>' . __('Add New Unit', 'amazonautolinks') . '</h3>';
				$this->oAALforms->form_setunit($numTabNum, $this->oAALOptions->arrOptions['newunit'], $this->oAALOptions->arrOptions['tab100']['errors']); 
				
				// schedule prefetch; the parameter is empty, which means prefetch the root pages.
				$this->oAALCatCache->schedule_prefetch();
			
// AmazonAutoLinks_CacheCategory();
				
			} else if ($numTabNum == 101) 
				$this->admin_tab101();
			?>
		</form>
		<?php
		

	}
	function admin_tab100_determine_next_page_to_go() {
	
		// initialize the flag value first. This flag is also used in the form fields to mark red attentions.
		$this->oAALOptions->arrOptions['tab100']['errors'] = False;	
		
		// validate the sent form data 
		$arrSubmittedFormValues = $_POST[$this->pluginkey]['tab100'];	
		$this->oAALOptions->arrOptions['tab100']['errors'] = $this->oAALforms->validate_unitoptions($arrSubmittedFormValues);
		
		// check if a validation error occured
		if ($this->oAALOptions->arrOptions['tab100']['errors']) {
					
			// Show a warning Message
			echo '<div class="error settings-error"><p>' . __('Some form information needs to be corrected.', 'amazonautolinks') . '</p></div>';
					
			// Update the option values as preview to refill the submitted values
			$arrSubmittedFormValues = $this->oAALforms->clean_unitoptions($arrSubmittedFormValues);	// trying to see if this may fix the <img> breaking issue
			$this->oAALOptions->set_newunit($arrSubmittedFormValues);	// does update_option() 
			
			// set the flag to indicate that repeat the page again. Do not go into the next page.
			return 100;		// returns the tab numeber to go next.
			
		}

		// the submitted options are valid; overwrite the option values so that previous values will be gone.
		
		// needs to merge with the previous ones because if the user comes from the proceeding page and has some seleceted categories,
		// those category info should be preserved so that when the user proceeds the settings again, he/she will have the previously seleceted categories
			
		// if the user is returning from the proceeding page, restore the previous values
		if (!is_array($this->oAALOptions->arrOptions['newunit'])) $this->oAALOptions->arrOptions['newunit'] = array();
		// if ($this->oAALOptions->arrOptions['tab101']['cameback'])	

		$this->oAALOptions->arrOptions['newunit'] = array_merge($this->oAALOptions->arrOptions['newunit'], $arrSubmittedFormValues);
	
		// $this->oAALOptions->arrOptions['newunit'] = $_POST[$this->pluginkey]['tab100'];
		$this->oAALOptions->arrOptions['newunit'] = $this->oAALforms->setup_unitoption($this->oAALOptions->arrOptions['newunit']);
		
		// Update the option values as preview and proceed to the next
		$this->oAALOptions->update();
	
		return 101;	// returns the tab number to go next.
							
	}
	/* ------------------------------------------ Tab 101 : Create Unit 2 --------------------------------------------- */
	function admin_tab101() {
		$this->oAALforms_selectcategories->form_selectcategories(101, $this->oAALOptions->arrOptions['newunit']);
	} // end of tab101

	/* ------------------------------------------ Tab 200 : Manage Units --------------------------------------------- */
	function admin_tab200($numTabNum=200) {
// print_r($this->oAALOptions->arrOptions['units']);
		/* POST Data : Delete Units */
		// verify nonce
		if (!$this->oAALforms->verifynonce_in_tab($numTabNum, $this->pluginkey, 'nonce')) return;	// do nothing	
		
		// Clear Cache
		if (isset($_POST[$this->pluginkey]['tab200']['tab200_submitted']) && isset($_POST[$this->pluginkey]['tab200']['clearcache'])) {
		
			// remove feed caches
			add_filter( 'wp_feed_cache_transient_lifetime', create_function( '$a', 'return 0;' ) );	
			global $wpdb;
			$wpdb->query( "DELETE FROM `wp_options` WHERE `option_name` LIKE ('_transient%_feed_%')" );
			
			// remove category caches
			$wpdb->query( "DELETE FROM `wp_options` WHERE `option_name` LIKE ('_transient%_aal_%')" );
			$wpdb->query( "DELETE FROM `wp_options` WHERE `option_name` LIKE ('_transient_timeout%_aal_%')" );
			
			// remove events
			delete_option('amazonautolinks_catcache_events');
			
			echo '<div class="updated"><p>' . __('Caches are cleared. Please make sure the browser cache is also cleared if the unit items are still shown.', 'amazonautolinks') . '</p></div>';
		}
		
		// Delete Units
		if (isset($_POST[$this->pluginkey]['tab200']['tab200_submitted']) && isset($_POST[$this->pluginkey]['tab200']['deleteselectedunits'])) {

			// Delete units of the submitted unit keys
			if ($this->oAALOptions->delete_units($_POST[$this->pluginkey]['tab200']['delete'])) echo '<div class="updated"><p>' . __('Deleted the selected units.', 'amazonautolinks') . '</p></div>';
			
			// also clean broken units (remove unlabeled units)
			if ($this->oAALOptions->clean_unlabeled_units()) echo '<div class="error settings-error"><p>' . __('There was a broken unit and deleted.', 'amazonautolinks') . '</p></div>';

		}
		?>
		<h3><?php echo $this->tabcaptions[2]; ?></h3>		
		
		<!-- Create New Unit Button -->
		<div style="float:right; margin-bottom: 20px;" >
			<?php $this->oAALforms->form_submitbutton(100, 'editunit', __('Create New Unit', 'amazonautolinks')); ?>
		</div>
		
		<!-- Unit Table -->
		<?php 
		// Once it occured that pressing the delete button reedirected the page to the edit unit page. 
		// So speficy where to go after submitting the form.
		$strAction = '?page=' . $this->pageslug . '&tab=' . $numTabNum ;  
		?>
		<form method="post" action="<?php echo $strAction;?>" >
			<?php $this->admin_tab200_unittable(); ?>		
			
			<div style="float:right; margin-top:20px;">
				<!-- Clear Unit Cache button -->		
				<?php $this->oAALforms->form_submitbutton(200, 'clearcache', __('Clear Unit Cache', 'amazonautolinks'), 'nonce', False); // the last parameter specifies that the form tag is not included ?>			
				<!-- Delete Selected Units button -->
				<?php $this->oAALforms->form_submitbutton(200, 'deleteselectedunits', __('Delete Selected Units', 'amazonautolinks'), 'nonce', False); // the last parameter specifies that the form tag is not included ?>
			</div>		
		</form>
		<?
	}	// end of tab200 --------------------------------------------------------------------
	function admin_tab200_unittable() {
		
		// in case unnamed unit is injected in a process of misoperations, delete it. 
		// This should not happen but it occured once while debugging.
		$this->oAALOptions->delete_unnamed_key('units');	// does update_option()

		// check if the number of units is valid
		// there could be a case that the user downgrades the version from pro. So leave them as they are.
		/* 		
		if ($this->IsReachedLimitNumUnits(4)) {
			do {
				array_pop($this->oAALOptions->arrOptions['units']);
			} While (count($stack) > 3);
			update_option($this->pluginkey, $this->oAALOptions->arrOptions);
		} */

		?>
		<table class="wp-list-table widefat fixed posts" cellspacing="0">
			<thead><?php $this->manage_units_table_header();?></thead>
			<tfoot><?php $this->manage_units_table_header();?></tfoot>
			<tbody id="the-list">
				<?php 

				$numUnit = count($this->oAALOptions->arrOptions['units']);
				foreach( $this->oAALOptions->arrOptions['units'] as $strUnitID => $unit ) {
					if (!$strUnitID) continue;	// this happened somehow when debugging. It shouldn't happen though.
					echo '<tr>';
					for ($i=0; $i <= 11; $i++) {
						if ($i==0) 
							echo '<td align="center" valign="middle" class="check-column">' . '<input type="checkbox" name="' . $this->pluginkey . '[tab200][delete][' . $strUnitID . ']" value="1" ></td>';
						else if ($i==1)
							echo '<td>' . $unit['unitlabel'] . '</td>';		//. ': ' . $unit['id']
						else if ($i==2)
							echo '<td>' . $unit['associateid'] . '</td>';
						else if ($i==3)
							echo '<td>' . $unit['imagesize'] . '</td>';
						else if ($i==4)
							echo '<td>' . ucfirst($unit['sortorder']) . '</td>';
						else if ($i==5) {
							echo '<td>';	
							if (is_array($unit['feedtypes'])) {
								ForEach($unit['feedtypes'] as $type => $check) {
									if ($check) 
										echo $this->readable_feedtypes($type) . '<br />';
								}
							}
							echo '</td>';	
						}
						else if ($i==6)
							echo '<td>' . $unit['numitems'] . '</td>';
						else if ($i==7) {
							echo '<td>';
							echo $unit['nosim'] ? __('On', 'amazonautolinks') : __('Off', 'amazonautolinks');
							echo '</td>';			
						}							
						else if ($i==8) {
							echo '<td>';
							if (is_array($unit['insert'])) {
								ForEach($unit['insert'] as $key => $value) {
									if ($value)
										echo $this->readable_insertplace($key) . '<br />';
								}
							}
							echo '</td>';
						}						
						else if ($i==9)
							echo '<td>' . '[amazonautolinks label="' . $unit['unitlabel'] . '"]<br />' 
							. '&lt;?php AmazonAutoLinks("' . $unit['unitlabel'] . '"); ?&gt;</td>';
						else if ($i==10) {
							echo '<td>';
							if (is_array($unit['categories'])) {
								ForEach($unit['categories'] as $catname => $catinfo) {
									echo $catname . '<br />';
								}
							}
							echo '</td>';
						}
						else if ($i==11)
							echo '<td>'
								. $this->custom_a_tag(__('Edit', 'amazonautolinks'), 202, array('edit' => $this->oAALfuncs->urlencrypt($unit['unitlabel'])))
								. ' | '
								. $this->custom_a_tag(__('View', 'amazonautolinks'), 201, array('view' => $this->oAALfuncs->urlencrypt($unit['unitlabel'])))
								. '</td>';
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
				return ucwords(__("Best Sellers", 'amazonautolinks'));
				break;
			case "hotnewreleases":
				return ucwords(__("Hot New Releases", 'amazonautolinks'));
				break;
			case "moverandshakers":
				return ucwords(__("Mover & Shakers", 'amazonautolinks'));
				break;
			case "toprated":
				return ucwords(__("Top Rated", 'amazonautolinks'));
				break;
			case "mostwishedfor":
				return ucwords(__("Most Wished For", 'amazonautolinks'));
				break;
			case "giftideas":
				return ucwords(__("Gift Ideas", 'amazonautolinks'));
				break;				
		}
	}	
	function readable_insertplace($key) {
		switch ($key) {
			case "postabove":
				return ucwords(__("Above Post", 'amazonautolinks'));
				break;
			case "postbelow":
				return ucwords(__("Below Post", 'amazonautolinks'));
				break;
			case "excerptabove":
				return ucwords(__("Above Excerpt", 'amazonautolinks'));
				break;
			case "excerptbelow":
				return ucwords(__("Below Excerpt", 'amazonautolinks'));
				break;
			case "feedabove":
				return ucwords(__("Above Feed Item", 'amazonautolinks'));
				break;
			case "feedbelow":
				return ucwords(__("Below Feed Item", 'amazonautolinks'));
				break;				
			case "feedexcerptabove":
				return ucwords(__("Above Feed Excerpt", 'amazonautolinks'));
				break;				
			case "feedexcerptbelow":
				return ucwords(__("Below Feed Excerpt", 'amazonautolinks'));
				break;								
		}	
	}
	function custom_a_tag($strText, $numTab, $arrQueries="", $strStyle="") {
	
		// creates ta custom <a> tag with a modified href attribute. 
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
			<?php $this->oAALforms->form_submitbutton(200, 'preview', __('Go Back', 'amazonautolinks')); ?>
		</div>		
		<h4><?php _e('Preview', 'amazonautolinks'); ?>: <?php echo $strUnitLabel; ?></h4>
		<div style="padding: 2em 3em 2em 3em;">
			<?php		
			$oAAL = new AmazonAutoLinks_Core($strUnitLabel);
			echo $oAAL->fetch();
			?>
		</div>
		<div style="float:right; margin-bottom: 20px;" >
			<?php $this->oAALforms->form_submitbutton(200, 'preview', __('Go Back', 'amazonautolinks')); ?>
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
		// if neither arrived by clicking the edit link nor by pressing the proceed button of the setting
		if (!IsSet($_GET['edit']) && !IsSet($_POST[$this->pluginkey]['tab' . $numTabNum]['proceedbutton'])) {	
			$this->admin_tab200();
			return;	// do not continue 
		}	

		// if the 'Proceed' button is pressed
		if (IsSet($_POST[$this->pluginkey]['tab202']['proceedbutton'])) {
		
			// validate the submitted values
			$arrSubmittedFormValues = $_POST[$this->pluginkey]['tab202'];
		
			$this->oAALOptions->arrOptions['tab202']['errors'] = $this->oAALforms->validate_unitoptions($arrSubmittedFormValues, 'edit');
			if ($this->oAALOptions->arrOptions['tab202']['errors']) {	// if it's invalid
				
				// Show a warning Message
				echo '<div class="error settings-error"><p>' . __('Some form information needs to be corrected.', 'amazonautolinks') . '</p></div>';
						
				// Update the option values as preview to refill the submitted values
				// It has to merge with the previous options because they have predefined options which submitted ones don't have, such as categories
				$arrSubmittedFormValues = $this->oAALforms->clean_unitoptions($arrSubmittedFormValues);	// trying to see if this may fix the <img> breaking issue
				$this->oAALOptions->arrOptions['editunit'] = array_merge($this->oAALOptions->arrOptions['editunit'], $arrSubmittedFormValues);
				$this->oAALOptions->update();
				
				// do it again, redirect to this page 
				$numTabNum = 202;	
				
			} else {	// if the submitted option values are valid

				// okey, save options and go to the next page, category selection.
				// It has to merge with the previous options because they have predefined options which submitted ones don't have, such as categories			 
				$this->oAALOptions->arrOptions['editunit'] = $this->oAALforms->setup_unitoption(array_merge($this->oAALOptions->arrOptions['editunit'], $arrSubmittedFormValues));
				$this->oAALOptions->update();

				// go to the next page, which is the page to select categories
				$numTabNum = 203;	
			}
		}
		// if the save button is pressed
		else if (IsSet($_POST[$this->pluginkey]['tab202']['savebutton'])) {
		
			$arrSubmittedFormValues = $_POST[$this->pluginkey]['tab202'];
	
			// validate the sumitted values and if it's okey, save the options to the database and go to Tab 200.
			$this->oAALOptions->arrOptions['tab202']['errors'] = $this->oAALforms->validate_unitoptions($arrSubmittedFormValues, 'edit');
			if ($this->oAALOptions->arrOptions['tab202']['errors']) {	// if it's invalid
			
				// Show a warning Message
				echo '<div class="error settings-error"><p>' . __('Some form information needs to be corrected.', 'amazonautolinks') . '</p></div>';
						
				// Update the option values as preview to refill the submitted values
				$arrSubmittedFormValues = $this->oAALforms->clean_unitoptions($arrSubmittedFormValues);	// trying to see if this may fix the <img> breaking issue
				$this->oAALOptions->update_editunit($arrSubmittedFormValues);	// does update_option()
				
				// do it again
				$numTabNum = 202;	
			} else {
			
				// okey, all done. Save options and go back to Manage Unit
				$arrSubmittedFormValues = $this->oAALforms->setup_unitoption($arrSubmittedFormValues);
				$this->oAALOptions->save_submitted_unitoption_edit($arrSubmittedFormValues);	// this method include update_option()	
				echo '<div class="updated"><p>' . __('Updated the options.', 'amazonautolinks') . '</p></div>';
				$this->admin_tab200(200);
				return; // do not continue			
			}
		} else {
		
			// no button is pressed, meaning new landing
			$strUnitLabel = $this->oAALfuncs->urldecrypt($_GET['edit']);	// note that the unit label is passed , not ID
			
			// this stores the temporary unit option in 'editunit' option key; the user modifies it and it will be used to update the unit option
			$this->oAALOptions->store_temporary_editunit_option($strUnitLabel);	// this method includes update_option()
			
			// schedule prefetch; the parameter is empty, which means prefetch the root pages.
			$this->oAALCatCache->schedule_prefetch();
		}
		?>
		
		<!-- Go Back Button -->
		<?php if ($numTabNum == 202) { ?>
		<div style="float:right; margin: 20px;" ><?php $this->oAALforms->form_submitbutton(200, 'goback', 'Go Back'); ?></div>
		<?php } ?>
		
		<!-- Edit Unit Form  -->
		<form method="post" action="">	
			<?php
			$this->oAALforms->embednonce($this->pluginkey, 'nonce'); 
			$this->oAALforms->embedhiddenfield($this->pluginkey, $numTabNum); 
			if ($numTabNum == 202) {
				echo '<h3>' . __('Edit Unit', 'amazonautolinks') . '</h3>';	
				$this->oAALforms->form_setunit($numTabNum, $this->oAALOptions->arrOptions['editunit'], $this->oAALOptions->arrOptions['tab202']['errors']); 
			} else if ($numTabNum == 203) 
				$this->admin_tab203($numTabNum); // got to the category selection page
			?>
		</form>
		<?php	
		
		// delete the unnecessry data
		$this->oAALOptions->unset_error_flags(202);	// uses update_option()
		
	}
	function admin_tab203($numTab=203) {
		$this->oAALforms_selectcategories->form_selectcategories($numTab, $this->oAALOptions->arrOptions['editunit']);
	}
	/* ------------------------------------------ Tab 300: General Settings --------------------------------------------- */
	function admin_tab300($numTabNum=300) {
		
		/* Check GET and POST arrays */
		$bResult = $this->IsPostSentFrom($numTabNum);
		if (is_null($bResult)) {
			echo '<div class="error settings-error"><p>' . __('Nonce verification failed.', 'amazonautolinks') . '</p></div>'; // passed validation
			return;	// do not continue 
		}
		if ($bResult) {		// means there are some data sent
			if ($this->savesubmittion(300, "savebutton", "general"))
				echo '<div class="updated"><p>' . __('Options are saved.', 'amazonautolinks') . '</p></div>'; // passed validation
			else
				echo '<div class="error settings-error"><p>' . __('Some form information needs to be corrected.', 'amazonautolinks') . '</p></div>'; // failed validation
		} 
		// else no data submitted, meaning the user just arrived at this page.
		
	?>
		<h3><?php echo $this->tabcaptions[3]; ?></h3>		
		<form method="post" action="">	
			<?php
			$this->oAALforms->embednonce($this->pluginkey, 'nonce'); 
			$this->oAALforms->embedhiddenfield($this->pluginkey, $numTabNum); 
			$this->oAALforms->form_generaloptions($numTabNum, $this->oAALOptions->arrOptions['general'], $this->oAALOptions->arrOptions['tab300']['errors']); 
			?>
		</form>				
	<?php	
	}	// end of tab300 ---------------------------------------------------------------------
	
	/* ------------------------------------------ Tab 400: Introducing Pro version --------------------------------------------- */
	function buynowbutton($strFloat='right', $strPadding='10px 5em 20px') {
		$strImgBuyNow = plugins_url( 'img/buynowbutton.gif', dirname(__FILE__ ));
	?>
		<div style="padding:<?php echo $strPadding; ?>;">
			<div style="float:<?php echo $strFloat; ?>;"><a href="http://michaeluno.jp/en/amazon-auto-links/amazon-auto-links-pro" title="<?php _e('Get Pro Now!', 'amazonautolinks') ?>"><img src="<?php echo $strImgBuyNow; ?>" /></a></div>
		</div>	
	<?php
	}
	function admin_tab400($numTab=400) {
		$strCheckMark = plugins_url( 'img/checkmark.gif', dirname(__FILE__ ));
		$strDeclineMark = plugins_url( 'img/declinedmark.gif', dirname(__FILE__ ));
	?>
		<h3><?php _e('Get Pro Now!', 'amazonautolinks'); ?></h3>
		<p><?php _e('Please consider upgrading to the pro version if you like the plugin and want more useful features, which include the ability of item formatting, unlimited numbers of categories, units, and items, and more!', 'amazonautolinks'); ?></p>
		<?php $this->buynowbutton(); ?>
		<h3><?php _e('Supported Features', 'amazonautolinks'); ?></h3>
		<div align="center" style="margin-top:30px;">
			<table class="aal-table" cellspacing="0" cellpadding="10" width="600" align="center">
				<tbody>
					<tr>
						<th>&nbsp;</th>
						<th>
							<?php _e('Standard', 'amazonautolinks'); ?>
						</th>
						<th>
							<?php _e('Pro', 'amazonautolinks'); ?>
						</th>
					</tr>
					<tr>
						<td><?php _e('Image Size', 'amazonautolinks'); ?></td>
						<td align="center"><img title="<?php _e('Available', 'amazonautolinks'); ?>" border="0" alt="<?php _e('Available', 'amazonautolinks'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
						<td align="center"><img title="<?php _e('Available', 'amazonautolinks'); ?>" border="0" alt="<?php _e('Available', 'amazonautolinks'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td></tr>
					<tr>
						<td><?php _e('Black List', 'amazonautolinks'); ?></td>
						<td align="center"><img title="<?php _e('Available', 'amazonautolinks'); ?>" border="0" alt="<?php _e('Available', 'amazonautolinks'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
						<td align="center"><img title="<?php _e('Available', 'amazonautolinks'); ?>" border="0" alt="<?php _e('Available', 'amazonautolinks'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td></tr>
					<tr>
						<td><?php _e('Sort Order', 'amazonautolinks'); ?></td>
						<td align="center"><img title="<?php _e('Available', 'amazonautolinks'); ?>" border="0" alt="<?php _e('Available', 'amazonautolinks'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
						<td align="center"><img title="<?php _e('Available', 'amazonautolinks'); ?>" border="0" alt="<?php _e('Available', 'amazonautolinks'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td></tr>
					<tr>
						<td><?php _e('Direct Link Bonus', 'amazonautolinks'); ?></td>
						<td align="center"><img title="<?php _e('Available', 'amazonautolinks'); ?>" border="0" alt="<?php _e('Available', 'amazonautolinks'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
						<td align="center"><img title="<?php _e('Available', 'amazonautolinks'); ?>" border="0" alt="<?php _e('Available', 'amazonautolinks'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td></tr>
					<tr>
						<td><?php _e('Insert in Posts and Feeds', 'amazonautolinks'); ?></td>
						<td align="center"><img title="<?php _e('Available', 'amazonautolinks'); ?>" border="0" alt="<?php _e('Available', 'amazonautolinks'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
						<td align="center"><img title="<?php _e('Available', 'amazonautolinks'); ?>" border="0" alt="<?php _e('Available', 'amazonautolinks'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td></tr>
					<tr>
						<td><?php _e('Widget', 'amazonautolinks'); ?></td>
						<td align="center"><img title="<?php _e('Available', 'amazonautolinks'); ?>" border="0" alt="<?php _e('Available', 'amazonautolinks'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td>
						<td align="center"><img title="<?php _e('Available', 'amazonautolinks'); ?>" border="0" alt="<?php _e('Available', 'amazonautolinks'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td></tr>
					<tr>
						<td><?php _e('HTML Formatting', 'amazonautolinks'); ?></td>
						<td align="center"><img title="<?php _e('Unavailable', 'amazonautolinks'); ?>" border="0" alt="<?php _e('Unavailable', 'amazonautolinks'); ?>" src="<?php  echo $strDeclineMark; ?>" width="32" height="32"> </td>
						<td align="center"><img title="<?php _e('Available', 'amazonautolinks'); ?>" border="0" alt="<?php _e('Available', 'amazonautolinks'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td></tr>
					<tr>
						<td><?php _e('Cache Expiration Time', 'amazonautolinks'); ?></td>
						<td align="center"><img title="<?php _e('Unavailable', 'amazonautolinks'); ?>" border="0" alt="<?php _e('Unavailable', 'amazonautolinks'); ?>" src="<?php  echo $strDeclineMark; ?>" width="32" height="32"> </td>
						<td align="center"><img title="<?php _e('Available', 'amazonautolinks'); ?>" border="0" alt="<?php _e('Available', 'amazonautolinks'); ?>" src="<?php  echo $strCheckMark; ?>" width="32" height="32"> </td></tr>
					<tr>
						<td><?php _e('Max Number of Items to Show', 'amazonautolinks'); ?></td>
						<td align="center">10</td>
						<td align="center"><strong><?php _e('Unlimited', 'amazonautolinks'); ?></strong></td></tr>
					<tr>
						<td><?php _e('Max Number of Categories Per Unit', 'amazonautolinks'); ?></td>
						<td align="center">3</td>
						<td align="center"><strong><?php _e('Unlimited', 'amazonautolinks'); ?></strong></td>
						</tr>
					<tr>
						<td><?php _e('Max Number of Units', 'amazonautolinks'); ?></td>
						<td align="center">3</td>
						<td align="center"><strong><?php _e('Unlimited', 'amazonautolinks'); ?></strong></td>
					</tr>		
				</tbody>
			</table>
		</div>
		<h4><?php _e('HTML Formating', 'amazonautolinks'); ?></h4>
		<p><?php _e('This gives you more freedom to stylize how the items are displayed by formatting the html tags.', 'amazonautolinks'); ?></p>
		<h4><?php _e('Cacue Expiration Time', 'amazonautolinks'); ?></h4>
		<p><?php _e('You can set more flexible timeout for the cached files.', 'amazonautolinks'); ?></p>		
		<h4><?php _e('Max Number of Items to Show', 'amazonautolinks'); ?></h4>
		<p><?php _e('Get pro for unlimited items to show.', 'amazonautolinks'); ?></p>		
		<h4><?php _e('Max Number of Categories Per Unit', 'amazonautolinks'); ?></h4>
		<p><?php _e('Get pro for unlimited categories to set up!', 'amazonautolinks'); ?></p>		
		<h4><?php _e('Max Number of Units', 'amazonautolinks'); ?></h4>
		<p><?php _e('Get pro for unlimited units so that you can put ads as many as you want.', 'amazonautolinks'); ?></p>		
		
		<?php $this->buynowbutton('right', '20px 5em 20px'); ?>
	<?php
	}
	function admin_tab500($numTab=500) {
	?>
		<h3><?php _e('Translators' , 'amazonautolinks'); ?></h3>
		<p><?php _e('Bilinguals or anyone fluent in a different language can submit a translation file and the pro version will be rewarded if the language file has not been translated.' , 'amazonautolinks'); ?></p>
		<p><?php _e('To create a language file, with a plugin called, <a href="http://wordpress.org/extend/plugins/codestyling-localization/">Codestyling Localization</a>, no programming skill is required. You just edit the messages it displays. Then send the .mo file to <a href="mailto:miunosoft@michaeluno.jp">miunosoft@michaeluno.jp</a> via E-mail.' , 'amazonautolinks'); ?></p>
		<h3><?php _e('Web Masters and Bloggers' , 'amazonautolinks'); ?></h3>
		
		<p><?php _e('A web site owner with <a href="http://en.wikipedia.org/wiki/PageRank">Google PageRank</a> 3 or higher can recieve the pro version by writing an article about this plugin. It should have a link to the <a href="http://michaeluno.jp/en/amazon-auto-links/">product page</a> and some opinion or information about the plugin. It should be constructive. It could be about how to use it or how useful it is or how it could be improved or anything helpful to people and the developer. If the article is published, send the notification with the page url to <a href="mailto:miunosoft@michaeluno.jp">miunosoft@michaeluno.jp</a>.' , 'amazonautolinks'); ?></p>

		<h3><?php _e('Bug Report' , 'amazonautolinks'); ?></h3>
		<p><?php _e('If you find the plugin not working or having issues, please report it via the <a href="http://michaeluno.jp/en/bug-report">bug report form</a>.' , 'amazonautolinks'); ?></p>
		
		<?php $this->donation_info(); ?>
	<?php
	}
	function donation_info() {
	
		// since v1.0.7
		$donate_link = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=J4UJHETVAZX34">' . __('Paypal', 'amazonautolinks') . '</a>';
		?>
		<h3><?php _e('Donation' , 'amazonautolinks'); ?></h3>
		<p><?php _e('Donations are always appreciated. If you like to donate to the developer, please do so via ' . $donate_link , 'amazonautolinks'); ?>.</p>
		<?php
	
	}
	/* Methods for $this->admin_page() */
	function define_tab_captions() {
	
		// called from $this->admin_page()
		$this->tabcaptions = array(					// must set the key number so that the other extended class can remove an element without affecting other elements 
			1 => __('New Unit', 'amazonautolinks'),				// 1, for tab 100 - 199
			2 => __('Manage Units', 'amazonautolinks'),			// 2, for tab 200 - 299
			3 => __('General Settings', 'amazonautolinks'),		// 3, for tab 300 - 399
			4 => __('Upgrade to Pro', 'amazonautolinks'),				// 4, for tab 400 - 499
			5 => __('Information', 'amazonautolinks')			// 5, for tab 500 - 599
		);
	}
	function page_header() {
	
		// called from $this->admin_page()
				
		// icon
		$this->screen_icon();

		// user ad
		$oUserAd = new AmazonAutoLinks_UserAds($this->pluginkey);
		$oUserAd->show_top_banner();
		
		// page header title
		$strClassVer = $this->classver == 'pro' ? ' Pro' : '';
		echo '<h2 style="height:1.8em;">' . $this->pluginname . $strClassVer . '</h2>';
		
		// tab menu
		$this->tab_menu();	// the property, $this->current_tab, is set there	
	}
	function screen_icon() {
	
		// called from $this->admin_page() but can be independently used
		echo '<div class="icon32" style="background-image: url(' . plugins_url( 'img/logo_AmazonAutoLinks36.gif' , dirname(__FILE__ )) . ');"><br /></div>';
	}	
	function tab_menu() {
	
		// called from $this->admin_page(), dependant on admin_page() 
		// This method should be called from the administration page. It displays the tabbed menu placed on top of the page.
		// creates the property, current_tab, which is used in the main method to display the admin page.
		$this->current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 100;			// retrieve the current tab from the url
		$numFlooredTabNum = round(floor($this->current_tab / 100 ) * 100, -2);		// this converts tab numbers to the floored rounded number. e.g. 399 becomes 300 
		$numTabs = count($this->tabcaptions);		// stores how many tabs are available
		echo '<h2 class="nav-tab-wrapper">';
		foreach($this->tabcaptions as $numTab => $strTabCaption) {
			if ($numTab == 0)
				continue;
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
		<tr>
			<th scope="col" id="cb" class="manage-column column-cb check-column" style="" valign="middle">
				<input type="checkbox">
			</th>
			<th scope="col" id="unitlabel" class="manage-column column-date asc" style="">
				<span><?php _e('Unit Label', 'amazonautolinks'); ?></span><span class="sorting-indicator"></span>
			</th>
			<th scope="col" id="associateid" class="manage-column column-date asc" style="">
				<span><?php _e('Associate ID', 'amazonautolinks'); ?></span><span class="sorting-indicator"></span>
			</th>
			<th scope="col" id="imagesize" class="manage-column column-comments num " style="">
				<?php _e('Image Size', 'amazonautolinks'); ?>
			</th>				
			<th scope="col" id="sortorder" class="manage-column column-date" style="">
				<?php _e('Sort Order', 'amazonautolinks'); ?>
			</th>
			<th scope="col" id="types" class="manage-column column-date" style="">
				<?php _e('Types', 'amazonautolinks'); ?>
			</th>
			<th scope="col" id="numitems" class="manage-column column-comments num sortable desc" style="">
				<?php _e('Items to Show', 'amazonautolinks'); ?>
			</th>
			<th scope="col" id="refnosim" class="manage-column column-comments num " style="">
				<?php _e('Nosim', 'amazonautolinks'); ?>
			</th>								
			<th scope="col" id="insertion" class="manage-column column-date" style="">
				<?php _e('Insertions', 'amazonautolinks'); ?>
			</th>			
			<th scope="col" id="code" class="manage-column column-tags" style="">
				<?php _e('Short Code', 'amazonautolinks'); ?> / <?php _e('PHP Code', 'amazonautolinks'); ?>
			</th>
			<th scope="col" id="categories" class="manage-column column-tags" style="">
				<?php _e('Categories', 'amazonautolinks'); ?>
			</th>				
			<th scope="col" id="operation" class="manage-column column-comments num " style="">
				<?php _e('Operation', 'amazonautolinks'); ?>
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
	function savesubmittion($numTab, $strButton, $strOption) {
		
		// $numTab: indicates which page tab number to deal with
		// $strButton: indicates what form button is pressed
		// $strOption: indicates what option name to save in the array of '$options[$this->pluginkey][$strOption]'
		// returns false if the validation fails; returns true if the opsions are updated.
		
		// if the button is not pressed, go back
		if (!IsSet($_POST[$this->pluginkey]['tab' . $numTab][$strButton]))
			return;	
		
		// validate the submitted data
		$this->oAALOptions->arrOptions[$numTab]['errors'] = $this->validate_options($numTab);
		if ($this->oAALOptions->arrOptions[$numTab]['errors'])
			return false;
		
		// save the data
		$this->oAALOptions->arrOptions[$strOption] = $_POST[$this->pluginkey]['tab' . $numTab];
		$this->oAALOptions->update();
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