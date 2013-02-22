<?php
/**
 * @package     Amazon Auto Links
 * @copyright   Copyright (c) 2013, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.2.2
 * @description	Creates a log for the plugin activity.
*/
class AmazonAutoLinks_DebugLog_ {

	function __construct( &$arrLogs, $bIsEnabled ) {
		$this->arrLogs = $arrLogs;
		$this->bIsLogEnabled =  $bIsEnabled;
	}
	function Append( $vLog, $strFunc='' ) {
		if ( ! $this->bIsLogEnabled ) return;	
		
		$arrArgs = is_array( $vLog ) ? $vLog : array( 'message' => $vLog );
			
		$arrDebug = debug_backtrace();
		$arrDefault = array( 
			'message' => 'called',
			'time' => date( 'Y-m-d h:i:s A' ),
			'color' => '#000000',
			);
		$arrArgs = wp_parse_args( $arrArgs, $arrDefault + $arrDebug[0] );
		/*	supported keys
		 * 	e.g.
			[message] => ''
			[time] => 2013 02 12 07:52:12 AM
			[color]
			[file] => ***.php
			[line] => 6
			[function] => Info
			[class] => Debug
			[object] =>
			[type] =>
			[args] =>
		 * */
		 
		$arrArgs['function'] = $strFunc ? $strFunc : $arrArgs['function'];
		$this->arrLogs[uniqid()] = $arrArgs;
		// array_unshift( $this->arrLogs, $arrArgs );
// echo 'Appended:<pre>' . print_r( $this->arrLogs, true ) . '</pre>';		
	}
	function File($strMsg, $strFunc='', $strFileName='log.html') {

		// for debugging
		if ($strFunc=='') $strFunc = __FUNCTION__;
		$strPath = AMAZONAUTOLINKSPLUGINDIR . '/' . $strFileName;
		if (!file_exists($strPath)) file_put_contents($strPath, '');	// create a file if not exist
		$strLog = date('Y m d h:i:s A') . ': ' . $strFunc . ': ' . $strMsg . '<br />' . PHP_EOL;
		$arrLines = file($strPath);
		$arrLines = array_reverse($arrLines);
		array_push($arrLines, $strLog);
		$arrLines = array_reverse($arrLines);
		$arrLines = array_splice($arrLines, 0, 100);   // extract the first 100 elements
		file_put_contents($strPath, implode('', $arrLines));	
	}

}
