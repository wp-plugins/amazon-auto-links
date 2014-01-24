<?php
/**
 * Deals with the plugin admin pages.
 * 
 * @package     Amazon Auto Links
 * @copyright   Copyright (c) 2013, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		2.0.0
 * 
 */
abstract class AmazonAutoLinks_AdminPage_ extends AmazonAutoLinks_AdminPageFramework {

	public function start_AmazonAutoLinks_AdminPage() {
		
		// Set objects
		$this->oOption = & $GLOBALS['oAmazonAutoLinks_Option'];
		$this->oEncode = new AmazonAutoLinks_Encrypt;

		// Disable object caching in the plugin pages and the options.php (the page that stores the settings)
		if ( 
			$GLOBALS['pagenow'] == 'options.php'
			|| isset( $_GET['post_type'] ) && ( $_GET['post_type'] == AmazonAutoLinks_Commons::PostTypeSlug || $_GET['post_type'] == AmazonAutoLinks_Commons::PostTypeSlugAutoInsert ) ) 
		{
			// wp_suspend_cache_addition( true );	
			$GLOBALS['_wp_using_ext_object_cache'] = false;
		}
	
		// For the create new unit page. Disable the default one.
		if ( $GLOBALS['pagenow'] == 'post-new.php' && isset( $_GET['post_type'] ) && count( $_GET ) == 1 ) {
				
			if ( $_GET['post_type'] == AmazonAutoLinks_Commons::PostTypeSlug )
				die( wp_redirect( add_query_arg( array( 'post_type' => AmazonAutoLinks_Commons::PostTypeSlug, 'page' => 'aal_add_category_unit' ), admin_url( 'edit.php' ) ) ) );
			if ( $_GET['post_type'] == AmazonAutoLinks_Commons::PostTypeSlugAutoInsert )
				die( wp_redirect( add_query_arg( array( 'post_type' => AmazonAutoLinks_Commons::PostTypeSlug, 'page' => 'aal_define_auto_insert' ), admin_url( 'edit.php' ) ) ) );

		}
		
		$this->oUserAds = isset( $GLOBALS['oAmazonAutoLinksUserAds'] ) ? $GLOBALS['oAmazonAutoLinksUserAds'] : new AmazonAutoLinks_UserAds;
					
	}
	
    public function setUp() {

		// Set capability for admin pages.
		if ( isset( $this->oProps->arrOptions['aal_settings']['capabilities']['setting_page_capability'] ) 
			&& ! empty( $this->oProps->arrOptions['aal_settings']['capabilities']['setting_page_capability'] )
		)	
			$this->setCapability( $this->oProps->arrOptions['aal_settings']['capabilities']['setting_page_capability'] );
	
		// Set the root menu.
		$this->setRootMenuPageBySlug( 'edit.php?post_type=amazon_auto_links' );
		
		// Set sub menus.
		$this->addSubMenuItems(
			array(
				'strPageTitle'		=> __( 'Add Unit by Category', 'amazon-auto-links' ),
				'strPageSlug'		=> 'aal_add_category_unit',
				'strScreenIcon'	=> AmazonAutoLinks_Commons::getPluginURL( "/image/screen_icon_32x32.png" ),
				// 'fPageHeadingTab' => false,
			),		
			array(
				'strPageTitle'		=> __( 'Add Unit by Tag', 'amazon-auto-links' ),
				'strPageSlug'		=> 'aal_add_tag_unit',
				'strScreenIcon'	=> AmazonAutoLinks_Commons::getPluginURL( "/image/screen_icon_32x32.png" ),
			),				
			array(
				'strPageTitle'		=> __( 'Add Unit by Search', 'amazon-auto-links' ),
				'strPageSlug'		=> 'aal_add_search_unit',
				'strScreenIcon'		=> AmazonAutoLinks_Commons::getPluginURL( "/image/screen_icon_32x32.png" ),
			),				
			array(
				'strMenuTitle'		=> __( 'Manage Auto Insert', 'amazon-auto-links' ),
				'strURL'			=> admin_url( 'edit.php?post_type=aal_auto_insert' ),
			),								
			array(
				'strPageTitle'		=> $GLOBALS['pagenow'] == 'edit.php' && isset( $_GET['mode'], $_GET['page'], $_GET['post_type'], $_GET['post'] ) && $_GET['mode'] == 'edit' && $_GET['page'] == 'aal_define_auto_insert'
					? __( 'Edit Auto Insert', 'amazon-auto-links' )
					: __( 'Add Auto Insert', 'amazon-auto-links' ),
				'strPageSlug'		=> 'aal_define_auto_insert',
				'strScreenIcon'		=> AmazonAutoLinks_Commons::getPluginURL( "/image/screen_icon_32x32.png" ),
			),					
			array(
				'strPageTitle'		=> __( 'Settings', 'amazon-auto-links' ),
				'strPageSlug'		=> 'aal_settings',
				'strScreenIcon'		=> AmazonAutoLinks_Commons::getPluginURL( "/image/screen_icon_32x32.png" ),
			),
			// array(
				// 'strPageTitle' => __( 'Extensions', 'amazon-auto-links' ),
				// 'strPageSlug' => 'aal_extensions',
				// 'strScreenIcon'	=> AmazonAutoLinks_Commons::getPluginURL( "/image/screen_icon_32x32.png" ),
			// ),			
			array(
				'strPageTitle' => __( 'Templates', 'amazon-auto-links' ),
				'strPageSlug' => 'aal_templates',
				'strScreenIcon'	=> AmazonAutoLinks_Commons::getPluginURL( "/image/screen_icon_32x32.png" ),
			),
			array(
				'strPageTitle' => __( 'About', 'amazon-auto-links' ),
				'strPageSlug' => 'aal_about',
				'strScreenIcon'	=> AmazonAutoLinks_Commons::getPluginURL( "/image/screen_icon_32x32.png" ),
			),
			array(
				'strPageTitle' => __( 'Help', 'amazon-auto-links' ),
				'strPageSlug' => 'aal_help',
				'strScreenIcon'	=> AmazonAutoLinks_Commons::getPluginURL( "/image/screen_icon_32x32.png" ),
			)					
		);
		if ( $this->oOption->isDebugMode() )
			$this->addSubMenuItems(
				array(
					'strPageTitle'		=> __( 'Debug', 'amazon-auto-links' ),
					'strPageSlug'		=> 'aal_debug',
					'strScreenIcon'	=> AmazonAutoLinks_Commons::getPluginURL( "/image/screen_icon_32x32.png" ),
					// 'fPageHeadingTab' => false,
				)
			);
			
		// In-page tabs for the Add New Category Unit page.
		$this->addInPageTabs(
			array(
				'strPageSlug'	=> 'aal_add_category_unit',
				'strTabSlug'	=> 'set_category_unit_options',
				'strTitle'		=> __( 'Select Categories', 'amazon-auto-links' ),
				'fHide'			=> true,
			),		
			array(
				'strPageSlug'	=> 'aal_add_category_unit',
				'strTabSlug'	=> 'select_categories',
				'strTitle'		=> __( 'Select Categories', 'amazon-auto-links' ),
				'strParentTabSlug' => 'set_category_unit_options',
				'fHide'			=> true,
			)
		);
		// In-page tabs for the Add Search Unit page.
		$this->addInPageTabs(
			array(
				'strPageSlug'	=> 'aal_add_search_unit',
				'strTabSlug'	=> 'initial_search_settings',
				'strTitle'		=> __( 'Initial Options', 'amazon-auto-links' ),
				'fHide'			=> true,
			),		
			array(
				'strPageSlug'	=> 'aal_add_search_unit',
				'strTabSlug'	=> 'search_products',
				'strTitle'		=> __( 'Product Search', 'amazon-auto-links' ),
				'strParentTabSlug' => 'initial_search_settings',
				'fHide'			=> true,
			),
			array(
				'strPageSlug'	=> 'aal_add_search_unit',
				'strTabSlug'	=> 'item_lookup',
				'strTitle'		=> __( 'Item Lookup', 'amazon-auto-links' ),
				'strParentTabSlug' => 'initial_search_settings',
				'fHide'			=> true,
			),
			array(
				'strPageSlug'	=> 'aal_add_search_unit',
				'strTabSlug'	=> 'similarity_lookup',
				'strTitle'		=> __( 'Similarity Lookup', 'amazon-auto-links' ),
				'strParentTabSlug' => 'initial_search_settings',
				'fHide'			=> true,
			)
		);
		// The Settings page
		$this->addInPageTabs(
			array(
				'strPageSlug'	=> 'aal_settings',
				'strTabSlug'	=> 'authentication',
				'strTitle'		=> __( 'Authentication', 'amazon-auto-links' ),
				'numOrder'		=> 1,				
			),						
			array(
				'strPageSlug'	=> 'aal_settings',
				'strTabSlug'	=> 'general',
				'strTitle'		=> __( 'General', 'amazon-auto-links' ),
				'numOrder'		=> 2,				
			),				
			array(
				'strPageSlug'	=> 'aal_settings',
				'strTabSlug'	=> 'misc',
				'strTitle'		=> __( 'Misc', 'amazon-auto-links' ),
				'numOrder'		=> 3,				
			),			
			array(
				'strPageSlug'	=> 'aal_settings',
				'strTabSlug'	=> 'reset',
				'strTitle'		=> __( 'Reset', 'amazon-auto-links' ),
				'numOrder'		=> 4,				
			),
			array(
				'strPageSlug'	=> 'aal_settings',
				'strTabSlug'	=> 'support',
				'strTitle'		=> __( 'Support', 'amazon-auto-links' ),
				'fHide'			=> true,
				// 'strParentTabSlug' => 'general',
			),
			array(
				'strPageSlug'	=> 'aal_settings',
				'strTabSlug'	=> 'import_v1_options',
				'strTitle'		=> __( 'Import v1 Options', 'amazon-auto-links' ),
				'fHide'			=> true,
				// 'strParentTabSlug' => 'general',
			)
		);
		// $this->addInPageTabs(
			// array(
				// 'strPageSlug'	=> 'extensions',
				// 'strTabSlug'	=> 'get_extensions',
				// 'strTitle'		=> __( 'Get Extensions', 'amazon-auto-links' ),
				// 'numOrder'		=> 10,				
			// )		
		// );
		$this->addInPageTabs(
			array(
				'strPageSlug'	=> 'aal_templates',
				'strTabSlug'	=> 'table',
				'strTitle'		=> __( 'Installed Templates', 'amazon-auto-links' ),
				'numOrder'		=> 1,				
			),
			array(
				'strPageSlug'	=> 'aal_templates',
				'strTabSlug'	=> 'get',
				'strTitle'		=> __( 'Get Templates', 'amazon-auto-links' ),
				'numOrder'		=> 10,				
			)			
		);		
		$this->addInPageTabs(
			array(
				'strPageSlug'	=> 'aal_about',
				'strTabSlug'	=> 'features',
				'strTitle'		=> __( 'Features', 'amazon-auto-links' ),			
			),
			array(
				'strPageSlug'	=> 'aal_about',
				'strTabSlug'	=> 'get_pro',
				'strTitle'		=> __( 'Get Pro!', 'amazon-auto-links' ),
			),
			array(
				'strPageSlug'	=> 'aal_about',
				'strTabSlug'	=> 'contact',
				'strTitle'		=> __( 'Contact', 'amazon-auto-links' ),			
			),
			array(
				'strPageSlug'	=> 'aal_about',
				'strTabSlug'	=> 'change_log',
				'strTitle'		=> __( 'Change Log', 'amazon-auto-links' ),			
			)
		);
		$this->addInPageTabs(
			array(
				'strPageSlug'	=> 'aal_help',
				'strTabSlug'	=> 'install',
				'strTitle'		=> __( 'Installation', 'amazon-auto-links' ),
			),
			array(
				'strPageSlug'	=> 'aal_help',
				'strTabSlug'	=> 'faq',
				'strTitle'		=> __( 'FAQ', 'amazon-auto-links' ),			
			), 
			array(
				'strPageSlug'	=> 'aal_help',
				'strTabSlug'	=> 'notes',
				'strTitle'		=> __( 'Other Notes', 'amazon-auto-links' ),			
			)		
		);		
	
		/*
		 * HTML elements and styling
		 */
		$this->showPageHeadingTabs( false );		// disables the page heading tabs by passing false.
		$this->setInPageTabTag( 'h2' );				
		$this->setInPageTabTag( 'h3', 'aal_add_category_unit' );				
		$this->showInPageTabs( false, 'aal_add_category_unit' );
		$this->showInPageTabs( false, 'aal_add_search_unit' );
		$this->showInPageTabs( false, 'aal_define_auto_insert' );
				
		$this->enqueueStyle( AmazonAutoLinks_Commons::getPluginURL( 'css/admin.css' ) );
		$this->enqueueStyle( AmazonAutoLinks_Commons::getPluginURL( 'css/select_categories.css' ), 'aal_add_category_unit', 'select_categories' );
		// $this->enqueueStyle( AmazonAutoLinks_Commons::getPluginURL( 'css/aal_add_category_unit.css' ), 'aal_add_category_unit' );
		$this->enqueueStyle( AmazonAutoLinks_Commons::getPluginURL( 'css/aal_add_search_unit.css' ), 'aal_add_search_unit' );
		// $this->enqueueStyle( AmazonAutoLinks_Commons::getPluginURL( 'css/aal_add_tag_unit.css' ), 'aal_add_tag_unit' );
		$this->enqueueStyle( AmazonAutoLinks_Commons::getPluginURL( 'css/aal_settings.css' ), 'aal_settings' );
		// $this->enqueueStyle( AmazonAutoLinks_Commons::getPluginURL( 'css/aal_define_auto_insert.css' ), 'aal_define_auto_insert' );
		$this->enqueueStyle( AmazonAutoLinks_Commons::getPluginURL( 'css/aal_templates.css' ), 'aal_templates' );
		$this->enqueueStyle( AmazonAutoLinks_Commons::getPluginURL( 'css/readme.css' ), 'aal_about' );
		$this->enqueueStyle( AmazonAutoLinks_Commons::getPluginURL( 'css/readme.css' ), 'aal_help' );
		$this->enqueueStyle( AmazonAutoLinks_Commons::getPluginURL( 'css/get_pro.css' ), 'aal_about', 'get_pro' );
		$this->enqueueStyle( AmazonAutoLinks_Commons::getPluginURL( 'template/preview/style-preview.css' ), 'aal_add_category_unit', 'select_categories' );

		$this->setDisallowedQueryKeys( array( 'aal-option-upgrade', 'bounce_url' ) );
		
		/*
		 * Form elements - Sections
		 */

		// Form Elements - Add Unit by Category 
		$oCategoryFormElements = new AmazonAutoLinks_Form_Category( 'aal_add_category_unit' );
		call_user_func_array( array( $this, "addSettingSections" ), $oCategoryFormElements->getSections() );
		call_user_func_array( array( $this, "addSettingFields" ), $oCategoryFormElements->getFields( 'category' ) );
		call_user_func_array( array( $this, "addSettingFields" ), $oCategoryFormElements->getFields( 'category_auto_insert' ) );
		call_user_func_array( array( $this, "addSettingFields" ), $oCategoryFormElements->getFields( 'category_template' ) );
									
		// Form Elements - Add Unit by Tag and Customer ID 
		$oTagFormElements = new AmazonAutoLinks_Form_Tag( 'aal_add_tag_unit' );
		call_user_func_array( array( $this, "addSettingSections" ), $oTagFormElements->getSections() );
		call_user_func_array( array( $this, "addSettingFields" ), $oTagFormElements->getFields( 'tag' ) );
		call_user_func_array( array( $this, "addSettingFields" ), $oTagFormElements->getFields( 'tag_auto_insert' ) );
		call_user_func_array( array( $this, "addSettingFields" ), $oTagFormElements->getFields( 'tag_template' ) );

		// Form Elements - Add Unit by Search
		$oSearchFormElements = new AmazonAutoLinks_Form_Search( 'aal_add_search_unit' );
		call_user_func_array( array( $this, "addSettingSections" ), $oSearchFormElements->getSections() );
		call_user_func_array( array( $this, "addSettingFields" ), $oSearchFormElements->getFields( 'search' ) );
		
		call_user_func_array( array( $this, "addSettingFields" ), $oSearchFormElements->getFields( 'search_second', 'search2_' ) );
		call_user_func_array( array( $this, "addSettingFields" ), $oSearchFormElements->getFields( 'search_advanced', 'search2_' ) );
		call_user_func_array( array( $this, "addSettingFields" ), $oSearchFormElements->getFields( 'search_auto_insert', 'search2_' ) );
		call_user_func_array( array( $this, "addSettingFields" ), $oSearchFormElements->getFields( 'search_template', 'search2_' ) );
		
		call_user_func_array( array( $this, "addSettingFields" ), $oSearchFormElements->getFields( 'search_item_lookup', 'search3_' ) );
		call_user_func_array( array( $this, "addSettingFields" ), $oSearchFormElements->getFields( 'search_item_lookup_advanced', 'search3_' ) );
		call_user_func_array( array( $this, "addSettingFields" ), $oSearchFormElements->getFields( 'search_auto_insert2', 'search3_' ) );
		call_user_func_array( array( $this, "addSettingFields" ), $oSearchFormElements->getFields( 'search_template2', 'search3_' ) );

		call_user_func_array( array( $this, "addSettingFields" ), $oSearchFormElements->getFields( 'similarity_lookup', 'search4_' ) );
		call_user_func_array( array( $this, "addSettingFields" ), $oSearchFormElements->getFields( 'similarity_lookup_advanced', 'search4_' ) );
		call_user_func_array( array( $this, "addSettingFields" ), $oSearchFormElements->getFields( 'search_auto_insert3', 'search4_' ) );
		call_user_func_array( array( $this, "addSettingFields" ), $oSearchFormElements->getFields( 'search_template3', 'search4_' ) );
		
		// Form elements - Add / Edit Auto Insert
		$oAutoInsertFormElements = new AmazonAutoLinks_Form_AutoInsert( 'aal_define_auto_insert' );
		call_user_func_array( array( $this, "addSettingSections" ), $oAutoInsertFormElements->getSections() );
		call_user_func_array( array( $this, "addSettingFields" ), $oAutoInsertFormElements->getFields( 'autoinsert_status' ) );
		call_user_func_array( array( $this, "addSettingFields" ), $oAutoInsertFormElements->getFields( 'autoinsert_area' ) );
		call_user_func_array( array( $this, "addSettingFields" ), $oAutoInsertFormElements->getFields( 'autoinsert_static_insertion' ) );
		call_user_func_array( array( $this, "addSettingFields" ), $oAutoInsertFormElements->getFields( 'autoinsert_enable' ) );
		call_user_func_array( array( $this, "addSettingFields" ), $oAutoInsertFormElements->getFields( 'autoinsert_disable' ) );
		
		// Form elements - Settings 
		$oSettingsFormElements = new AmazonAutoLinks_Form_Settings( 'aal_settings' );
		call_user_func_array( array( $this, "addSettingSections" ), $oSettingsFormElements->getSections() );
		call_user_func_array( array( $this, "addSettingFields" ), $oSettingsFormElements->getFields() );	
		
		$this->addLinkToPluginDescription(  
			'<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=J4UJHETVAZX34">' . __( 'Donate', 'amazon-auto-links' ) . '</a>',
			'<a href="http://en.michaeluno.jp/contact/custom-order/?lang=' . ( WPLANG ? WPLANG : 'en' ) . '">' . __( 'Order custom plugin', 'amazon-auto-links' ) . '</a>'
		);						
		
	}
	
	
	/*
	 * Customize the Menu
	 */
	public function buildMenus() {
	
		parent::buildMenus();

		// Somehow the settings link in the plugin listing page points to the Create Rule by List page. So fix it to the Settings page.
		$this->oProps->strDefaultPageSlug = 'aal_settings';
		
		// Remove the default post type menu item.
		$strPageSlug = $this->oProps->arrRootMenu['strPageSlug'];
		foreach ( $GLOBALS['submenu'][ $strPageSlug ] as $intIndex => $arrSubMenu ) {
						
			if ( ! isset( $arrSubMenu[ 2 ] ) ) continue;
			
			// Remove the default Add New entry.
			if ( $arrSubMenu[ 2 ] == 'post-new.php?post_type=' . AmazonAutoLinks_Commons::PostTypeSlug ) {
				unset( $GLOBALS['submenu'][ $strPageSlug ][ $intIndex ] );
				continue;
			}
			
			// Edit the first item
			if ( $arrSubMenu[ 2 ] == 'edit.php?post_type=' . AmazonAutoLinks_Commons::PostTypeSlug ) {
				$GLOBALS['submenu'][ $strPageSlug ][ $intIndex ][ 0 ] = __( 'Manage Units', 'amazon-auto-links' );
				continue;
			}

			// Copy and remove the Tag menu element to change the position. 
			if ( $arrSubMenu[ 2 ] == 'edit-tags.php?taxonomy=' . AmazonAutoLinks_Commons::TagSlug . '&amp;post_type=' . AmazonAutoLinks_Commons::PostTypeSlug ) {
				$arrMenuEntry_Tag = array( $GLOBALS['submenu'][ $strPageSlug ][ $intIndex ] );
				unset( $GLOBALS['submenu'][ $strPageSlug ][ $intIndex ] );
				continue;				
			}

		}
		
		// Second iterations.
		$intMenuPos_Setting = -1;
		foreach ( $GLOBALS['submenu'][ $strPageSlug ] as $intIndex => $arrSubMenu ) {
			
			$intMenuPos_Setting++;	
			if (  isset( $arrSubMenu[ 2 ] ) && $arrSubMenu[ 2 ] == 'aal_settings' ) 
				break;	// the position variable will now contain the position of the Setting menu item.
	
		}
	
		// Insert the Tag menu item before the Settings menu item.
		if ( isset( $arrMenuEntry_Tag ) )
			array_splice( 
				$GLOBALS['submenu'][ $strPageSlug ], // original array
				$intMenuPos_Setting, 	// position
				0, 	// offset - should be 0
				$arrMenuEntry_Tag 	// replacement array
			);		

		// Unfortunately array_splice() will lose all the associated keys(index).
		
	}
	
	/*
	 * Layout the setting pages
	 * */
	function head_AmazonAutoLinks_AdminPage( $strHead ) {

		return '<div class="top-right">' . $this->oUserAds->getTopBanner() . '</div>'
			. $strHead 
			. '<div class="amazon-auto-links-admin-body">'
				. '<table border="0" cellpadding="0" cellspacing="0" unselectable="on" width="100%">'
					. '<tbody>'
						. '<tr>'
							. '<td valign="top">'
								. '<div style="margin-top: 10px;">'
									. $this->oUserAds->getTextAd()
								. '</div>';
			
	}
	function foot_AmazonAutoLinks_AdminPage( $strFoot ) {
		
		switch ( isset( $_GET['tab'] ) ? $_GET['tab'] : '' ) {
			case 'tabname':
				$numItems = defined( 'WPLANG' ) && WPLANG == 'ja' ? 4 : 4;
				break;
			default:
				$numItems = 4;
				break;
		}	
		
		return $strFoot 
						// . '<div style="float:left; margin-top: 10px" >' 
						// . $this->oUserAds->getTextAd() 
						// . '</div>'
							. '</td>
							<td valign="top" rowspan="2" style="padding-top:20px;">' 
							. ( rand( 0, 1 ) ? $this->oUserAds->get160xNTopRight() : $this->oUserAds->get160xN( $numItems ) )
							// . $this->oUserAds->GetSkyscraper( $numItems ) 
							. '</td>
						</tr>
						<tr>
							<td valign="bottom" align="center">'
								// . $this->oUserAds->getBottomBanner() 
						. '</td>
						</tr>
					</tbody>
				</table>'
			. '</div><!-- end amazon-auto-links-admin-body -->';
			
	}	 
	
	/*
	 * The Add Unit by Category Page 
	 * 
	 */
	public function do_aal_add_category_unit() {
		// echo AmazonAutoLinks_Debug::getArray( $this->oProps );
	}
	public function load_aal_add_category_unit_select_categories() {	// load_ + page slug + _  + tab slug

		// Retrieve the submitted options. 
		$strTransientID = isset( $_GET['transient_id'] ) ? $_GET['transient_id'] : '';
		$arrOptions = get_transient( 'AAL_CreateUnit_' . $strTransientID );
		
		// Note that this method is called in the validation callback as well whose page is options.php and does not have $_GET parameters.
		if ( ! isset( $_GET['post'] ) && $arrOptions === false )
			die ( "<div class='error'><p>" . __( 'An error occurred. Please go back to the previous page and do it again.', 'amazon-auto-links' ) . "</p></div>" );
			
		$this->oCategorySelect = new AmazonAutoLinks_CategorySelect( $arrOptions );	
	
	}
	public function do_aal_add_category_unit_select_categories() {	// do_ + page slug + _  + tab slug
		
		$strTransientID = isset( $_GET['transient_id'] ) ? $_GET['transient_id'] : '';
		$arrOptions = $this->oCategorySelect->renderForm();
		set_transient( 'AAL_CreateUnit_' . $strTransientID, $arrOptions, 60*10*6*24 );	// this transient should be deleted when creating a new unit.
		
	}
	
	public function validation_aal_add_category_unit_set_category_unit_options( $arrInput, $arrOldInput ) {	// validation + _ + page slug + tab slug
		
		$fVerified = true;
		$arrErrors = array();
		
		// Check the limitation.
		if ( $this->oOption->isUnitLimitReached() ) {

			$this->setFieldErrors( array( 'error' ) );		// must set an field error array which does not yield empty so that it won't be redirected.
			$this->setSettingNotice( 
				sprintf( 
					__( 'Please upgrade to <A href="%1$s">Pro</a> to add more units! Make sure to empty the <a href="%2$s">trash box</a> to delete the units completely!', 'amazon-auto-links' ), 
					'http://en.michaeluno.jp/amazon-auto-links-pro/',
					admin_url( 'edit.php?post_status=trash&post_type=' . AmazonAutoLinks_Commons::PostTypeSlug )
				)
			);
			return $arrOldInput;
			
		} 	
		
		if ( empty( $arrInput['aal_add_category_unit']['category']['category_associate_id'] ) ) {
			
			$arrErrors['category']['category_associate_id'] = __( 'The associate ID cannot be empty.', 'amazon-auto-links' );
			$fVerified = false;
			
		}
					
		// An invalid value is found.
		if ( ! $fVerified ) {
		
			// Set the error array for the input fields.
			$this->setFieldErrors( $arrErrors );		
			$this->setSettingNotice( __( 'There was an error in your input.', 'amazon-auto-links' ) );
			return $arrOldInput;
			
		}		
			
		// Drop the sections.
		$arrNewFields = array();
		foreach( $arrInput['aal_add_category_unit'] as $strSection => $arrFields  ) 
			$arrNewFields = $arrNewFields + $arrFields;
		$arrSanitizedFields = array();
		
		// Remove the category_ prefix in the keys.
		foreach( $arrNewFields as $strKey => $vValue ) 
			$arrSanitizedFields[ preg_replace( '/^category_/', '', $strKey ) ] = $vValue;
		$arrSanitizedFields['categories'] = array();
		$arrSanitizedFields['categories_exclude'] = array();
		
		$arrSanitizedFields = $this->oOption->sanitizeUnitOpitons( $arrSanitizedFields );
			
		// If nothing is checked for the feed type, enable the bestseller item.
		if ( ! array_filter( $arrSanitizedFields['feed_type'] ) ) 			
			$arrSanitizedFields['feed_type']['bestsellers'] = true;
		
		$arrTempUnitOptions = ( array ) get_transient( 'AAL_CreateUnit_' . $arrSanitizedFields['transient_id'] );
		set_transient( 'AAL_CreateUnit_' . $arrSanitizedFields['transient_id'], AmazonAutoLinks_Utilities::uniteArrays( $arrSanitizedFields, $arrTempUnitOptions ), 60*10*6*24 );
			
// AmazonAutoLinks_Debug::logArray( $arrSanitizedFields );
		
		return $arrInput;
		
	}

	/*
	 * The Add Unit by Tag Page
	 * 
	 */ 
	public function validation_aal_add_tag_unit( $arrInput, $arrOldInput ) {	// validation + _ + page slug + tab slug

		$fVerified = true;
		$arrErrors = array();
		
		// Check the limitation.
		if ( $this->oOption->isUnitLimitReached() ) {
			
			$this->setSettingNotice( 
				sprintf( 
					__( 'Please upgrade to <A href="%1$s">Pro</a> to add more units! Make sure to empty the <a href="%2$s">trash box</a> to delete the units completely!', 'amazon-auto-links' ), 
					'http://en.michaeluno.jp/amazon-auto-links-pro/',
					admin_url( 'edit.php?post_status=trash&post_type=' . AmazonAutoLinks_Commons::PostTypeSlug )
				)
			);
			return $arrOldInput;
			
		}		
		
		// Customer ID must be 13 characters
		if ( $arrInput['aal_add_tag_unit']['tag']['tag_customer_id'] && strlen( $arrInput['aal_add_tag_unit']['tag']['tag_customer_id'] ) != 13 ) {
			
			$arrErrors['tag']['tag_customer_id'] = __( 'The customer ID must consist of 13 characters.', 'amazon-auto-links' ) . ' ';
			$arrInput['aal_add_tag_unit']['tag']['tag_customer_id'] = '';
			$fVerified = false;
			
		}
		
		if ( empty( $arrInput['aal_add_tag_unit']['tag']['tag_tags'] ) && empty( $arrInput['aal_add_tag_unit']['tag']['tag_customer_id'] ) ) {
			
			$arrErrors['tag']['tag_tags'] = __( 'Either tags or customer ID has to be entered.', 'amazon-auto-links' );
			
			$strMessage = __( 'Either tags or customer ID has to be entered.', 'amazon-auto-links' );
			$arrErrors['tag']['tag_customer_id'] = isset( $arrErrors['tag']['tag_customer_id'] )
				? $arrErrors['tag']['tag_customer_id'] . $strMessage
				: $strMessage;
			$fVerified = false;
			
		}
		
		if ( empty( $arrInput['aal_add_tag_unit']['tag']['tag_associate_id'] ) ) {
			
			$arrErrors['tag']['tag_associate_id'] = __( 'The associate ID cannot be empty.', 'amazon-auto-links' );
			$fVerified = false;
			
		}
		
		// An invalid value is found.
		if ( ! $fVerified ) {
		
			// Set the error array for the input fields.
			$this->setFieldErrors( $arrErrors );		
			$this->setSettingNotice( __( 'There was an error in your input.', 'amazon-auto-links' ) );
			return $arrOldInput;
			
		}		
			
		// Drop the sections.
		$arrNewFields = array();
		foreach( $arrInput['aal_add_tag_unit'] as $strSection => $arrFields  ) 
			$arrNewFields = $arrNewFields + $arrFields;
		$arrSanitizedFields = array();
		
		// Remove the tag_ prefix in the keys.
		foreach( $arrNewFields as $strKey => $vValue ) 
			$arrSanitizedFields[ preg_replace( '/^tag_/', '', $strKey ) ] = $vValue;
		
		// Sanitize the tag input
		$arrSanitizedFields['tags'] = trim( AmazonAutoLinks_Utilities::trimDelimitedElements( $arrSanitizedFields['tags'], ',' ) ); 
		
		$arrSanitizedFields = $this->oOption->sanitizeUnitOpitons( $arrSanitizedFields );
		
		// If nothing is checked for the feed type, enable the bestseller item.
		if ( ! array_filter( $arrSanitizedFields['feed_type'] ) ) 			
			$arrSanitizedFields['feed_type']['new'] = true;		

// AmazonAutoLinks_Debug::logArray( '--Before Escaping KSES Filter--' );			
// AmazonAutoLinks_Debug::logArray( $arrSanitizedFields['item_format'] );
// AmazonAutoLinks_Debug::logArray( $arrSanitizedFields['image_format'] );
// AmazonAutoLinks_Debug::logArray( $arrSanitizedFields['title_format'] );

		// Apply allowed HTML tags for the KSES filter.
		add_filter( 'safe_style_css', array( $this, 'allowInlineStyleMaxWidth' ) );
		$arrAllowedHTMLTags = AmazonAutoLinks_Utilities::convertStringToArray( $this->oOption->arrOptions['aal_settings']['form_options']['allowed_html_tags'], ',' );
		$arrSanitizedFields['item_format'] = AmazonAutoLinks_WPUtilities::escapeKSESFilter( $arrSanitizedFields['item_format'], $arrAllowedHTMLTags );
		$arrSanitizedFields['image_format'] = AmazonAutoLinks_WPUtilities::escapeKSESFilter( $arrSanitizedFields['image_format'], $arrAllowedHTMLTags );
		$arrSanitizedFields['title_format'] = AmazonAutoLinks_WPUtilities::escapeKSESFilter( $arrSanitizedFields['title_format'], $arrAllowedHTMLTags );
		remove_filter( 'safe_style_css', array( $this, 'allowInlineStyleMaxWidth' ) );
		
// AmazonAutoLinks_Debug::logArray( '--After Escaping KSES Filter--' );
// AmazonAutoLinks_Debug::logArray( $arrAllowedHTMLTags );
// AmazonAutoLinks_Debug::logArray( $arrSanitizedFields['item_format'] );
// AmazonAutoLinks_Debug::logArray( $arrSanitizedFields['image_format'] );
// AmazonAutoLinks_Debug::logArray( $arrSanitizedFields['title_format'] );

		// Create a post.			
		$fDoAutoInsert = $arrSanitizedFields['auto_insert'];
		unset( $arrSanitizedFields['auto_insert'] );
		
		$intNewPostID = AmazonAutoLinks_Option::insertPost( $arrSanitizedFields );
		
		// Create an auto insert
		if ( $fDoAutoInsert ) {
			
			$arrAutoInsertOptions = array( 
					'unit_ids' => array( $intNewPostID ) 
				) + AmazonAutoLinks_Form_AutoInsert::$arrStructure_AutoInsertOptions;
			
			AmazonAutoLinks_Option::insertPost( $arrAutoInsertOptions, AmazonAutoLinks_Commons::PostTypeSlugAutoInsert );
			
		}
		
		die( wp_redirect( 
			// e.g. http://.../wp-admin/post.php?post=196&action=edit&post_type=amazon_auto_links
			add_query_arg( 
				array( 
					'post_type' => AmazonAutoLinks_Commons::PostTypeSlug,
					'action' => 'edit',
					'post' => $intNewPostID,
				), 
				admin_url( 'post.php' ) 
			)
		) );		
		
	}
		public function allowInlineStyleMaxWidth( $arrProperties ) {
			$arrProperties[] = 'max-width';
			return $arrProperties;
		}
		
	/*
	 * The Add Unit by Search Page
	 * 
	 */ 	
	public function load_aal_add_search_unit_search_products() {

		// Validation callbacks sets it in the $_POST array so check the $_REQUEST array.
		if ( ! isset( $_REQUEST['transient_id'] ) || false === get_transient( "AAL_CreateUnit_" . $_REQUEST['transient_id'] ) ) {
			
			$strMessage = __( 'A problem occurred while loading the page of adding a search unit. Please go back to the previous page.', 'amazon-auto-links' );
			// $this->setSettingNotice( $strMessage );
			die( "<div class='error'><p>{$strMessage}</p></div>" );
			
		}			
	
	}
	
	public function validation_aal_add_search_unit_initial_search_settings( $arrInput, $arrOldInput ) {	// validation_{page slug}_{tab slug}
		
		$fVerified = true;
		$arrErrors = array();
		$arrSearchOptions = $arrInput['aal_add_search_unit']['search'];
	
		// Check the limitation.
		if ( $this->oOption->isUnitLimitReached() ) {

			$this->setFieldErrors( array( 'error' ) );		// must set an field error array which does not yield empty so that it won't be redirected.
			$this->setSettingNotice( 
				sprintf( 
					__( 'Please upgrade to <A href="%1$s">Pro</a> to add more units! Make sure to empty the <a href="%2$s">trash box</a> to delete the units completely!', 'amazon-auto-links' ), 
					'http://en.michaeluno.jp/amazon-auto-links-pro/',
					admin_url( 'edit.php?post_status=trash&post_type=' . AmazonAutoLinks_Commons::PostTypeSlug )
				)
			);
			return $arrOldInput;
			
		} 		
		
		// If the Access Key fields are present, it means the user has not set them yet in the Settings page.
		// In this case, just check if they are valid and if so, save them in the settings' option array. Otherwise, return an error.
		if ( isset( $arrSearchOptions['search_access_key'], $arrSearchOptions['search_access_key_secret'] ) ) {

			$strPublicKey = $arrSearchOptions['search_access_key'];
			if ( strlen( $strPublicKey ) != 20 ) {
				$arrErrors['search']['search_access_key'] = __( 'The Access Key ID must consist of 20 characters.', 'amazon-auto-links' ) . ': ' . $strPublicKey . ' ';
				$fVerified = false;				
			}
			$strPrivateKey = $arrSearchOptions['search_access_key_secret'];
			if ( strlen( $strPrivateKey ) != 40 ) {
				$arrErrors['search']['search_access_key_secret'] = __( 'The Secret Access Key must consist of 40 characters.', 'amazon-auto-links' ) . ': ' . $strPrivateKey . ' ';
				$fVerified = false;
			}	
			
			// An invalid value is found.
			if ( ! $fVerified ) {
			
				// Set the error array for the input fields.
				$this->setFieldErrors( $arrErrors );
				$this->setSettingNotice( __( 'There was an error in your input.', 'amazon-auto-links' ) );
				return $arrOldInput;
				
			}				
			
			// Test authentication - browse the Books node in amazon.com.
			$oAmazonAPI = new AmazonAutoLinks_ProductAdvertisingAPI( 'com', $strPublicKey, $strPrivateKey );
			if ( ! $oAmazonAPI->test() ) {
				
				$arrErrors['search']['search_access_key'] = __( 'Sent Value', 'amazon-auto-links' ) . ': ' . $strPublicKey;
				$arrErrors['search']['search_access_key_secret'] = __( 'Sent Value', 'amazon-auto-links' ) . ': ' . $strPrivateKey;			
				$this->setFieldErrors( $arrErrors );
				$this->setSettingNotice( __( 'Failed authentication.', 'amazon-auto-links' ) );
				$arrOldInput;
				
			}		

			// It is authenticated, so set the keys in the Settings option array.
			// Since the validation_ callbacks internally merge with the framework's property option array,
			// modify the property array, NOT the option object that plugin creates.
			$this->oProps->arrOptions['aal_settings']['authentication_keys']['access_key'] = $strPublicKey;
			$this->oProps->arrOptions['aal_settings']['authentication_keys']['access_key_secret'] = $strPrivateKey;
			
		}
		
		if ( empty( $arrSearchOptions['search_associate_id'] ) ) {
			
			$arrErrors['search']['search_associate_id'] = __( 'The associate ID cannot be empty.', 'amazon-auto-links' );
			$fVerified = false;							
			
		}
	
		// An invalid value is found.
		if ( ! $fVerified ) {
		
			// Set the error array for the input fields.
			$this->setFieldErrors( $arrErrors );
			$this->setSettingNotice( __( 'There was an error in your input.', 'amazon-auto-links' ) );
			return $arrOldInput;
			
		}					
				
		// Drop the sections.
		$arrNewFields = array();
		foreach( $arrInput['aal_add_search_unit'] as $strSection => $arrFields  ) 
			$arrNewFields = $arrNewFields + $arrFields;
		
		// Remove the search_ prefix in the keys.
		$arrSanitizedFields = array();
		foreach( $arrNewFields as $strKey => $vValue ) 
			$arrSanitizedFields[ preg_replace( '/^search_/', '', $strKey ) ] = $vValue;
	
		// Set the unit type based on the chosen one.
		// Redirect to the appropriate page by the search type.
		switch( $arrSanitizedFields['Operation'] ) {
			case 'ItemSearch':
				$arrSanitizedFields['unit_type'] = 'search';
				$sTabSlug = 'search_products';				
				break;
			case 'ItemLookup':
				$arrSanitizedFields['unit_type'] = 'item_lookup';
				$sTabSlug = 'item_lookup';
				break;
			case 'SimilarityLookup':
				$arrSanitizedFields['unit_type'] = 'similarity_lookup';
				$sTabSlug = 'similarity_lookup';
				break;
		}
// AmazonAutoLinks_Debug::logArray( 'validation passed' );	
// AmazonAutoLinks_Debug::logArray( $arrSanitizedFields );	
			
		// Save the transient
		$arrTempUnitOptions = ( array ) get_transient( 'AAL_CreateUnit_' . $arrSanitizedFields['transient_id'] );
		$aSavingUnitOptions = AmazonAutoLinks_Utilities::uniteArrays( $arrSanitizedFields, $arrTempUnitOptions );
		set_transient( 'AAL_CreateUnit_' . $arrSanitizedFields['transient_id'], $aSavingUnitOptions, 60*10*6*24 );
									
		// Go to the next page.
		die( wp_redirect( add_query_arg( array( 'tab' => $sTabSlug, 'transient_id' => $arrSanitizedFields['transient_id'] ) + $_GET, $arrSanitizedFields['bounce_url'] ) ) );
											
	}
	
	public function validation_aal_add_search_unit_search_products( $arrInput, $arrOldInput ) {	// validation_ + page slug + tab slug
		$this->createSearchUnit( $arrInput );
	}

	public function validation_aal_add_search_unit_item_lookup( $arrInput, $arrOldInput ) {	// validation_ + page slug + tab slug		
		$this->createSearchUnit( $arrInput );
	}

	public function validation_aal_add_search_unit_similarity_lookup( $arrInput, $arrOldInput ) {	// validation_ + page slug + tab slug		
		$this->createSearchUnit( $arrInput );
	}
	
	/**
	 * Creates a search unit type 
	 * @since			2.0.2
	 */
	protected function createSearchUnit( $arrInput ) {
		
		// Drop the sections.
		$arrNewFields = array();
		foreach( $arrInput['aal_add_search_unit'] as $strSection => $arrFields  ) 
			$arrNewFields = $arrNewFields + $arrFields;
		
		// Remove the search_ prefix in the keys.
		$arrSanitizedFields = array();
		foreach( $arrNewFields as $strKey => $vValue ) 
			$arrSanitizedFields[ preg_replace( '/^search\d_/', '', $strKey ) ] = $vValue;
		$arrSanitizedFields = $this->oOption->sanitizeUnitOpitons( $arrSanitizedFields );

		// Create a post.			
		$fDoAutoInsert = $arrSanitizedFields['auto_insert'];
		unset( $arrSanitizedFields['auto_insert'] );
		$intNewPostID = AmazonAutoLinks_Option::insertPost( $arrSanitizedFields );
		
		// Create an auto insert
		if ( $fDoAutoInsert ) {
			$arrAutoInsertOptions = array( 
					'unit_ids' => array( $intNewPostID ) 
				) + AmazonAutoLinks_Form_AutoInsert::$arrStructure_AutoInsertOptions;
			AmazonAutoLinks_Option::insertPost( $arrAutoInsertOptions, AmazonAutoLinks_Commons::PostTypeSlugAutoInsert );
		}		
		die( wp_redirect( 
			// e.g. http://.../wp-admin/post.php?post=196&action=edit&post_type=amazon_auto_links
			add_query_arg( 
				array( 
					'post_type' => AmazonAutoLinks_Commons::PostTypeSlug,
					'action' => 'edit',
					'post' => $intNewPostID,
				), 
				admin_url( 'post.php' ) 
			)
		) );
				
	}

	/**
	 * The define Auto Insert page.
	 */
	public function validation_aal_define_auto_insert( $arrInput, $arrOldInput ) {
		
		// Drop the sections.
		$arrNewFields = array();
		foreach( $arrInput['aal_define_auto_insert'] as $strSection => $arrFields  ) 
			$arrNewFields = $arrNewFields + $arrFields;

		// Remove the search_ prefix in the keys.
		$arrSanitizedFields = array();
		foreach( $arrNewFields as $strKey => $vValue ) 
			$arrSanitizedFields[ preg_replace( '/^autoinsert_/', '', $strKey ) ] = $vValue;
		$fVerified = true;
		$arrErrors = array();	
		
		// Check necessary settings.
		if ( ! array_filter( $arrSanitizedFields['built_in_areas'] +  $arrSanitizedFields['static_areas'] ) 
			&& ! $arrSanitizedFields['filter_hooks']
			&& ! $arrSanitizedFields['action_hooks']
			) {
				
			$arrErrors['autoinsert_area']['autoinsert_built_in_areas'] = __( 'At least one area must be set.', 'amazon-auto-links' );
			$arrErrors['autoinsert_static_insertion']['autoinsert_static_areas'] = __( 'At least one area must be set.', 'amazon-auto-links' );
			$arrErrors['autoinsert_area']['autoinsert_filter_hooks'] = __( 'At least one area must be set.', 'amazon-auto-links' );
			$arrErrors['autoinsert_area']['autoinsert_action_hooks'] = __( 'At least one area must be set.', 'amazon-auto-links' );
			$fVerified = false;
			
		}
		if ( ! isset( $arrSanitizedFields['unit_ids'] ) ) {	// if no item is selected, the select input with the multiple attribute does not send the key.
			
			$arrErrors['autoinsert_area']['autoinsert_unit_ids'] = __( 'A unit must be selected.', 'amazon-auto-links' );
			$fVerified = false;
			
		}
		
		// An invalid value is found.
		if ( ! $fVerified ) {
		
			// Set the error array for the input fields.
			$this->setFieldErrors( $arrErrors );
			$this->setSettingNotice( __( 'There was an error in your input.', 'amazon-auto-links' ) );
			return $arrOldInput;
			
		}				
		
		$arrSanitizedFields['filter_hooks'] = AmazonAutoLinks_Utilities::trimDelimitedElements( $arrSanitizedFields['filter_hooks'], ',' );
		$arrSanitizedFields['action_hooks'] = AmazonAutoLinks_Utilities::trimDelimitedElements( $arrSanitizedFields['action_hooks'], ',' );
		$arrSanitizedFields['enable_post_ids'] = AmazonAutoLinks_Utilities::trimDelimitedElements( $arrSanitizedFields['enable_post_ids'], ',' );
		$arrSanitizedFields['diable_post_ids'] = AmazonAutoLinks_Utilities::trimDelimitedElements( $arrSanitizedFields['diable_post_ids'], ',' );
// AmazonAutoLinks_Debug::logArray( $arrSanitizedFields );
		
		
		// Edit - Update the post.
		$fIsEdit = ( isset( $_POST['mode'], $_POST['post'] ) && $_POST['post'] && $_POST['mode'] == 'edit' );
		if ( $fIsEdit )
			AmazonAutoLinks_Option::updatePostMeta( $_POST['post'], $arrSanitizedFields );
		else	// New - Create a post.	
			$intNewPostID = AmazonAutoLinks_Option::insertPost( $arrSanitizedFields, AmazonAutoLinks_Commons::PostTypeSlugAutoInsert );
		
		// e.g. http://.../wp-admin/edit.php?post_type=aal_auto_insert
		die( 
			wp_redirect( 
				$fIsEdit	// edit.php?post_type=amazon_auto_links&page=aal_define_auto_insert&mode=edit&post=265 
					? admin_url( 'edit.php?post_type=' . AmazonAutoLinks_Commons::PostTypeSlug . '&page=aal_define_auto_insert&mode=edit&post=' . $_POST['post'] ) // stay on the same page.
					: admin_url( 'edit.php?post_type=' . AmazonAutoLinks_Commons::PostTypeSlugAutoInsert ) 	// the listing table page
			) 
		);
				
	}
	
	/*
	 * Settings Page
	 */	
	public function do_after_aal_settings () {	// do_after_ + page slug
	
		if ( ! $this->oOption->isDebugMode() ) return;
		echo "<h4>Saved Options</h4>";
		echo $this->oDebug->getArray( $this->oProps->arrOptions  );
		echo "<h4>Actual Options</h4>";
		echo "<p class='description'>Options merged with plugin's default option values</p>";
		echo $this->oDebug->getArray( $this->oOption->arrOptions );
	
	}
	
	public function validation_aal_settings( $arrInput, $arrOldInput ) {	// validation_ + page slug
		return $arrInput;	
	}
	
	public function do_form_aal_settings_authentication() {
	
		$this->renderAuthenticationStatus();
	

		
	// AmazonAutoLinks_Debug::dumpArray( $arrResponse );
		
	}
	/**
	 * Renders the authentication status table.
	 * 
	 * @since			2.0.0
	 * @param			array			$arrStatus			This arrays should be the merged array of the results of 'account/verify_credientials' and 'rate_limit_status' requests.
	 * 
	 */
	protected function renderAuthenticationStatus() {
	
		$strPublicKey = $this->getFieldValue( 'access_key' );
		$strPrivateKey = $this->getFieldValue( 'access_key_secret' );
		$oAmazonAPI = new AmazonAutoLinks_ProductAdvertisingAPI( 'com', $strPublicKey, $strPrivateKey );
		$fVerified = $oAmazonAPI->test();

		?>		
		<h3><?php _e( 'Status', 'amazon-auto-links' ); ?></h3>
		<table class="form-table auth-status">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<?php _e( 'Status', 'amazon-auto-links' ); ?>
					</th>
					<td>
						<?php echo $fVerified ? '<span class="authenticated">' . __( 'Authenticated', 'amazon-auto-links' ) . '</span>': '<span class="unauthenticated">' . __( 'Not authenticated', 'amazon-auto-links' ) . '</span>'; ?>
					</td>
				</tr>
			</tbody>
		</table>
					
		<?php

		// $arrResponse = $oAmazonAPI->request(
			// array(
				// 'Operation' => 'BrowseNodeLookup',
				// 'BrowseNodeId' => '0',	// the Books node 
				// 'BrowseNodeId' => '1000,301668',	// the Books node 
			// ),
			// 'US'	// or 'com' would work
		// );	


// AmazonAutoLinks_Debug::dumpArray( $arrResponse );
		
	}
	
	public function validation_aal_settings_authentication( $arrInput, $arrOldInput ) {	// validation_ + page slug + tab slug

		$fVerified = true;
		$arrErrors = array();

		// Access Key must be 20 characters
		$arrInput['aal_settings']['authentication_keys']['access_key'] = trim( $arrInput['aal_settings']['authentication_keys']['access_key'] );
		$strPublicKey = $arrInput['aal_settings']['authentication_keys']['access_key'];
		if ( strlen( $strPublicKey ) != 20 ) {
			
			$arrErrors['authentication_keys']['access_key'] = __( 'The Access Key ID must consist of 20 characters.', 'amazon-auto-links' ) . ' ';
			$fVerified = false;
			
		}
		
		// Access Secret Key must be 40 characters.
		$arrInput['aal_settings']['authentication_keys']['access_key_secret'] = trim( $arrInput['aal_settings']['authentication_keys']['access_key_secret'] );
		$strPrivateKey = $arrInput['aal_settings']['authentication_keys']['access_key_secret'];
		if ( strlen( $strPrivateKey ) != 40 ) {
			
			$arrErrors['authentication_keys']['access_key_secret'] = __( 'The Secret Access Key must consist of 40 characters.', 'amazon-auto-links' ) . ' ';
			$fVerified = false;
			
		}
		
		// An invalid value is found.
		if ( ! $fVerified ) {
		
			// Set the error array for the input fields.
			$this->setFieldErrors( $arrErrors );
			$this->setSettingNotice( __( 'There was an error in your input.', 'amazon-auto-links' ) );
			return $arrOldInput;
			
		}			
	
		// Test authentication - browse the Books node in amazon.com.
		$oAmazonAPI = new AmazonAutoLinks_ProductAdvertisingAPI( 'com', $strPublicKey, $strPrivateKey );
		if ( ! $oAmazonAPI->test() ) {
			
			$arrErrors['authentication_keys']['access_key'] = __( 'Sent Value', 'amazon-auto-links' ) . ': ' . $strPublicKey;
			$arrErrors['authentication_keys']['access_key_secret'] = __( 'Sent Value', 'amazon-auto-links' ) . ': ' . $strPrivateKey;			
			$this->setFieldErrors( $arrErrors );
			$this->setSettingNotice( __( 'Failed authentication.', 'amazon-auto-links' ) );
			$arrOldInput;
			
		}
// AmazonAutoLinks_Debug::logArray( $arrInput );
		

		return $arrInput;
		
	}
	
	public function validation_aal_settings_misc( $arrInput, $arrOldInput ) {
		
// AmazonAutoLinks_Debug::logArray( $arrInput );		

		// Sanitize text inputs
		// [aal_settings] => Array
				// [form_options] => Array
						// [allowed_html_tags] 	
		$arrInput['aal_settings']['form_options']['allowed_html_tags'] = trim( AmazonAutoLinks_Utilities::trimDelimitedElements( $arrInput['aal_settings']['form_options']['allowed_html_tags'], ',' ) );
		return $arrInput;
	}
	
	public function validation_aal_settings_general( $arrInput, $arrOldInput ) {
		
		// Sanitize text inputs
		foreach( $arrInput['aal_settings']['product_filters']['black_list'] as &$str1 )
			$str1 = trim( AmazonAutoLinks_Utilities::trimDelimitedElements( $str1, ',' ) ); 
		foreach( $arrInput['aal_settings']['product_filters']['white_list'] as &$str2 ) 
			$str2 = trim( AmazonAutoLinks_Utilities::trimDelimitedElements( $str2, ',' ) );			
			
		// Sanitize the query key.
		$arrInput['aal_settings']['query']['cloak'] = AmazonAutoLinks_Utilities::sanitizeCharsForURLQueryKey( $arrInput['aal_settings']['query']['cloak'] );
		
		
/* 	  [aal_settings] => Array
        (
            [product_filters] => Array
                (
                    [white_list] => Array
                        (
                            [asin] => 
                            [title] => 
                            [description] => 
                        )

                    [black_list] => Array
                        (
                            [asin] => 
                            [title] => 
                            [description] => 
                        )

                )

            [support] => Array
                (
                    [rate] => 10
                    [review] => 0
                )

            [query] => Array
                (
                    [cloak] => productlink
                    [submit_general] => Save Changes
                )

        )	 */
// AmazonAutoLinks_Debug::logArray( $arrInput );					
		return $arrInput;
		
	}
	
		
	public function validation_aal_settings_reset( $arrInput, $arrOldInput ) {

		if ( isset( $arrInput['aal_settings']['caches']['clear_caches'] ) && $arrInput['aal_settings']['caches']['clear_caches'] ) {
			AmazonAutoLinks_Transients::cleanTransients( 'AAL' );
			$this->setSettingNotice( __( 'The caches have been cleared.', 'amazon-auto-links' ) );			
		}

		return $arrOldInput;	// no need to update the options.
		
	}

	public function load_aal_settings_support() {
		
		$this->setAdminNotice( __( 'Please select your preferences.', 'amazon-auto-links' ), 'updated' );
		$this->showInPageTabs( false, 'aal_settings' );
	
	}
	
	public function validation_aal_settings_support( $arrInput, $arrOldInput ) {
		

		return $arrInput;
		
	}
	
	/**
	 * The v1 Option Importer page(tab)
	 */
	public function load_aal_settings_import_v1_options() {
		
		if ( ! isset( $_GET['bounce_url'] ) ) return;
		
		$strBounceURL = get_transient( $_GET['bounce_url'] );	// AAL_BounceURL_Importer
		
// AmazonAutoLinks_Debug::logArray( $strBounceURL );
		// $strBounceURL = $this->oEncode->decode( $_GET['bounce_url'] );

		// If the Dismiss link is selected, 
		if ( isset( $_GET['action'] ) && $_GET['action'] == 'dismiss' ) {
			$this->oOption->arrOptions['aal_settings']['import_v1_options']['dismiss'] = true;
			$this->oOption->save();
			die( wp_redirect( $strBounceURL ) );
			
		} 
		
		$oImportV1Options = new AmazonAutoLinks_ImportV1Options;
		
		$arrV1Options = get_option( 'amazonautolinks' );		
		if ( $arrV1Options === false ) {
			$this->oOption->arrOptions['aal_settings']['import_v1_options']['dismiss'] = true;
			$this->oOption->save();			
			die( wp_redirect( $strBounceURL . "&aal-option-upgrade=not-found" ) );
		}
	
		$intRemained = $this->oOption->getRemainedAllowedUnits();			
// AmazonAutoLinks_Debug::logArray( "remained allowed number of units: " . $intRemained );		
		if ( $intRemained > 0 ) {
			
			// Import units and general options and delete the option from the database
			$oImportV1Options->importGeneralSettings( $arrV1Options['general'] );
			$intCount = $oImportV1Options->importUnits( $arrV1Options['units'] );
			
			// Delete the old options from the database.
// delete_option( 'amazonautolinks' );
			
			$this->oOption->arrOptions['aal_settings']['import_v1_options']['dismiss'] = true;
			$this->oOption->save();
			if ( $intCount )
				die( wp_redirect( $strBounceURL . "&aal-option-upgrade=succeed&count={$intCount}" ) );
			else 
				die( wp_redirect( $strBounceURL . "&aal-option-upgrade=failed" ) );
		}
		
		// Means it's free version and the old version has more than the allowed units.
		// In this case, just import the remained allowed number of units and leave them and do not delete the v1's old options from the database.
		$oImportV1Options->importGeneralSettings( $arrV1Options['general'] );
		$arrV1Units = array_slice( $arrV1Options['units'], 0, $intRemained );

		$intCount = $oImportV1Options->importUnits( $arrV1Units );
		$this->oOption->arrOptions['aal_settings']['import_v1_options']['dismiss'] = true;
		$this->oOption->save();
		if ( $intCount )
			die( wp_redirect( $strBounceURL . "&aal-option-upgrade=partial&count={$intCount}" ) );
		else 
			die( wp_redirect( $strBounceURL . "&aal-option-upgrade=failed" ) );
		
	}
	
	
	/**
	 * The global page load
	 * 
	 */
	public function load_AmazonAutoLinks_AdminPage() {

		// Check the support rate and ads visibility
		if ( 
			! ( isset( $_GET['tab'], $_GET['bounce_url'] ) && $_GET['tab'] == 'support' )
			&& ! $this->oOption->arrOptions['aal_settings']['support']['agreed']
			&& $this->oOption->isSupportMissing()
		) {
			
			// $strCurrentURL = $this->oEncode->encode( AmazonAutoLinks_WPUtilities::getCurrentAdminURL() )
			$strBounceURL = htmlspecialchars_decode( AmazonAutoLinks_WPUtilities::getCurrentAdminURL() );
			$strBounceURL = str_replace( 'tab=support', '', $strBounceURL );		// prevent infinite redirects			;
			$strBounceURL = remove_query_arg( 'aal-option-upgrade', $strBounceURL );
			set_transient( 'AAL_BounceURL', $strBounceURL, 60*10 );		
			wp_redirect( admin_url( 'edit.php?post_type=' . AmazonAutoLinks_Commons::PostTypeSlug . '&page=aal_settings&tab=support&bounce_url=AAL_BounceURL' ) );
		
		}

		// Check the v1 options exist and redirect to the v1 options importer.
		if ( 
			! ( isset( $_GET['tab'], $_GET['bounce_url'] ) && ( $_GET['tab'] == 'import_v1_options' || $_GET['tab'] == 'support' ) )
			&& ! $this->oOption->arrOptions['aal_settings']['import_v1_options']['dismiss']
			&& false !== get_option( 'amazonautolinks' )
		) {
			// $strCurrentURL = $this->oEncode->encode( AmazonAutoLinks_WPUtilities::getCurrentAdminURL() );	
			$strBounceURL = htmlspecialchars_decode( AmazonAutoLinks_WPUtilities::getCurrentAdminURL() );
			$strBounceURL = str_replace( 'tab=import_v1_options', '', $strBounceURL );		// prevent infinite redirects
			set_transient( 'AAL_BounceURL_Importer', $strBounceURL, 60*10 );
			$this->setAdminNotice( 
				sprintf( 
					__( 'Please upgrade the options of previous versions of the plugin by clicking <a href="%1$s">here</a>.', 'amazon-auto-links' )
					. ' ' . __( 'Before you do it, please <strong>back up</strong> the database.', 'amazon-auto-links' )
					. ' ' . __( 'Dismiss this message by clicking <a href="%2$s">here</a>.', 'amazon-auto-links' ),
					admin_url( 'edit.php?post_type=' . AmazonAutoLinks_Commons::PostTypeSlug . '&page=aal_settings&tab=import_v1_options&bounce_url=AAL_BounceURL_Importer' ),
					admin_url( 'edit.php?post_type=' . AmazonAutoLinks_Commons::PostTypeSlug . '&page=aal_settings&tab=import_v1_options&action=dismiss&bounce_url=AAL_BounceURL_Importer' )
				),
				'error'	
			);			
			return;
		}
			
		// Check v1 option importer messages
		if ( isset( $_GET['aal-option-upgrade'] ) ) {
			
			switch( $_GET['aal-option-upgrade'] ) {
				case 'not-found' :   
					$this->setAdminNotice( __( 'Could not find the options to import.', 'amazon-auto-links' ), 'error' );
				break;
				case 'succeed' :   
					$this->setAdminNotice( sprintf( __( 'Options have been imported. ( %1$s unit(s) )', 'amazon-auto-links' ), $_GET['count'] ), 'updated' );
				break;
				case 'partial' :   
					$this->setAdminNotice( sprintf( __( 'Options been partially imported. ( %1$s unit(s) )', 'amazon-auto-links' ), $_GET['count'] ), 'error' );
				break;				
				case 'failed' :
					$this->setAdminNotice( __( 'No unit was imported.', 'amazon-auto-links' ), 'error' );
				break;
				
			}
		}

	}
	
	/**
	 * The global validation task.
	 */
	public function validation_AmazonAutoLinks_AdminPage( $arrInput, $arrOldInput ) {
		
		// Deal with the reset button.
		// [option key][page slug][section][field]
		if ( isset( $_POST[ AmazonAutoLinks_Commons::AdminOptionKey ]['aal_settings']['reset_settings']['options_to_delete'] ) ) {
			
			$arrReset = $_POST[ AmazonAutoLinks_Commons::AdminOptionKey ]['aal_settings']['reset_settings']['options_to_delete'];
			if ( $arrReset['all'] )
				return array();	// this will save an empty array in the option.
			if ( $arrReset['general'] )
				unset( $arrInput['aal_settings'] );	// removes the element named 'aal_settings' from the options array
			if ( $arrReset['template'] )
				unset( $arrInput['arrTemplates'] ); // removes the element named 'arrTemplates' from the options array
	
		}
		
		// Manually set the support rate and ad visibility. 
		// this should be done in the global validation callback, not in validation_{pageslug}_{tab} as modified values in that method will be lost when merged with the global one.
		if ( isset( $_POST['tab'] ) && $_POST['tab'] == 'support' )
			foreach ( $arrInput['aal_settings']['initial_support'] as $strKey => $vValue  )
				$arrInput['aal_settings']['support'][ preg_replace( '/^initial_/', '', $strKey ) ] = $vValue;

		return $arrInput;
		
	}
	
	
	/*
	 * The Template page
	 */ 
	public function load_aal_templates() {	// load_ + {page slug}

		// For the list table bulk actions. The WP_List_Table class does not set the post type query string in the redirected page.
		// if ( 
			// ( isset( $_POST['post_type'] ) && $_POST['post_type'] == AmazonAutoLinks_Commons::PostTypeSlug )	// the form is submitted 
			// && ( ! isset( $_GET['post_type'] ) )	// and post_type query string is not in the url
			// && ( isset( $_GET['page'] ) && $_GET['page'] == 'templates' ) // and the page is the template listing table page,
		// )
			// die( wp_redirect( add_query_arg( array( 'post_type' => AmazonAutoLinks_Commons::PostTypeSlug ) + $_GET, admin_url( $GLOBALS['pagenow'] )  ) ) );

		$oTemplate = $GLOBALS['oAmazonAutoLinks_Templates'];		
		$this->oTemplateListTable = new AmazonAutoLinks_ListTable( $oTemplate->getActiveTemplates() + $oTemplate->getUploadedTemplates() );
		$this->oTemplateListTable->process_bulk_action();
		
	}		
	public function do_aal_templates_table() {	// do_ + page slug + tab slug
			
		$this->oTemplateListTable->prepare_items();
		?>
        <form id="template-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : 'aal_templates'; ?>" />
            <input type="hidden" name="tab" value="<?php echo isset( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : 'table'; ?>" />
            <input type="hidden" name="post_type" value="<?php echo isset( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : AmazonAutoLinks_Commons::PostTypeSlug; ?>" />
            <!-- Now we can render the completed list table -->
            <?php $this->oTemplateListTable->display() ?>
        </form>		
		<?php
				
	}
	public function do_aal_templates_get() {
		
		echo "<p>" . sprintf( __( 'Want your template to be listed here? Send the file to %1$s.', 'amazon-auto-links' ), 'wpplugins@michaeluno.jp' ) . "</p>";
		$oExtensionLoader = new AmazonAutoLinks_ListExtensions();
		$arrFeedItems = $oExtensionLoader->fetchFeed( 'http://feeds.feedburner.com/AmazonAutoLinksTemplates' );
		if ( empty( $arrFeedItems ) ) {
			echo "<h3>" . __( 'No extension has been found.', 'amazon-auto-links' ) . "</h3>";
			return;
		}
		$oExtensionLoader->printColumnOutput( $arrFeedItems );

	}


	
	/*
	 * Extension page
	 */ 
	public function do_before_extensions() {	// do_before_ + page slug
		$this->showPageTitle( false );
	}
	public function do_extensions_get_extensions() {
				
		$oExtensionLoader = new AmazonAutoLinks_Extensions();
		$arrFeedItems = $oExtensionLoader->fetchFeed( 'http://feeds.feedburner.com/MiunosoftAmazonAutoLinksExtension' );
		if ( empty( $arrFeedItems ) ) {
			echo "<h3>" . __( 'No extension has been found.', 'amazon-auto-links' ) . "</h3>";
			return;
		}
		
		$arrOutput = array();
		$intMaxCols = 4;
		$this->arrColumnInfo = $this->arrColumnInfoDefault;
		foreach( $arrFeedItems as $strTitle => $arrItem ) {
			
			// Increment the position
			$this->arrColumnInfo['numCurrColPos']++;
			
			// Enclose the item buffer into the item container
			$strItem = '<div class="' . $this->arrColumnOption['strClassAttrCol'] 
				. ' amazon_auto_links_col_element_of_' . $intMaxCols . ' '
				. ' amazon_auto_links_extension '
				. ( ( $this->arrColumnInfo['numCurrColPos'] == 1 ) ?  $this->arrColumnOption['strClassAttrFirstCol']  : '' )
				. '"'
				. '>' 
				. '<div class="amazon_auto_links_extension_item">' 
					. "<h4>{$arrItem['strTitle']}</h4>"
					. $arrItem['strDescription'] 
					. "<div class='get-now'><a href='{$arrItem['strLink']}' target='_blank' rel='nofollow'>" 
						. "<input class='button button-secondary' type='submit' value='" . __( 'Get it Now', 'amazon-auto-links' ) . "' />"
					. "</a></div>"
				. '</div>'
				. '</div>';	
				
			// If it's the first item in the row, add the class attribute. 
			// Be aware that at this point, the tag will be unclosed. Therefore, it must be closed somewhere. 
			if ( $this->arrColumnInfo['numCurrColPos'] == 1 ) 
				$strItem = '<div class="' . $this->arrColumnOption['strClassAttrRow']  . '">' . $strItem;
		
			// If the current column position reached the set max column, increment the current position of row
			if ( $this->arrColumnInfo['numCurrColPos'] % $intMaxCols == 0 ) {
				$this->arrColumnInfo['numCurrRowPos']++;		// increment the row number
				$this->arrColumnInfo['numCurrColPos'] = 0;		// reset the current column position
				$strItem .= '</div>';  // close the section(row) div tag
				$this->arrColumnInfo['fIsRowTagClosed'] = 	True;
			}		
			
			$arrOutput[] = $strItem;
		
		}
		
		// if the section(row) tag is not closed, close it
		if ( ! $this->arrColumnInfo['fIsRowTagClosed'] ) $arrOutput[] .= '</div>';	
		$this->arrColumnInfo['fIsRowTagClosed'] = true;
		
		// enclose the output in the group tag
		$strOut = '<div class="' . $this->arrColumnOption['strClassAttr'] . ' '
				.  $this->arrColumnOption['strClassAttrGroup'] . ' '
				. '"'
				// . ' style="min-width:' . 200 * $intMaxCols . 'px;"'
				. '>'
				. implode( '', $arrOutput )
				. '</div>';
		
		echo '<div class="amazon_auto_links_extension_container">' . $strOut . '</div>';
		
	}
	
	/*
	 * The Help Page
	 */
	public function do_before_aal_help() {	// do_before_ + {page slug}
		
		include_once( AmazonAutoLinks_Commons::$strPluginDirPath . '/library/wordpress-plugin-readme-parser/parse-readme.php' );
		$this->oWPReadMe = new WordPress_Readme_Parser;
		$this->arrWPReadMe = $this->oWPReadMe->parse_readme( AmazonAutoLinks_Commons::$strPluginDirPath . '/readme.txt' );
		
	}
	public function do_aal_help_install() {		// do_ + page slug + _ + tab slug
		echo $this->arrWPReadMe['sections']['installation'];
	}	
	public function do_aal_help_faq() {		// do_ + page slug + _ + tab slug
		echo $this->arrWPReadMe['sections']['frequently_asked_questions'];
	}
	public function do_aal_help_notes() {		// do_ + page slug + _ + tab slug
		
		include_once( AmazonAutoLinks_Commons::$strPluginDirPath . '/library/simple_html_dom.php' ) ;

		$html = str_get_html( $this->arrWPReadMe['remaining_content'] );
		
		$html->find( 'h3', 0 )->outertext = '';
		$html->find( 'h3', 1 )->outertext = '';
		
		$toc = '';
		$last_level = 0;

		foreach($html->find( 'h4,h5,h6' ) as $h){	// original: foreach($html->find('h1,h2,h3,h4,h5,h6') as $h
			$innerTEXT = trim($h->innertext);
			$id =  str_replace(' ','_',$innerTEXT);
			$h->id= $id; // add id attribute so we can jump to this element
			$level = intval($h->tag[1]);

			if($level > $last_level)
				$toc .= "<ol>";
			else{
				$toc .= str_repeat('</li></ol>', $last_level - $level);
				$toc .= '</li>';
			}

			$toc .= "<li><a href='#{$id}'>{$innerTEXT}</a>";

			$last_level = $level;
		}

		$toc .= str_repeat('</li></ol>', $last_level);
		$html_with_toc = $toc . "<hr>" . $html->save();		
		
		echo $html_with_toc;
		
	}	
	
	/*
	 * The About page
	 */
	public function do_before_aal_about() {		// do_before_ + {page slug}

		include_once( AmazonAutoLinks_Commons::$strPluginDirPath . '/library/wordpress-plugin-readme-parser/parse-readme.php' );
		$this->oWPReadMe = new WordPress_Readme_Parser;
		$this->arrWPReadMe = $this->oWPReadMe->parse_readme( AmazonAutoLinks_Commons::$strPluginDirPath . '/readme.txt' );
	
	}
	public function do_aal_about_features() {		// do_ + page slug + _ + tab slug
		echo $this->arrWPReadMe['sections']['description'];
	}
	public function do_aal_about_change_log() {		// do_ + page slug + _ + tab slug
		echo "<p>" . sprintf( __( 'The other versions of Amazon Auto Links can be downloaded from <a href="%1$s">this page</a>.', 'amazon-auto-links' ), 'http://wordpress.org/plugins/amazon-auto-links/developers/' ) . "</p>";
		echo $this->arrWPReadMe['sections']['changelog'];
	}
	public function do_aal_about_get_pro() {
		
		$strCheckMark = AmazonAutoLinks_Commons::getPluginURL( '/image/checkmark.gif' );
		$strDeclineMark = AmazonAutoLinks_Commons::getPluginURL( '/image/declinedmark.gif' );
		$strAvailable = __( 'Available', 'amazon-auto-links' );
		$strUnavailable = __( 'Unavailable', 'amazon-auto-links' );
		$strImgAvailable = "<img class='feature-available' title='{$strAvailable}' alt='{$strAvailable}' src='{$strCheckMark}' />";
		$strImgUnavailable = "<img class='feature-unavailable' title='{$strUnavailable}' alt='{$strUnavailable}' src='{$strDeclineMark}' />";
		
	?>
		<h3><?php _e( 'Get Pro Now!', 'amazon-auto-links' ); ?></h3>
		<p><?php _e( 'Please consider upgrading to the pro version if you like the plugin and want more useful features, which includes unlimited numbers of categories, units, and items, and more!', 'amazon-auto-links' ); ?></p>
		<?php $this->printBuyNowButton(); ?>
		<h3><?php _e( 'Supported Features', 'amazon-auto-links' ); ?></h3>
		<div class="get-pro">
			<table class="aal-table" cellspacing="0" cellpadding="10">
				<tbody>
					<tr class="aal-table-head">
						<th>&nbsp;</th>
						<th><?php _e( 'Standard', 'amazon-auto-links' ); ?></th>
						<th><?php _e( 'Pro', 'amazon-auto-links' ); ?></th>
					</tr>
					<tr class="aal-table-row">
						<td><?php _e( 'Image Size', 'amazon-auto-links' ); ?></td>
						<td><?php echo $strImgAvailable; ?></td>
						<td><?php echo $strImgAvailable; ?></td>
					</tr>
					<tr class="aal-table-row">
						<td><?php _e( 'Black and White List', 'amazon-auto-links'); ?></td>
						<td><?php echo $strImgAvailable; ?></td>
						<td><?php echo $strImgAvailable; ?></td>
					</tr>
					<tr class="aal-table-row">
						<td><?php _e( 'Sort Order', 'amazon-auto-links' ); ?></td>
						<td><?php echo $strImgAvailable; ?></td>
						<td><?php echo $strImgAvailable; ?></td>
					</tr>
					<tr class="aal-table-row">
						<td><?php _e( 'Direct Link Bonus', 'amazon-auto-links' ); ?></td>
						<td><?php echo $strImgAvailable; ?></td>
						<td><?php echo $strImgAvailable; ?></td>
					</tr>
					<tr class="aal-table-row">
						<td><?php _e( 'Insert in Posts and Feeds', 'amazon-auto-links' ); ?></td>
						<td><?php echo $strImgAvailable; ?></td>
						<td><?php echo $strImgAvailable; ?></td>
					</tr>
					<tr class="aal-table-row">
						<td><?php _e( 'Widget', 'amazon-auto-links' ); ?></td>
						<td><?php echo $strImgAvailable; ?></td>
						<td><?php echo $strImgAvailable; ?></td>
					</tr>	
					<tr class="aal-table-row">
						<td><?php _e( 'Max Number of Items to Show', 'amazon-auto-links' ); ?></td>
						<td>10</td>
						<td><strong><?php _e( 'Unlimited', 'amazon-auto-links' ); ?></strong></td>
					</tr>
					<tr class="aal-table-row">
						<td><?php _e( 'Max Number of Categories Per Unit', 'amazon-auto-links' ); ?></td>
						<td>3</td>
						<td><strong><?php _e( 'Unlimited', 'amazon-auto-links' ); ?></strong></td>
					</tr>
					<tr class="aal-table-row">
						<td><?php _e( 'Max Number of Units', 'amazon-auto-links' ); ?></td>
						<td>3</td>
						<td><strong><?php _e( 'Unlimited', 'amazon-auto-links' ); ?></strong></td>
					</tr>		
					<tr class="aal-table-row">
						<td><?php _e( 'Export and Import Units', 'amazon-auto-links' ); ?></td>
						<td><?php echo $strImgUnavailable; ?></td>
						<td><?php echo $strImgAvailable; ?></td>
					</tr>						
					<tr class="aal-table-row">
						<td><?php _e( 'Exclude Sub Categories', 'amazon-auto-links' ); ?></td>
						<td><?php echo $strImgUnavailable; ?></td>
						<td><?php echo $strImgAvailable; ?></td>
					</tr>					
					<tr class="aal-table-row">
						<td><?php _e( 'Multiple Columns', 'amazon-auto-links' ); ?></td>
						<td><?php echo $strImgUnavailable; ?></td>
						<td><?php echo $strImgAvailable; ?></td>
					</tr>						
					<tr class="aal-table-row">
						<td><?php _e( 'Advanced Search Options', 'amazon-auto-links' ); ?></td>
						<td><?php echo $strImgUnavailable; ?></td>
						<td><?php echo $strImgAvailable; ?></td>
					</tr>							
				</tbody>
			</table>
		</div>	
		<h4><?php	_e( 'Max Number of Items to Show', 'amazon-auto-links' ); ?></h4>
		<p><?php	_e( 'Get pro for unlimited items to show.', 'amazon-auto-links' ); ?></p>		
		<h4><?php	_e( 'Max Number of Categories Per Unit', 'amazon-auto-links' ); ?></h4>
		<p><?php	_e( 'Get pro for unlimited categories to set up!', 'amazon-auto-links' ); ?></p>		
		<h4><?php	_e( 'Max Number of Units', 'amazon-auto-links' ); ?></h4>
		<p><?php	_e( 'Get pro for unlimited units so that you can put ads as many as you want.', 'amazon-auto-links' ); ?></p>		
		
		<?php 
			$this->printBuyNowButton(); 
		
	}
		protected function printBuyNowButton() {	
			$strLink='http://en.michaeluno.jp/amazon-auto-links/amazon-auto-links-pro';
			?>
			<div class="get-now-button">
				<a href="<?php echo $strLink; ?>?lang=<?php echo ( WPLANG ? WPLANG : 'en' ); ?>" title="<?php _e( 'Get Now!', 'amazon-auto-links' ) ?>">
					<img src="<?php echo AmazonAutoLinks_Commons::getPluginURL( '/image/buynowbutton.gif' ); ?>" />
				</a>
			</div>	
			<?php
		}
	
	public function do_aal_about_contact() {
		include( AmazonAutoLinks_Commons::$strPluginDirPath . '/text/about.txt' );
	}
	
	/*
	 * The Debug page
	 * */
	 public function do_aal_debug() {
			
		echo "<h3>Current URL</h3>";	
		echo $strCurrentURL = add_query_arg( $_GET, admin_url( $GLOBALS['pagenow'] ) );
		echo "<h3>Modified URL</h3>";	
		$arrQuery = array( 'post_type' => 'hello' ) + $_GET;
		unset( $arrQuery['post_type'] );
		echo add_query_arg( $arrQuery, admin_url( $GLOBALS['pagenow'] ) );		
			
		echo "<h3>V1 Options</h3>";	
		
		$arrV1Options = get_option( 'amazonautolinks' );
		unset( $arrV1Options['tab100'] );
		unset( $arrV1Options['tab101'] );
		unset( $arrV1Options['tab200'] );
		unset( $arrV1Options['tab201'] );
		unset( $arrV1Options['tab202'] );
		unset( $arrV1Options['tab203'] );
		unset( $arrV1Options['tab300'] );
		unset( $arrV1Options['editunit'] );
		unset( $arrV1Options[ 0 ] );
		 
		$this->oDebug->dumpArray( $arrV1Options );
			
	 }
	
}