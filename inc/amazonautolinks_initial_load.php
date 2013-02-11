<?php
/*
	this is the initial load script called from the main plugin file
*/
	
add_action( 'plugins_loaded', 'AmazonAutoLinks_LoadPlugin' );		
function AmazonAutoLinks_LoadPlugin() {

	// Register Classes - this must be be done before using classes defined in this plugin
	AmazonAutoLinks_RegisterClasses();

	// instantiate the option class first so that the option object can be shared with other classes, which presumably consumes less memory.
	// in other words, there is no need to instantiate the option class in each class.
	$oAALOptions = new AmazonAutoLinks_Options( AMAZONAUTOLINKSKEY );

	// Admin Pages
	// this registers the method, RegisterHooks, of the AmazonAutoLinks_Admin class
	$oAALAdmin = new AmazonAutoLinks_Admin( $oAALOptions );
	$oAALAdmin->RegisterHooks();

	// Contents Hooks
	// this registers the method, RegisterHooks, of the AmazonAutoLinks_Contents class
	$oAALContents = new AmazonAutoLinks_Contents( AMAZONAUTOLINKSKEY, $oAALOptions );
	$oAALContents->RegisterHooks();

	// URL redirects for URL cloaking
	$oAALRedirects = new AmazonAutoLinks_Redirects( $oAALOptions );
	$oAALRedirects->Redirect();

	// Load actions to hook events for WordPress cron jobs
	add_action( 'init', array( new AmazonAutoLinks_Events( $oAALOptions ), "LoadEvents" ) );	// 'AmazonAutoLinks_Events');

	// Widgets
	// todo: find a way to avoid using create_function() 
	add_action( 'widgets_init', create_function( '', 'register_widget( "AmazonAutoLinks_Widget" );' ) );

}

// Plugin Requirements & initial setup
register_activation_hook( AMAZONAUTOLINKSPLUGINFILE, 'AmazonAutoLinks_Requirements' );
register_activation_hook( AMAZONAUTOLINKSPLUGINFILE, 'AmazonAutoLinks_SetupTransients' );

// Clean up transients upon plugin deactivation
register_deactivation_hook( AMAZONAUTOLINKSPLUGINFILE, 'AmazonAutoLinks_CleanTransients' );


function AmazonAutoLinks_CleanTransients() {
	
	// delete transients
	global $wpdb, $table_prefix;
	$wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ( '_transient%_feed_%' )" );
	$wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ( '_transient%_aal_%' )" );
	$wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ( '_transient_timeout%_aal_%' )" );
	
}
function AmazonAutoLinks_CleanOptions($key='') {
	
	// delete options
	delete_option( AMAZONAUTOLINKSKEY );				// used for the main option data
	delete_option('amazonautolinks_catcache_events');	// used for category cache events
	delete_option('amazonautolinks_userads');			// used for the user ads
	
	// initialize it
	$arr = array();
	if ($key != '') {
		$arr = get_option( AMAZONAUTOLINKSKEY );
		$arr[$key] = array();
	}
	update_option(AMAZONAUTOLINKSKEY, $arr);

	// delete transients
	global $wpdb, $table_prefix;
	$wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ('_transient%_aal_%')" );
	$wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ('_transient_timeout%_aal_%')" );
	
	// $wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ('_transient%_feed_%')" );	// this is for feed cache 
}

function AmazonAutoLinks_Log($strMsg, $strFunc='', $strFileName='log.html') {

	return; // if you like to see the plugin workings, comment out this line and the below line and you'll find a log file in the plugin directory.
	// if (!in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'))) return;	// if the access is not from localhost, do not process.

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

function AmazonAutoLinks($strUnitLabel) {
	
	// This function is used to embed the Amazon products unit in a theme
	$options = get_option( AMAZONAUTOLINKSKEY );
	
	// as of v1.0.7, the option key is the ID of the unit so parse them to match the 'unitlabel' element to the passed unit label
	foreach($options['units'] as $strUnitID => $arrUnitOption) {
		if ($arrUnitOption['unitlabel'] == $strUnitLabel) {
			$oAAL = new AmazonAutoLinks_Core($options['units'][$strUnitID]);
			echo $oAAL->fetch();		
			return;
		}
	}
	
	// here will be read if there is not match
	echo AMAZONAUTOLINKSPLUGINNAME . ' ' . __( 'Error: No such unit label exists.', 'amazon-auto-links' );
	return;
}

function AmazonAutoLinks_RegisterClasses() {
		
	/*  
		Called from the plugins_loaded hook and the plugin activation hook.
		This function reads class files in wp-include/plugins/[this-plugin-path]/classes 
		and registers them to be auto-loaded so that require() or include() in each class file is no longer necessary.
		After that, it defines new clesses based on the regstered class names. 
		The class files must have a class definition with the file name without its file extension.
	*/

	// Register standard classes
	global $arrAALDirPaths;
	// if it is called from the plugin activation hook, $arrAALDirPaths may not have been created.
	if ( !is_array( $arrAALDirPaths ) || count( $arrAALDirPaths ) == 0 ) $arrAALDirPaths = array( AMAZONAUTOLINKSPLUGINDIR . '/classes/' );


	$arrAALPHPfiles = array();	// holds all including class names
	foreach ( $arrAALDirPaths as $strAALDirPath ) {
		foreach ( array_map( create_function( '$a', 'return basename( $a, ".php" );' ), glob( $strAALDirPath . '*.php' ) ) as $strFileName )
			array_push( $arrAALPHPfiles, $strFileName );
		spl_autoload_register(
			create_function( '$class_name', '
				if ( in_array( $class_name, ' . var_export( $arrAALPHPfiles, true ) . ' ) ) 
					include_once( ' . var_export( $strAALDirPath, true ) . ' . $class_name . ".php" );
			')
		);
	}

// echo '<pre>' . print_r( $arrAALPHPfiles, true ) . '</pre>';	
	
	// Define classes 
	$strClassNamePrefix = 'AmazonAutoLinks_';	// define a prefix of file name to avoid executing harmful code in file names.
	foreach ( $arrAALPHPfiles as $strFileName ) {
		
		// security filters
		if ( substr( $strFileName, 0, strlen( $strClassNamePrefix ) ) != $strClassNamePrefix ) continue; // filter out files which don't start with the prefix		
		if ( preg_match( "/[#;\(\){}]/", $strFileName ) ) continue;	// if the file name contains characters looking like PHP code, skip it
		
		// $strFileName: either ending with _ or Pro e.g. AmazonAutoLinks_Admin_ / AmazonAutoLinks_Admin_Pro
		// $strClassName: the class name to be declared, the one to be used. e.g. AmazonAutoLinks_Admin
		if ( substr( $strFileName, -4 ) == '_Pro' ) {		// case, Pro
			$strClassNamePro = $strFileName;				// leave it as it is, e.g. AmazonAutoLinks_Admin_Pro -> AmazonAutoLinks_Admin_Pro
			$strClassName = substr( $strFileName, 0, -4 );	// removes the last four caracters. e.g. AmazonAutoLinks_Admin_Pro -> AmazonAutoLinks_Admin
		} else {											// case, Standard
			$strClassNamePro = $strFileName . 'Pro';		// adds Pro, e.g. Amazonautolinks_Admin_ -> amazonautolinks_Admin_Pro
			$strClassName = substr( $strFileName, 0, -1 );	// removes the last one character. e.g. Amazonautolinks_Admin_ -> Amazonautolinks_Admin		
		}
		
		// Declare classes 	
		if ( class_exists( $strClassNamePro ) && !class_exists( $strClassName ) ) 
			eval( "class $strClassName extends $strClassNamePro {};" );	// extend the pro class
		else if ( class_exists( $strFileName ) && !class_exists( $strClassName ) ) 	// if the pro class does not exist and the given class name has not been declared,
			eval( "class $strClassName extends $strFileName {};" );		
		
	} 
}

function AmazonAutoLinks_Requirements() {

	// Called from the activation hook.
	// requirements for this plugin to work in PHP version >= 5.1.2
	global $wp_version, $wpdb;
	$plugin = AMAZONAUTOLINKSPLUGINFILEBASENAME;	//plugin_basename( __FILE__ );
	$plugin_data = get_plugin_data( AMAZONAUTOLINKSPLUGINFILE, false );
	$numPHPver = '5.1.2';	// required php version
	$numWPver = '3.0';		// required WordPress version
	$bSufficient = True;
	$strMsg = '';
	if ( version_compare( phpversion(), $numPHPver, "<" ) ) {
		$bSufficient = False;
		$strMsg .= $plugin_data['Name'] . ': ' . __( 'The plugin requires the following PHP version or higher:', 'amazon-auto-links' )  
		. ' ' . $numPHPver . ' ' . __( 'Your PHP version is:', 'amazon-auto-links' ) . phpversion() 
		. ' ' . __( 'Deactivating the plugin.', 'amazon-auto-links' ) . '<br />';
	}	
	if ( version_compare( $wp_version, $numWPver, "<" ) ) {
		$bSufficient = False;
		$strMsg .=  $plugin_data['Name'] . ': ' . __( 'The plugin requires the following WordPress version or higher:', 'amazon-auto-links' ) 
		. ' ' . $numWPver . ' ' . __( 'Your WordPress version is:', 'amazon-auto-links' ) . $wp_version . ' ' 
		. __( 'Deactivating the plugin.', 'amazon-auto-links' ) ;
	}
	if ( !$bSufficient && is_plugin_active( $plugin ) ) {
		echo '<div class="error"><p>' . $strMsg . '</p></div>';
		deactivate_plugins( $plugin );
	}
}

function AmazonAutoLinks_SetupTransients() {	

	// Called from the activation hook. So this should be functional individually.
	if ( !class_exists( 'AmazonAutoLinks_UserAds' ) ) AmazonAutoLinks_RegisterClasses();
	$o = new AmazonAutoLinks_UserAds( AMAZONAUTOLINKSKEY, new AmazonAutoLinks_Options( AMAZONAUTOLINKSKEY ) );
	$o->SetupTransients();	
	// $o->check_user_countrycode();
}