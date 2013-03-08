<?php
/**
 * @package     Amazon Auto Links
 * @copyright   Copyright (c) 2013, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.2.0
 * @description	Checks the specified requirements and if it fails, it deactivates the plugin.
*/
class AmazonAutoLinks_Requirements_ {

	// Properties
	protected $strPHPver = '';
	protected $strWPver = '';
	protected $arrFuncs = array();
	protected $arrPluginData = array();
	protected $strAdminNotice = '';	// admin notice
	protected $bSufficient = true;	// tells whether it suffices for all the requirements.
	
	function __construct( $strPHPver="5.1.2", $strWPver="3.0", $arrFuncs=array(), $arrClasses=array() ) {
		
		$this->strPHPver = $strPHPver;
		$this->strWPver = $strWPver;
		$this->arrFuncs = ( array ) $arrFuncs;
		$this->arrClasses = ( array ) $arrClasses;
		$this->arrScriptInfo = debug_backtrace();
		
		if ( !function_exists( 'get_plugin_data' )  )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			
		// $this->arrPluginData = get_plugin_data( $this->arrScriptInfo[0]['file'], false );
		$this->arrPluginData = get_plugin_data( AMAZONAUTOLINKSPLUGINFILE, false );
		
		$this->strAdminNotice = '<strong>' . $this->arrPluginData['Name'] . '</strong><br />';

		$this->CheckRequirements();
		// add_action( 'admin_init', array( $this, 'CheckRequirements' ) );
	}

	function CheckRequirements() {
		/*
		 * Do not call this function with register_activation_hook(). For some reasons, it won't trigger the deactivate_plugins() function.
		 * */
		 
		global $wp_version;
		
		if ( !$this->IsSufficientPHPVersion( $this->strPHPver ) ) {
			$this->bSufficient = False;
			$this->strAdminNotice .=  
				__( 'The plugin requires the following PHP version or higher:', 'amazon-auto-links' )
				. ' <strong>' . $this->strPHPver . '</strong>'
				. ' ' . __( 'Your PHP version is:', 'amazon-auto-links' ) 
				. ' <strong>' . phpversion() . '</strong>'
				. '<br />';
		}

		if ( !$this->IsSufficientWordPressVersion( $this->strWPver ) ) {
			$this->bSufficient = False;
			$this->strAdminNotice .=  
				__( 'The plugin requires the following WordPress version or higher:', 'amazon-auto-links' )
				. ' <strong>' . $this->strWPver . '</strong>'
				. ' ' . __( 'Your WordPress version is:', 'amazon-auto-links' ) 
				. ' <strong>' . $wp_version . '</strong>'
				. '<br />';
		}
		
		// 'The plugin requires the PHP <a href="http://www.php.net/manual/en/mbstring.installation.php">mb string extension</a> installed on the server.
		if ( count( $arrNonFoundFuncs = $this->CheckFunctions( $this->arrFuncs ) ) > 0 ) {
			$this->bSufficient = False;
			$this->strAdminNotice .= 
				__( 'The following function(s) is/are missing on your server to run this plugin. Please consult your host, not the plugin developer, to enable them. : ', 'amazon-auto-links' )
				. ' <strong>' .  implode( ", ", $arrNonFoundFuncs ) . '</strong>'
				. '<br />';
		}
		if ( count( $arrNonFoundClasses = $this->CheckClasses( $this->arrClasses ) ) > 0 ) {
			$this->bSufficient = False;
			$this->strAdminNotice .= 
				__( 'The following class(es) is/are missing on your server to run this plugin. Please consult your host, not the plugin developer, to enable them. : ', 'amazon-auto-links' )
				. ' <strong>' .  implode( ", ", $arrNonFoundClasses ) . '</strong>'
				. '<br />';
		}	
		
		if ( !$this->bSufficient ) {

			add_action( 'admin_notices', array( $this, 'ShowAdminNotice' ) );	
			deactivate_plugins( AMAZONAUTOLINKSPLUGINFILE );

		}
	}
	
	function ShowAdminNotice() {
		echo '<div class="error"><p>' 
			. $this->strAdminNotice 
			. '<strong>' . __( 'Deactivating the plugin.', 'amazon-auto-links' ) . '</strong>'
			. '</p></div>';
	}
	
	protected function IsSufficientPHPVersion( $strPHPver ) {
		
		if ( version_compare( phpversion(), $strPHPver, ">=" ) ) return true;
			
	}
	protected function IsSufficientWordPressVersion( $strWPver ) {
		
		global $wp_version;
		if ( version_compare( $wp_version, $strWPver, ">=" ) ) return true;
		
	}
	protected function CheckClasses( $arrClasses ) {
		
		$arrClasses = $arrClasses ? $arrClasses : $this->arrClasses;
		$arrNonExistentClasses = array();
		foreach( $arrClasses as $strClass ) 
			if ( ! class_exists( $strClass ) )
				$arrNonExistentClasses[] = $strClass;
		return $arrNonExistentClasses;
		
	}
	protected function CheckFunctions( $arrFuncs ) {
		
		// returns non-existent functions as array.
		$arrFuncs = $arrFuncs ? $arrFuncs : $this->arrFuncs;
		$arrNonExistentFuncs = array();
		foreach( $arrFuncs as $strFunc ) 
			if ( ! function_exists( $strFunc ) ) 
				$arrNonExistentFuncs[] = $strFunc;
		return $arrNonExistentFuncs;
		
	}	
}