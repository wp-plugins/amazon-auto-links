<?php
/**
 * Handles plugin's shortcodes.
 * 
 * @package     Amazon Auto Links
 * @copyright   Copyright (c) 2013, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		2.0.0
*/
abstract class AmazonAutoLinks_Shortcode_ {

	public function __construct( $strShortCode ) {
						
		// Add the shortcode.
		add_shortcode( $strShortCode, array( $this, 'getOutput' ) );
		
	}
	
	public function getOutput( $arrArgs ) {
			
		// $this->oFetch = isset( $this->oFetch ) ? $this->oFetch : new AmazonAutoLinks_Fetch();
		
		// if ( isset( $arrArgs['id'] ) || isset( $arrArgs['ids'] ) ) 
			// return $this->oFetch->getTweetsOutput( $arrArgs );
		// else if ( isset( $arrArgs['tag'] ) || isset( $arrArgs['tags'] ) ) 
			// return $this->oFetch->getTweetsOutputByTag( $arrArgs );
			
		$oUnits = new AmazonAutoLinks_Units( $arrArgs );
// var_dump( $arrArgs );		
		return $oUnits->getOutput();
		
		
	}	

}