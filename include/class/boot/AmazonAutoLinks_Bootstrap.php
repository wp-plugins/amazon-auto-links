<?php
/**
 * Handles the initial set-up for the plugin.
 * 
 * @package     Amazon Auto Links
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl	http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		2.0.0
 * @action		aal_action_setup_transients			The cron event hook that sets up transients.
*/

final class AmazonAutoLinks_Bootstrap extends AmazonAutoLinks_Bootstrap_Base {
	
	function __construct( $sPluginFilePath ) {
		
		$this->_fIsAdmin = is_admin();
		$this->_sFilePath = $sPluginFilePath;
		
		// 0. Define constants.
		$this->_defineConstants();
		
		// 1. Set global variables.
		$this->_setGlobals();
		
		// 2. Set up auto-load classes.
		$this->_loadClasses( $this->_sFilePath );
		
		// 3. Load the class that holds the common plugin info.
		AmazonAutoLinks_Commons::setUpStaticProperties( $this->_sFilePath );
		
		// 4. Set up activation hook.
		register_activation_hook( $this->_sFilePath, array( $this, '_replyToDoWhenPluginActivates' ) );
		
		// 5. Set up deactivation hook.
		register_deactivation_hook( $this->_sFilePath, array( $this, '_replyToDoWhenPluginDeactivates' ) );
		// register_uninstall_hook( $this->_sFilePath, 'self::_replyToDoWhenPluginUninstalled' );
		
		// 6. Set up localization.
		$this->_localize();

		// 7. Schedule to call start-up functions after all the plugins are loaded.
		add_action( 'plugins_loaded', array( $this, '_replyToLoadPlugin' ), 999, 1 );

		// 8. Plugin requirement check. 
		$this->_checkRequirements();
		
	}	
	
	/**
	 * Loads the plugin full components.
	 * 
	 * The callback method triggered with the 'plugins_loaded' hook.
	 * 
	 */
	public function _replyToLoadPlugin() {
		
		// All the necessary classes have been already loaded.
		
		// 0. Load Necessary libraries
		include_once( AmazonAutoLinks_Commons::$strPluginDirPath . '/include/library/admin-page-framework-for-amazon-auto-links.php' );

		// 1. Include functions.
		include_once( AmazonAutoLinks_Commons::$strPluginDirPath . '/include/function/AmazonAutoLinks.php' );
		
		// 2. Option Object
		$GLOBALS['oAmazonAutoLinks_Option'] = new AmazonAutoLinks_Option( AmazonAutoLinks_Commons::AdminOptionKey );

		// 3. Templates
		$GLOBALS['oAmazonAutoLinks_Templates'] = new AmazonAutoLinks_Templates;		
		$GLOBALS['oAmazonAutoLinks_Templates']->loadFunctionsOfActiveTemplates();
		add_action( 'wp_enqueue_scripts', array( $GLOBALS['oAmazonAutoLinks_Templates'], 'enqueueActiveTemplateStyles' ) );
		if ( $this->_fIsAdmin ) {
			$GLOBALS['oAmazonAutoLinks_Templates']->loadSettingsOfActiveTemplates();
		}
			
		// 4. Admin pages
		if ( $this->_fIsAdmin ) {
			new AmazonAutoLinks_AdminPage( AmazonAutoLinks_Commons::AdminOptionKey, $this->_sFilePath );		
		}
		
		// 5. Post Type - It should not use "if ( is_admin() )" for the this class because posts of custom post type can be accessed from front-end regular pages.
		new AmazonAutoLinks_PostType( AmazonAutoLinks_Commons::PostTypeSlug, null, $this->_sFilePath ); 	// post type slug
		new AmazonAutoLinks_PostType_AutoInsert( AmazonAutoLinks_Commons::PostTypeSlugAutoInsert, null, $this->_sFilePath ); 	// post type slug
	
		// 6. Meta Boxes
		if ( $this->_fIsAdmin ) {
			$this->_registerMetaBoxes();
		}
				
		// 7. Shortcode - e.g. [amazon_auto_links id="143"]
		new AmazonAutoLinks_Shortcode( AmazonAutoLinks_Commons::ShortCode );	// amazon_auto_links
		new AmazonAutoLinks_Shortcode( 'amazonautolinks' );	 // backward compatibility with v1.x. This will be deprecated later at some point.
			
		// 8. Widgets
		add_action( 'widgets_init', 'AmazonAutoLinks_WidgetByID::registerWidget' );
		// add_action( 'widgets_init', 'AmazonAutoLinks_WidgetByTag::registerWidget' );
				
		// 9. Auto-insert		
		new AmazonAutoLinks_AutoInsert;
		
		// 10. Events
		new AmazonAutoLinks_Event;	
		
		// 11. MISC
		if ( $this->_fIsAdmin ) {
			$GLOBALS['oAmazonAutoLinksUserAds'] = isset( $GLOBALS['oAmazonAutoLinksUserAds'] ) ? $GLOBALS['oAmazonAutoLinksUserAds'] : new AmazonAutoLinks_UserAds;
		}
		
// AmazonAutoLinks_Debug::logArray( $GLOBALS['arrAmazonAutoLinks_Classes'] );	


	}
	
		/**
		 * Registers the plugin meta boxes
		 * 
		 * @since			2.0.3
		 */
		private function _registerMetaBoxes() {
			
			$GLOBALS['strAmazonAutoLinks_UnitType'] = AmazonAutoLinks_Option::getUnitType();
			$strUnitType = $GLOBALS['strAmazonAutoLinks_UnitType'];
			$bIsUpdatinUnit = ( empty( $_GET ) && $GLOBALS['pagenow'] == 'post.php' );	// when saving the meta data, the GET array is empty
			if ( $strUnitType == 'category' || $bIsUpdatinUnit ) {	
				new AmazonAutoLinks_MetaBox_CategoryOptions(
					'amazon_auto_links_category_unit_options_meta_box',	// meta box ID
					__( 'Category Unit Options', 'amazon-auto-links' ),		// meta box title
					array( AmazonAutoLinks_Commons::PostTypeSlug ),	// post, page, etc.
					'normal',
					'default'
				);	
				new AmazonAutoLinks_MetaBox_Categories;
			}
			// Do not use  else here for the meta box saving process
			if ( $strUnitType == 'tag' || $bIsUpdatinUnit ) {
				new AmazonAutoLinks_MetaBox_TagOptions(
					'amazon_auto_links_tag_unit_options_meta_box',	// meta box ID
					__( 'Tag Unit Options', 'amazon-auto-links' ),		// meta box title
					array( AmazonAutoLinks_Commons::PostTypeSlug ),	// post, page, etc.
					'normal',
					'default'
				);					
			}
			// Do not use  else here for the meta box saving process
			if ( $strUnitType == 'search' || $bIsUpdatinUnit ) {
				new AmazonAutoLinks_MetaBox_SearchOptions(
					'amazon_auto_links_search_unit_options_meta_box',	// meta box ID
					__( 'Search Unit Options', 'amazon-auto-links' ),		// meta box title
					array( AmazonAutoLinks_Commons::PostTypeSlug ),	// post, page, etc.
					'normal',
					'default'			
				);	
				new AmazonAutoLinks_MetaBox_SearchOptions_Advanced(
					'amazon_auto_links_advanced_search_unit_options_meta_box',	// meta box ID
					__( 'Advanced Search Options', 'amazon-auto-links' ),		// meta box title
					array( AmazonAutoLinks_Commons::PostTypeSlug ),	// post, page, etc.
					'normal',
					'default'			
				);	
			}
			// Do not use else here for the meta box saving process
			if ( $strUnitType == 'item_lookup' || $bIsUpdatinUnit ) {	// the second condition is for when updating the unit.
				new AmazonAutoLinks_MetaBox_ItemLookupOptions(
					'amazon_auto_links_item_lookup_unit_options_meta_box',	// meta box ID
					__( 'Item Look-up Options', 'amazon-auto-links' ),		// meta box title
					array( AmazonAutoLinks_Commons::PostTypeSlug ),	// post, page, etc.
					'normal',
					'default'			
				);
				new AmazonAutoLinks_MetaBox_ItemLookupOptions_Advanced(
					'amazon_auto_links_advanced_item_lookup_unit_options_meta_box',	// meta box ID
					__( 'Advanced Item Look-up Options', 'amazon-auto-links' ),		// meta box title
					array( AmazonAutoLinks_Commons::PostTypeSlug ),	// post, page, etc.
					'normal',
					'default'				
				);
			}			
			// Do not use else here for the meta box saving process
			if ( $strUnitType == 'similarity_lookup' || $bIsUpdatinUnit ) {	// the second condition is for when updating the unit.
				new AmazonAutoLinks_MetaBox_SimilarityLookupOptions(
					'amazon_auto_links_similarity_lookup_unit_options_meta_box',	// meta box ID
					__( 'Similarity Look-up Options', 'amazon-auto-links' ),		// meta box title
					array( AmazonAutoLinks_Commons::PostTypeSlug ),	// post, page, etc.
					'normal',
					'default'			
				);
				new AmazonAutoLinks_MetaBox_SimilarityLookupOptions_Advanced(
					'amazon_auto_links_advanced_similarity_lookup_unit_options_meta_box',	// meta box ID
					__( 'Advanced Similarity Look-up Options', 'amazon-auto-links' ),		// meta box title
					array( AmazonAutoLinks_Commons::PostTypeSlug ),	// post, page, etc.
					'normal',
					'default'				
				);
			}				
			
			
			new AmazonAutoLinks_MetaBox_Template(
				'amazon_auto_links_template_meta_box',	// meta box ID
				__( 'Template', 'amazon-auto-links' ),		// meta box title
				array( AmazonAutoLinks_Commons::PostTypeSlug ),	// post, page, etc.
				'normal',	// side 
				'default'
			);
			
			new AmazonAutoLinks_MetaBox_Misc;		
			
		}

	
}