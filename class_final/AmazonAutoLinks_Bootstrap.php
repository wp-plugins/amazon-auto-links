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

final class AmazonAutoLinks_Bootstrap {
	
	function __construct( $strPluginFilePath ) {
	
		$this->strFilePath = $strPluginFilePath;
		
		// 0. Define constants.
		$this->defineConstants();
		
		// 1. Set global variables.
		$this->setGlobals();
		
		// 2. Set up auto-load classes.
		$this->loadClasses( $this->strFilePath );
		
		// 3. Load the class that holds the common plugin info.
		AmazonAutoLinks_Commons::setUpStaticProperties( $this->strFilePath );
		
		// 4. Set up activation hook.
		register_activation_hook( $this->strFilePath, array( $this, 'doWhenPluginActivates' ) );
		
		// 5. Set up deactivation hook.
		register_deactivation_hook( $this->strFilePath, array( $this, 'doWhenPluginDeactivates' ) );
		// register_uninstall_hook( $this->strFilePath, 'self::doWhenPluginUninstalled' );
		
		// 6. Set up localization.
		$this->localize();

		// 7. Schedule to call start-up functions after all the plugins are loaded.
		add_action( 'plugins_loaded', array( $this, 'loadPlugin' ), 999, 1 );

		// 8. Plugin requirement check. 
		$this->checkRequirements();
		
	}	
	
	private function defineConstants() {
		
		define( "AMAZONAUTOLINKSPLUGINFILEBASENAME", plugin_basename( $this->strFilePath ) );	// for backward compatibility.
		// if ( ! defined( 'MINUTE_IN_SECONDS' ) ) define( 'MINUTE_IN_SECONDS', 60 );
		// if ( ! defined( 'HOUR_IN_SECONDS' ) ) define( 'HOUR_IN_SECONDS',   60 * MINUTE_IN_SECONDS );
		// if ( ! defined( 'DAY_IN_SECONDS' ) ) define( 'DAY_IN_SECONDS',    24 * HOUR_IN_SECONDS   );
		// if ( ! defined( 'WEEK_IN_SECONDS' ) ) define( 'WEEK_IN_SECONDS',    7 * DAY_IN_SECONDS    );
		// if ( ! defined( 'YEAR_IN_SECONDS' ) ) define( 'YEAR_IN_SECONDS',  365 * DAY_IN_SECONDS    );	
	
	}
	
	private function setGlobals() {
		
		$GLOBALS['oAmazonAutoLinks_Option'] = null;	// stores the option object
		$GLOBALS['oAmazonAutoLinks_Templates'] = null;	// stores the template object
		
		// Stores custom registering class paths
		$GLOBALS['arrAmazonAutoLinks_Classes'] = isset( $GLOBALS['arrAmazonAutoLinks_Classes'] ) && is_array( $GLOBALS['arrAmazonAutoLinks_Classes'] ) ? $GLOBALS['arrAmazonAutoLinks_Classes'] : array();
				
		// Stores request url's transient info.
		$GLOBALS['arrAmazonAutoLinks_APIRequestURIs'] = array();
	
		// Stores the current unit type in admin pages.
		$GLOBALS['strAmazonAutoLinks_UnitType'] = '';	//
		
		// ASINs blacklist 
		$GLOBALS['arrBlackASINs'] = array();
		
	}
	
	private function loadClasses( $strFilePath ) {
		
		$strPluginDir =  dirname( $strFilePath );
		
		// Auto-loads classes placed in the class_final folder.
		if ( ! class_exists( 'AmazonAutoLinks_RegisterClasses' ) ) 
			include_once( $strPluginDir . '/class_final/AmazonAutoLinks_RegisterClasses.php' );		
		
		// Register finalized classes right away.
		$oRC = new AmazonAutoLinks_RegisterClasses( $strPluginDir . '/class_final' );
		$oRC->registerClasses();
		
		// Schedule to register regular classes when all the plugins are loaded. This allows other scripts to modify the loading class files.
		add_action( 'plugins_loaded', array( new AmazonAutoLinks_RegisterClasses( $strPluginDir . '/class', $GLOBALS['arrAmazonAutoLinks_Classes'] ), 'registerClasses' ) );
		
	}

	public function doWhenPluginActivates() {
		
		// Schedule transient set-ups
		wp_schedule_single_event( time(), 'aal_action_setup_transients' );		
		
	}
	
	public function doWhenPluginDeactivates() {
		AmazonAutoLinks_Transients::cleanTransients();
	}	
	
	public static function doWhenPluginUninstalled() {
		AmazonAutoLinks_Transients::cleanTransients();	
	}
	
	private function localize() {
		
		load_plugin_textdomain( 
			AmazonAutoLinks_Commons::TextDomain, 
			false, 
			dirname( plugin_basename( $this->strFilePath ) ) . '/language/'
		);
		
		if ( is_admin() ) 
			load_plugin_textdomain( 
				'admin-page-framework', 
				false, 
				dirname( plugin_basename( $this->strFilePath ) ) . '/language/'
			);		
		
	}		
	
	public function loadPlugin() {
		
		// All the necessary classes have been already loaded.
		
		// 0. Load Necessary libraries
		include_once( AmazonAutoLinks_Commons::$strPluginDirPath . '/library/admin-page-framework-for-amazon-auto-links.php' );

		// 1. Include functions.
		include_once( AmazonAutoLinks_Commons::$strPluginDirPath . '/function/AmazonAutoLinks.php' );
		
		// 2. Option Object
		$GLOBALS['oAmazonAutoLinks_Option'] = new AmazonAutoLinks_Option( AmazonAutoLinks_Commons::AdminOptionKey );

		// 3. Templates
		$GLOBALS['oAmazonAutoLinks_Templates'] = new AmazonAutoLinks_Templates;		
		$GLOBALS['oAmazonAutoLinks_Templates']->loadFunctionsOfActiveTemplates();
		add_action( 'wp_enqueue_scripts', array( $GLOBALS['oAmazonAutoLinks_Templates'], 'enqueueActiveTemplateStyles' ) );
		if ( is_admin() )
			$GLOBALS['oAmazonAutoLinks_Templates']->loadSettingsOfActiveTemplates();
			
		// 4. Admin pages
		if ( is_admin() ) 
			new AmazonAutoLinks_AdminPage( AmazonAutoLinks_Commons::AdminOptionKey, $this->strFilePath );		
		
		// 5. Post Type
		// Should not use "if ( is_admin() )" for the this class because posts of custom post type can be accessed from front-end regular pages.
		new AmazonAutoLinks_PostType( AmazonAutoLinks_Commons::PostTypeSlug, null, $this->strFilePath ); 	// post type slug
		new AmazonAutoLinks_AutoInsert_PostType( AmazonAutoLinks_Commons::PostTypeSlugAutoInsert, null, $this->strFilePath ); 	// post type slug
	
		// 6. Meta Boxes
		if ( is_admin() ) {
			
			$GLOBALS['strAmazonAutoLinks_UnitType'] = AmazonAutoLinks_Option::getUnitType();
			$strUnitType = $GLOBALS['strAmazonAutoLinks_UnitType'];
			if ( $strUnitType == 'category' || ( empty( $_GET ) && $GLOBALS['pagenow'] == 'post.php' ) ) {	// when saving the meta data, the GET array is empty
				new AmazonAutoLinks_MetaBox_CategoryOptions(
					'amazon_auto_links_category_unit_options_meta_box',	// meta box ID
					__( 'Category Unit Options', 'amazon-auto-links' ),		// meta box title
					array( AmazonAutoLinks_Commons::PostTypeSlug ),	// post, page, etc.
					'normal',
					'default'
				);	
				new AmazonAutoLinks_MetaBox_Categories;
			}
			if ( $strUnitType == 'tag' || ( empty( $_GET ) && $GLOBALS['pagenow'] == 'post.php' ) ) {
				new AmazonAutoLinks_MetaBox_TagOptions(
					'amazon_auto_links_tag_unit_options_meta_box',	// meta box ID
					__( 'Tag Unit Options', 'amazon-auto-links' ),		// meta box title
					array( AmazonAutoLinks_Commons::PostTypeSlug ),	// post, page, etc.
					'normal',
					'default'
				);					
			}
			if ( $strUnitType == 'search' || ( empty( $_GET ) && $GLOBALS['pagenow'] == 'post.php' ) ) {	// the second condition is for when updating the unit.
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
		
			new AmazonAutoLinks_MetaBox_Template(
				'amazon_auto_links_template_meta_box',	// meta box ID
				__( 'Template', 'amazon-auto-links' ),		// meta box title
				array( AmazonAutoLinks_Commons::PostTypeSlug ),	// post, page, etc.
				'side',
				'default'
			);
			
			new AmazonAutoLinks_MetaBox_Misc;
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
		if ( is_admin() )
			$GLOBALS['oFetchTweetsUserAds'] = isset( $GLOBALS['oFetchTweetsUserAds'] ) ? $GLOBALS['oFetchTweetsUserAds'] : new AmazonAutoLinks_UserAds;
		
// AmazonAutoLinks_Debug::logArray( $GLOBALS['arrAmazonAutoLinks_Classes'] );	


	}
	
	/**
	 * Performs plugin requirements check.
	 * 
	 * This is triggered with the admin_init hook. Do not use this with register_activation_hook(), which does not work.
	 * 
	 */	
	private function checkRequirements() {
		
		// Requirement Check
		new AmazonAutoLinks_Requirements( 
			$this->strFilePath,
			array(
				'php' => array(
					'version' => '5.2.4',
					'error' => __( 'The plugin requires the PHP version %1$s or higher.', 'amazon-auto-links' ),
				),
				'wordpress' => array(
					'version' => '3.3',
					'error' => __( 'The plugin requires the WordPress version %1$s or higher.', 'amazon-auto-links' ),
				),
				'functions' => array(
					'mb_substr' => sprintf( __( 'The plugin requires the <a href="%2$s">%1$s</a> to be installed.', 'amazon-auto-links' ), __( 'the Multibyte String library', 'amazon-auto-links' ), 'http://www.php.net/manual/en/book.mbstring.php' ),
					'curl_version' => sprintf( __( 'The plugin requires the %1$s to be installed.', 'amazon-auto-links' ), __( 'the cURL library', 'amazon-auto-links' ) ),
				),
				'classes' => array(
					'DOMDocument' => sprintf( __( 'The DOMDocument class could not be found. The plugin requires the <a href="%1$s">libxml</a> extension to be activated.', 'amazon-auto-links' ), 'http://www.php.net/manual/en/book.libxml.php' ),
					'DomXpath' => sprintf( __( 'The DomXpath class could not be found. The plugin requires the <a href="%1$s">libxml</a> extension to be activated.', 'amazon-auto-links' ), 'http://www.php.net/manual/en/book.libxml.php' ),
				),
				'constants'	=> array(),
			),
			True, 			// if it fails it will deactivate the plugin
			'admin_init'
		);	
	}
	

	/**
	 * Not sure which script uses this.
	 * 
	 */
	public function enqueueStyle() {
		
        // Respects SSL, style.css is relative to the current file
        wp_register_style( 'amazon-auto-links', plugins_url( '/css/style.css', AmazonAutoLinks_Commons::getPluginFilePath() ) );
        wp_enqueue_style( 'amazon-auto-links' );
		
    }		

		
	
}