<?php
/**
 *	Deals with WordPress transients.
 * 
 * @package     Amazon Auto Links
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl	http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		2.0.0 
 */

final class AmazonAutoLinks_Transients {

	public static function cleanTransients( $vPrefixes=array() ) {	// for the deactivation hook.

		// This method also serves for the deactivation callback and in that case, an empty value is passed to the first parameter.		
		$arrPrefixes = empty( $arrPrefixes ) ? array( AmazonAutoLinks_Commons::TransientPrefix ) : ( array ) $vPrefixes;
		
		foreach( $arrPrefixes as $strPrefix ) {
			$GLOBALS['wpdb']->query( "DELETE FROM `" . $GLOBALS['table_prefix'] . "options` WHERE `option_name` LIKE ( '_transient_%{$strPrefix}%' )" );
			$GLOBALS['wpdb']->query( "DELETE FROM `" . $GLOBALS['table_prefix'] . "options` WHERE `option_name` LIKE ( '_transient_timeout_%{$strPrefix}%' )" );
		}
	
	}
	
}