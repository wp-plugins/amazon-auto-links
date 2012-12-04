<?php
class AmazonAutoLinks_Redirects_ {
	
	/*
	 * since v1.1.3
	 * This class is for redirecting urls, mainly used for the url cloak feature.
	 * */
	 
	function __construct($oOptions) {
		
		// the option array
		$this->oAALOptions = $oOptions;		
		
	}
	function Redirect() {
		
		// since v1.0.9
		// check a cloak query is passed in the url
		// $arrOptions = get_option(AMAZONAUTOLINKSKEY);
		$arrOptions = $this->oAALOptions->arrOptions;	// since v1.1.3
		$strCloakQuery = empty($arrOptions['general']['cloakquery']) ? 'productlink': $arrOptions['general']['cloakquery'];
		if (isset($_GET[$strCloakQuery])) {

			// if so, redirect to the actual url
			$oAALfuncs = new AmazonAutoLinks_Helper_Functions(AMAZONAUTOLINKSKEY);
			wp_redirect($oAALfuncs->urldecrypt($_GET[$strCloakQuery]));
			exit;		
		}
	}
}