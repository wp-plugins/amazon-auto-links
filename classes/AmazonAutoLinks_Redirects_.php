<?php
/**
 * @package     Amazon Auto Links
 * @copyright   Copyright (c) 2013, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.1.3
 * @description	Redirects the user to the Amazon store, mainly used for the url cloak feature.
*/
class AmazonAutoLinks_Redirects_ {
	
	function __construct( &$oOption ) {
		
		// the option array
		$this->oOption = $oOption;		
	
		$this->Redirect();
	}
	function Redirect() {
		
		// since v1.0.9
		// check a cloak query is passed in the url
		$arrOptions = $this->oOption->arrOptions;	// since v1.1.3
		$strCloakQuery = empty( $arrOptions['general']['cloakquery']) ? 'productlink' : $arrOptions['general']['cloakquery'];
		if ( isset( $_GET[$strCloakQuery] ) ) {

			// if so, redirect to the actual url
			$oAALfuncs = new AmazonAutoLinks_Helper_Functions( AMAZONAUTOLINKSKEY );
			
			wp_redirect( $oAALfuncs->urldecrypt( $_GET[$strCloakQuery] ) );
			exit;		
		}
	}
}