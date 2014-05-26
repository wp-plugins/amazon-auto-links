<?php
/**
 * The base class of the bootstrap class.
 * 
 * @package     Amazon Auto Links
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl	http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		2.0.5.2
 * @action		task		aal_action_setup_transients			The cron event hook that sets up transients.
 * @filter		apply		aal_filter_classes					Applies to the loading class array.
*/

abstract class AmazonAutoLinks_Bootstrap_Base {
	
	/**
	 * Defines plugin specific constants.
	 */
	protected function _defineConstants() {
		
		define( "AMAZONAUTOLINKSPLUGINFILEBASENAME", plugin_basename( $this->_sFilePath ) );	// for backward compatibility.
	
	}
	
	/**
	 * Declares plugin specific global variables.
	 */
	protected function _setGlobals() {
		
		// Stores the option object
		$GLOBALS['oAmazonAutoLinks_Option'] = null;	
		
		// Stores the template object
		$GLOBALS['oAmazonAutoLinks_Templates'] = null;	
		
		// Stores custom registering class paths
		$GLOBALS['arrAmazonAutoLinks_Classes'] = isset( $GLOBALS['arrAmazonAutoLinks_Classes'] ) && is_array( $GLOBALS['arrAmazonAutoLinks_Classes'] ) ? $GLOBALS['arrAmazonAutoLinks_Classes'] : array();
				
		// Stores request url's transient info.
		$GLOBALS['arrAmazonAutoLinks_APIRequestURIs'] = array();
	
		// Stores the current unit type in admin pages. This will be set in the method that loads meta boxes.
		$GLOBALS['strAmazonAutoLinks_UnitType'] = '';
		
		// ASINs blacklist 
		$GLOBALS['arrBlackASINs'] = array();
		
	}
	
	/**
	 * Register class files to be auto-loaded.
	 */
	protected function _loadClasses( $sFilePath ) {
		
		new AmazonAutoLinks_AutoLoad( dirname( $sFilePath ) . '/include/class/boot' );
		
		// Schedule to register regular classes when all the plugins are loaded. This allows other scripts to modify the loading class files.
		add_action( 'plugins_loaded', array( $this, '_replyToLoadClasses') );
		
	}
		/**
		 * Register class files to be auto-loaded with a delay.
		 */
		public function _replyToLoadClasses() {
						
			// For the backward compatibility. The old versions store elements with the key of file base name including its file extension.
			// Here it sets the key without its file extension.
			$_aAmazonAutoLinksClasses = array();
			foreach( ( array ) $GLOBALS['arrAmazonAutoLinks_Classes'] as $_sBaseName => $_sFilePath ) {
				$_aAmazonAutoLinksClasses[ pathinfo( $_sFilePath, PATHINFO_FILENAME ) ] = $_sFilePath;
			}
			$_aAmazonAutoLinksClasses = apply_filters( 'aal_filter_classes', $_aAmazonAutoLinksClasses );
			
			$_sPluginDir = dirname( $this->_sFilePath );
			$_aExcludeDirs = array(
				$_sPluginDir . '/include/class/boot',
			);
			new AmazonAutoLinks_AutoLoad( $_sPluginDir . '/include/class', $_aAmazonAutoLinksClasses, array( 'is_recursive' => true, 'exclude_dirs' => $_aExcludeDirs ) );

		}

	/**
	 * A callback method triggered when the plugin is activated.
	 */
	public function _replyToDoWhenPluginActivates() {
		
		// Schedule transient set-ups
		wp_schedule_single_event( time(), 'aal_action_setup_transients' );		
		
	}
	
	/**
	 * A callback method triggered when the plugin is deactivated.
	 */
	public function _replyToDoWhenPluginDeactivates() {
		AmazonAutoLinks_Transients::cleanTransients();
	}	
	
	/**
	 * A callback method triggered when the plugin is uninstalled.
	 * @remark			currently not used yet.
	 */
	public static function _replyToDoWhenPluginUninstalled() {
		AmazonAutoLinks_Transients::cleanTransients();	
	}
	
	/**
	 * Registers localization files.
	 */
	protected function _localize() {
		
		load_plugin_textdomain( 
			AmazonAutoLinks_Commons::TextDomain, 
			false, 
			dirname( plugin_basename( $this->_sFilePath ) ) . '/language/'
		);
		
		if ( is_admin() ) 
			load_plugin_textdomain( 
				'admin-page-framework', 
				false, 
				dirname( plugin_basename( $this->_sFilePath ) ) . '/language/'
			);		
		
	}		
		
	/**
	 * Performs plugin requirements check.
	 * 
	 * This is triggered with the admin_init hook. Do not use this with register_activation_hook(), which does not work.
	 * 
	 */	
	protected function _checkRequirements() {
		
		// Requirement Check
		new AmazonAutoLinks_Requirements( 
			$this->_sFilePath,
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
			true, 			// if it fails it will deactivate the plugin
			'admin_init'
		);	

	}
	
}