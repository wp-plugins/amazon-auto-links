<?php
/*
	this is the initial load script called from the main plugin file
*/

// Register Classes - this must be be done before using classes defined in this plugin
AmazonAutoLinks_RegisterClasses();

// instantiate the option class first so that the option object can be shared with other classes, which presumably consumes less memory.
// in other words, there is no need to instantiate the option class in each class.
$oAALOptions = new AmazonAutoLinks_Options( AMAZONAUTOLINKSKEY );

// Admin Pages
// this registers the method, RegisterHooks, of the AmazonAutoLinks_Admin class
add_action( 'plugins_loaded', array( new AmazonAutoLinks_Admin( $oAALOptions ), "RegisterHooks" ) );		

// Contents Hooks
// this registers the method, RegisterHooks, of the AmazonAutoLinks_Contents class
add_action( 'plugins_loaded', array( new AmazonAutoLinks_Contents( AMAZONAUTOLINKSKEY, $oAALOptions ), "RegisterHooks" ) );

// URL redirects for URL cloaking
add_action( 'plugins_loaded', array( new AmazonAutoLinks_Redirects( $oAALOptions ), "Redirect" ) );

// Load actions to hook events for WordPress cron jobs
add_action( 'init', array( new AmazonAutoLinks_Events( $oAALOptions ), "LoadEvents" ) );	// 'AmazonAutoLinks_Events');

// Widgets
// todo: find a way to avoid using create_function() 
add_action( 'widgets_init', create_function( '', 'register_widget( "AmazonAutoLinks_Widget" );' ) );

// Plugin Requirements & initial setup
register_deactivation_hook( AMAZONAUTOLINKSPLUGINFILE, 'AmazonAutoLinks_Requirements' );
register_deactivation_hook( AMAZONAUTOLINKSPLUGINFILE, 'AmazonAutoLinks_SetupTransients' );

// Clean up transients upon plugin deactivation
register_deactivation_hook( AMAZONAUTOLINKSPLUGINFILE, 'AmazonAutoLinks_CleanTransients' );

// debug
   // add_filter('admin_footer_text', 'AmazonAutoLinks_MemoryUsage'); 
    function AmazonAutoLinks_MemoryUsage () {

		$size = memory_get_peak_usage();
		$precision = 0;
		$base = log($size) / log(1024);
		$suffixes = array('', 'k', 'M', 'G', 'T');   
		$displaysize = round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];

    	echo '<p>' . $displaysize . '</p>';

    }

 



function AmazonAutoLinks_CleanTransients() {
	
	// delete transients
	global $wpdb;
	$wpdb->query( "DELETE FROM `wp_options` WHERE `option_name` LIKE ('_transient%_feed_%')" );
	$wpdb->query( "DELETE FROM `wp_options` WHERE `option_name` LIKE ('_transient%_aal_%')" );
	$wpdb->query( "DELETE FROM `wp_options` WHERE `option_name` LIKE ('_transient_timeout%_aal_%')" );
	
}
function AmazonAutoLinks_CleanOptions($key='') {
	
	// delete options
	delete_option( AMAZONAUTOLINKSKEY );				// used for the main option data
	delete_option('amazonautolinks_catcache_events');	// used for category cache events
	delete_option('amazonautolinks_userads');			// used for the user ads
	
	// initialize it
	$arr = array();
	if ($key != '') {
		$arr = get_option(AMAZONAUTOLINKSKEY);
		$arr[$key] = array();
	}
	update_option(AMAZONAUTOLINKSKEY, $arr);

	// delete transients
	global $wpdb;
	$wpdb->query( "DELETE FROM `wp_options` WHERE `option_name` LIKE ('_transient%_aal_%')" );
	$wpdb->query( "DELETE FROM `wp_options` WHERE `option_name` LIKE ('_transient_timeout%_aal_%')" );
	
	// $wpdb->query( "DELETE FROM `wp_options` WHERE `option_name` LIKE ('_transient%_feed_%')" );	// this is for feed cache 
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
	$options = get_option(AMAZONAUTOLINKSKEY);
	
	// as of v1.0.7, the option key is the ID of the unit so parse them to match the 'unitlabel' element to the passed unit label
	foreach($options['units'] as $strUnitID => $arrUnitOption) {
		if ($arrUnitOption['unitlabel'] == $strUnitLabel) {
			$oAAL = new AmazonAutoLinks_Core($options['units'][$strUnitID]);
			echo $oAAL->fetch();		
			return;
		}
	}
	
	// here will be read if there is not match
	echo AMAZONAUTOLINKSPLUGINNAME . ' ';
	_e('Error: No such unit label exists.', 'amazonautolinks');
	return;

}

function AmazonAutoLinks_RegisterClasses() {
		
	/*  
		This function reads class files in wp-include/plugins/[this-plugin-path]/classes 
		and registers them to be auto-loaded so that require() or include() in each class file is no longer necessary.
		After that, it defines new clesses based on the regstered class names. 
		The class files must have a class definition with the file name without its file extension.
		This function should be triggered by the plugins_loaded() function; otherwise, the "header already sent" error may occur during 
		the plugin activation.
	*/

	// Register standard classes
	$strAALDirPath = AMAZONAUTOLINKSPLUGINDIR . '/classes/';
	$arrAALPHPfiles = array_map(create_function( '$a', 'return basename($a, ".php");' ), glob($strAALDirPath . '*.php'));
	spl_autoload_register(
		create_function('$class_name', '
			if (in_array($class_name, ' . var_export($arrAALPHPfiles, true) . ')) 
				include(' . var_export($strAALDirPath, true) . ' . $class_name . ".php");
		')
	);

	// Register pro classes
	$strAALDirPathPro = AMAZONAUTOLINKSPLUGINDIR . '/classes_pro/';
	if (file_exists($strAALDirPathPro)) {
		$arrAALPHPfilesPro = array_map(create_function( '$a', 'return basename($a, ".php");' ), glob($strAALDirPathPro . '*.php'));
		spl_autoload_register(
			create_function('$class_name', '
				if (in_array($class_name, ' . var_export($arrAALPHPfilesPro, true) . ')) 
					include(' . var_export($strAALDirPathPro, true) . ' . $class_name . ".php");
			')
		);
		$arrAALPHPfiles = array_merge($arrAALPHPfiles, $arrAALPHPfilesPro);
	}
	
	// Define classes 
	$strClassNamePrefix = 'AmazonAutoLinks_';	// define a prefix of file name to avoid executing harmful code in file names.
	foreach ($arrAALPHPfiles as $strFileName) {
		
		// apply security filters
		if (substr($strFileName, 0, strlen($strClassNamePrefix)) != $strClassNamePrefix)
			continue;	// filter out files which don't start with the prefix
		if (preg_match("/[#;\(\){}]/", $strFileName))
			continue;	// if the file name contains characters looking like PHP code, skip it
			
		// $strFileName: either ending with _ or Pro e.g. AmazonAutoLinks_Admin_ / AmazonAutoLinks_Admin_Pro
		if (substr($strFileName, -4) == '_Pro') {			// case, Pro
			$strClassNamePro = $strFileName;				// leave it as it is, e.g. AmazonAutoLinks_Admin_Pro -> AmazonAutoLinks_Admin_Pro
			$strClassName = substr($strFileName, 0, -4);	// removes the last four caracters. e.g. AmazonAutoLinks_Admin_Pro -> AmazonAutoLinks_Admin
		} else {											// case, Standard
			$strClassNamePro = $strFileName . 'Pro';		// adds Pro, e.g. amazonautolinks_admin_ -> amazonautolinks_admin_pro
			$strClassName = substr($strFileName, 0, -1);	// removes the last one character. e.g. amazonautolinks_admin_ -> amazonautolinks_admin		
		}
		
		// delare classes 
		if (class_exists($strClassNamePro) && !class_exists($strClassName)) 		
			eval("class $strClassName extends $strClassNamePro {};");	
		else if (class_exists($strFileName) && !class_exists($strClassName)) 
			eval("class $strClassName extends $strFileName {};");		
		
	} 
}

// requirements for this plugin to work in PHP version >= 5.1.2
function AmazonAutoLinks_Requirements() {
	global $wp_version, $wpdb;
	$plugin = AMAZONAUTOLINKSPLUGINFILEBASENAME;	//plugin_basename( __FILE__ );
	$plugin_data = get_plugin_data( AMAZONAUTOLINKSPLUGINFILE, false );
	$numPHPver='5.1.2';		// required php version
	$numWPver='3.0';		// required WordPress version
	$bSufficient = True;
	$strMsg = '';
	if ( version_compare(phpversion(), $numPHPver, "<" ) ) {
		$bSufficient = False;
		$strMsg .= $plugin_data['Name'] . ': ' . __('The plugin requires the following PHP version or higher:', 'amazonautolinks')  
		. ' ' . $numPHPver . ' ' . __('Your PHP version is:', 'amazonautolinks') . phpversion() 
		. ' ' . __('Deactivating the plugin.', 'amazonautolinks') . '<br />';
	}	
	if ( version_compare($wp_version, $numWPver, "<" ) ) {
		$bSufficient = False;
		$strMsg .=  $plugin_data['Name'] . ': ' . __('The plugin requires the following WordPress version or higher:', 'amazonautolinks') 
		. ' ' . $numWPver . ' ' . __('Your WordPress version is:', 'amazonautolinks') . $wp_version . ' ' 
		. __('Deactivating the plugin.', 'amazonautolinks') ;
	}
	if (!$bSufficient && is_plugin_active($plugin)) {
		echo '<div class="error"><p>' . $strMsg . '</p></div>';
		deactivate_plugins( $plugin );
	}
}

function AmazonAutoLinks_SetupTransients() {	
	$o = new AmazonAutoLinks_UserAds( AMAZONAUTOLINKSKEY, new AmazonAutoLinks_Options( AMAZONAUTOLINKSKEY ) );
	$o->SetupTransients();	
	$o->check_user_countrycode();
}