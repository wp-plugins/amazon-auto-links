<?php
/*
	Plugin Name: Amazon Auto Links
	Plugin URI: http://michaeluno.jp/en/amazon-auto-links
	Description: Generates links of Amazon products just coming out today. You just pick categories and they appear even in JavaScript disabled browsers.
	Version: 1.0.5
	Author: Michael Uno (miunosoft)
	Author URI: http://michaeluno.jp
	Text Domain: amazonautolinks
	Domain Path: /lang
	Requirements: This plugin requires WordPress >= 3.0 and PHP >= 5.1.2
*/

// Define constants
define("AMAZONAUTOLINKSKEY", "amazonautolinks");
define("AMAZONAUTOLINKSPLUGINNAME", "Amazon Auto Links");
define("AMAZONAUTOLINKSPLUGINFILEBASENAME", plugin_basename(__FILE__));

// Register Classes
add_action('plugins_loaded', 'AmazonAutoLinks_RegisterClasses');

// Admin Pages
add_action( 'plugins_loaded', create_function( '', '$oAALAdmin = new AmazonAutoLinks_Admin;' ) );

// Load actions to hook events for Cron jobs
add_action('init', create_function( '', '$oAALEvents = new AmazonAutoLinks_Events;' ));

// Plugin Requirements
add_action('admin_init', 'AmazonAutoLinks_Requirements');

// Widgets
// add_action('widgets_init', 'AmazonAutoLinks_Widgets'); // this is disabled until the blank page issue gets resolved.

// uncomment the following function to clear all options and initialize to the default.
// AmazonAutoLinks_CleanOptions();

function AmazonAutoLinks_CleanOptions($key='') {
	delete_option( AMAZONAUTOLINKSKEY );
	delete_option('amazonautolinks_catcache_events');
	
	$arr = array();
	if ($key != '') {
		$arr = get_option(AMAZONAUTOLINKSKEY);
		$arr[$key] = array();
	}
	update_option(AMAZONAUTOLINKSKEY, $arr);

	global $wpdb;
	$wpdb->query( "DELETE FROM `wp_options` WHERE `option_name` LIKE ('_transient%_aal_%')" );
	$wpdb->query( "DELETE FROM `wp_options` WHERE `option_name` LIKE ('_transient_timeout%_aal_%')" );
	
	// $wpdb->query( "DELETE FROM `wp_options` WHERE `option_name` LIKE ('_transient%_feed_%')" );	// this is for feed cache 
}

function AmazonAutoLinks_Log($strMsg, $strFunc='', $strFileName='log.html') {

	return; // if you like to see the plugin workings, comment out this line and the below line and you'll find a log file in the plugin directory.
	if (!in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'))) return;	// if the access is not from localhost, do not process.

	// for debugging
	if ($strFunc=='') $strFunc = __FUNCTION__;
	$strPath = __DIR__ . '/' . $strFileName;
	if (!file_exists($strPath)) file_put_contents($strPath, '');	// create a file if not exist
	$strLog = date('Y m d h:i:s A') . ': ' . $strFunc . ': ' . $strMsg . '<br />' . PHP_EOL;
	$arrLines = file($strPath);
	$arrLines = array_reverse($arrLines);
	array_push($arrLines, $strLog);
	$arrLines = array_reverse($arrLines);
	$arrLines = array_splice($arrLines, 0, 100);   // extract the first 100 elements
	file_put_contents($strPath, implode('', $arrLines));	
}

// the function used to embed the Amazon products unit in a theme
function AmazonAutoLinks($unitlabel) {
	$options = get_option(AMAZONAUTOLINKSKEY);
	if (!IsSet($options['units'][$unitlabel])) {
		echo AMAZONAUTOLINKSPLUGINNAME . ' ';
		_e('Error: No such unit label exists.', 'amazonautolinks');
		return;
	}	
	$oAAL = new AmazonAutoLinks_Core($options['units'][$unitlabel], $options['general']);
	echo $oAAL->fetch( $oAAL->UrlsFromUnitLabel($unitlabel, $options));
}

function AmazonAutoLinks_RegisterClasses() {
		
	/*  
		This function reads class files in wp-include/plugins/[this-plugin-path]/classes 
		and registers them to be auto-loaded so that require() or include() in each class file is no longer necessary.
		After that, it defines new clesses based on the regstered class names. 
		The class files must have a class definition with the file name without file extension.
		This function should be trigered with the plugins_loaded() function; otherwise, the "header already sent" error may occur during 
		the plugin activation.
	*/

	// Register standard classes
	$strAALDirPath = dirname(__FILE__) . '/classes/';
	$arrAALPHPfiles = array_map(create_function( '$a', 'return basename($a, ".php");' ), glob($strAALDirPath . '*.php'));
	spl_autoload_register(
		create_function('$class_name', '
			if (in_array($class_name, ' . var_export($arrAALPHPfiles, true) . ')) 
				include(' . var_export($strAALDirPath, true) . ' . $class_name . ".php");
		')
	);

	// Register pro classes
	$strAALDirPathPro = dirname(__FILE__) . '/classes_pro/';
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
	$plugin = plugin_basename( __FILE__ );
	$plugin_data = get_plugin_data( __FILE__, false );
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
		$myrows = $wpdb->get_results( "SELECT * FROM wp_options WHERE option_name = 'active_plugins'" );
		print_r($mywors);
		deactivate_plugins( $plugin );
	}
	
}

function AmazonAutoLinks_Widgets() {

	// prepare widgets
	$oAALOptions = new AmazonAutoLinks_Options(AMAZONAUTOLINKSKEY);
	$i = 0;
	foreach($oAALOptions->arrOptions['units'] as $strUnitLabel => $arrUnitOptions) {
		if (empty($arrUnitOptions['widget'])) 
			continue;
					
		// if (empty($arrUnitOptions['id']))		// for backward compatibility. The earlier version of the plugin does not have this key.
			// $arrUnitOptions['id'] = uniqid();		
			
		// if the widget option is true, create a widget for the unit.
		// $strWidgetID =  'AmazonAutoLinks_Widget_' . $arrUnitOptions['id'];
		$strWidgetID =  'aal_' . sha1($strUnitLabel);
		$strDescription = $strUnitLabel;
		$strWidgetTitle = AMAZONAUTOLINKSPLUGINNAME . ': ' . $strUnitLabel;

		/*	
			Currently in the eval() code below, in order to show the unit contents, it instanciates the option class and then passes them to the fetch() method.
			I'm not sure if it is faster to fetch the output before the eval() statement. In this case the option class is already instanciated.
			So no need to instantiate the option object again but may fetch the contents even when the widget is not called, such as in a single view.
			
			// $oAALinWidget = new AmazonAutoLinks_Core($oAALOptions->arrOptions['units'][$strUnitLabel], $oAALOptions->arrOptions["general"]);
			// $strOutput = $oAALinWidget->fetch( $oAALinWidget->UrlsFromUnitLabel($strUnitLabel, $oAALOptions->arrOptions));
		*/

		eval('
			class '  . $strWidgetID . ' extends WP_Widget {
			
				function ' . $strWidgetID . '() {
					$widget_ops = array("classname" => "' . $strWidgetID . '"
										, "description" => "' . $strDescription . '" );
					$this->WP_Widget("' . $strWidgetID . '", "' . $strWidgetTitle . '", $widget_ops);			
				}

				function form($instance) {
					$instance = wp_parse_args( (array) $instance, array( "title" => "" ) );
					$title = $instance["title"];
					echo "<p><label for=\"" . $this->get_field_id("title") . "\">Title: <input class=\"widefat\" id=\"";
					echo $this->get_field_id("title") . "\" name=\"" . $this->get_field_name("title") . "\" type=\"text\" value=\"" . attribute_escape($title) . "\" /></label></p>";
				}
	 
				function update($new_instance, $old_instance) {
					$instance = $old_instance;
					$instance["title"] = $new_instance["title"];
					return $instance;
				}

				function widget($args, $instance) {
					extract($args, EXTR_SKIP);

					echo $before_widget;
					$title = empty($instance["title"]) ? " " : apply_filters("widget_title", $instance["title"]);

					if (!empty($title))
						echo $before_title . $title . $after_title;

					// WIDGET CODE GOES HERE
					$oAALOptions = new AmazonAutoLinks_Options(AMAZONAUTOLINKSKEY);
					$oAALinWidget = new AmazonAutoLinks_Core($oAALOptions->arrOptions["units"][' . $strUnitLabel . '], $oAALOptions->arrOptions["general"]);
					echo $oAALinWidget->fetch( $oAALinWidget->UrlsFromUnitLabel(' . $strUnitLabel . ', $oAALOptions->arrOptions));
					
					echo $after_widget;
				}			
			}
		');
		register_widget($strWidgetID);	
	}	

}

?>