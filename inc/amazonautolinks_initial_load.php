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
	new AmazonAutoLinks_Admin( $oAALOptions );

	// Contents Hooks
	// this registers the method, RegisterHooks, of the AmazonAutoLinks_Contents class
	new AmazonAutoLinks_Contents( AMAZONAUTOLINKSKEY, $oAALOptions );

	// URL redirects for URL cloaking
	new AmazonAutoLinks_Redirects( $oAALOptions );

	// Load actions to hook events for WordPress cron jobs
	add_action( 'init', array( new AmazonAutoLinks_Events( $oAALOptions ), "LoadEvents" ) );	// 'AmazonAutoLinks_Events');

	// Widgets
	add_action( 'widgets_init', 'AmazonAutoLinks_Widget::RegisterWidget' );

	// Plugin Requirements
	// do not use register_activation_hook(); deactivate_plugins() will fail for some reasons.
	new AmazonAutoLinks_Requirements( '5.1.2', '3.0', array( 'mb_language' ) );
		
}

// Initial setup
register_activation_hook( AMAZONAUTOLINKSPLUGINFILE, 'AmazonAutoLinks_SetupTransients' );

// Clean up transients upon plugin deactivation
register_deactivation_hook( AMAZONAUTOLINKSPLUGINFILE, 'AmazonAutoLinks_CleanTransients' );

function AmazonAutoLinks_CleanTransients() {
	
	// delete transients
	global $wpdb, $table_prefix;
	$wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ( '_transient%_feed_%' )" );
	$wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ( '_transient%_aal_%' )" );
	$wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ( '_transient_timeout%_aal_%' )" );
	
	AmazonAutoLinks_WPUnscheduleEventsByRegex( '/aal_feed_.+/' );
}
		
	
function AmazonAutoLinks_WPUnscheduleEventsByRegex( $strEventNameNeedle ) {

	// this function removes registered WP Cron events by a specified event name which matches the given regex pattern.
	$arrCronEvents = _get_cron_array();	
	foreach( $arrCronEvents as $nTimeStamp => $arrEvent ) {
		// array_keys() returns an array holding the keys and preg_grep() searches if a matching key exists. 
		// If exists, with casting (int), it returns true; otherwise, false.
		$bIsDelete = (int) preg_grep( $strEventNameNeedle, array_keys( $arrCronEvents[$nTimeStamp] ) );
		if ( $bIsDelete ) unset( $arrCronEvents[$nTimeStamp] );
	}
	_set_cron_array( $arrCronEvents );
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
			eval( "class $strClassName extends $strClassNamePro {}" );
		else if ( class_exists( $strFileName ) && !class_exists( $strClassName ) ) 	// if the pro class does not exist and the given class name has not been declared,
			eval( "class $strClassName extends $strFileName {}" );
			
	} 
}

function AmazonAutoLinks_SetupTransients() {	
	wp_schedule_single_event( time(), 'aal_setuptransients' );		
}
