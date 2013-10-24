<?php
/**
 * Methods used for debugging
 * 
	
	
 * @package     Amazon Auto Links
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl	http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.0.0
 * 
	
*/

final class AmazonAutoLinks_Debug {

	static public function dumpArray( $arr, $strFilePath=null ) {
		
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) return;
		
		echo self::getArray( $arr, $strFilePath );
		
	}

	static public function getArray( $arr, $strFilePath=null ) {
		
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) return;
		
		if ( $strFilePath ) 
			self::logArray( $arr, $strFilePath );			
			
		// esc_html() has a bug that breaks with complex HTML code.
		return "<div><pre class='dump-array'>" . htmlspecialchars( print_r( $arr, true ) ) . "</pre><div>";	
		
	}
	static public function logArray( $arr, $strFilePath=null ) {
		
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) return;
					
		file_put_contents( 
			$strFilePath ? $strFilePath : dirname( __FILE__ ) . '/array_log.txt', 
			date( "Y/m/d H:i:s" ) . PHP_EOL
			. print_r( $arr, true ) . PHP_EOL . PHP_EOL
			, FILE_APPEND 
		);					
							
	}
	
	static public function echoMemoryUsage() {
		
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) return;
				   
		echo self::getMemoryUsage() . "<br/>";
		
	} 		

    static public function getMemoryUsage( $intType=1 ) {	// since 1.1.4
       
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) return;
	   
		$intMemoryUsage = $intType == 1 ? memory_get_usage( true ) : memory_get_peak_usage( true );
       
        if ( $intMemoryUsage < 1024 ) return $intMemoryUsage . " bytes";
        
		if ( $intMemoryUsage < 1048576 ) return round( $intMemoryUsage/1024,2 ) . " kilobytes";
        
        return round( $intMemoryUsage / 1048576,2 ) . " megabytes";
           
    } 		
	
	static public function getOption( $strKey ) {

		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) return;
		
		$oOption = & $GLOBALS['oAmazonAutoLinks_Option'];		
		if ( ! isset( $oOption->arrOptions[ $strKey ] ) ) return;
		
		die( self::getArray( $oOption->arrOptions[ $strKey ] ) );
		
	}
}