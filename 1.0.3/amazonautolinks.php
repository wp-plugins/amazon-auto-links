<?php
/*
	Plugin Name: Amazon Auto Links
	Plugin URI: http://michaeluno.jp/en/amazon-auto-links
	Description: Generates links of Amazon products just coming out today. You just pick categories and they appear even in JavaScript disabled browsers.
	Version: 1.0.3
	Author: Michael Uno (miunosoft)
	Author URI: http://michaeluno.jp
	Text Domain: amazonautolinks
	Domain Path: /lang
	Requirements: This plugin requires WordPress >= 3.0 and PHP >= 5.1.2
*/

// Define constants
define("AMAZONAUTOLINKSKEY", "amazonautolinks");
define("AMAZONAUTOLINKSPLUGINNAME", "Amazon Auto Links");

// Load actions to hook events for Cron jobs
add_action('init', 'AmazonAutoLinks_LoadActions');

// Plugin Requirements
add_action('admin_init', 'AmazonAutoLinks_Requirements');

// Register Classes
add_action('plugins_loaded', 'AmazonAutoLinks_RegisterClasses');

// Admin Page
add_action( 'plugins_loaded', create_function( '', '$oAALAdmin = new AmazonAutoLinks_Admin;' ) );

// Custom Admin CSS
add_action('admin_head', 'AmazonAutoLinks_CustomCSS');

// Widgets
// add_action('widgets_init', 'AmazonAutoLinks_Widgets');

// uncomment the following function to clear all options and initialize to the default.
// AmazonAutoLinks_CleanOptions();

function AmazonAutoLinks_CleanOptions($key='') {
	delete_option( AMAZONAUTOLINKSKEY );
	delete_option( AMAZONAUTOLINKSKEY . '_events');

	
	$arr = array();
	if ($key != '') {
		$arr = get_option(AMAZONAUTOLINKSKEY);
		$arr[$key] = array();
	}
	update_option(AMAZONAUTOLINKSKEY, $arr);

	global $wpdb;
	$wpdb->query( "DELETE FROM `wp_options` WHERE `option_name` LIKE ('_transient%_aal_%')" );
	$wpdb->query( "DELETE FROM `wp_options` WHERE `option_name` LIKE ('_transient_timeout%_aal_%')" );
	
	// $wpdb->query( "DELETE FROM `wp_options` WHERE `option_name` LIKE ('_transient%_%')" );
	
}

// Caches
function AmazonAutoLinks_LoadActions() {

	// since this function has to be called prior to other hooks including the class registration process,
	// retrieve options manually
	// the event option uses a separate option key since cron jobs runs and updates options asyncronomously, 
	// it should not affect or get affected by other processes.
	$arrEventOptions = get_option('amazonautolinks_events');
	if (!is_array($arrEventOptions)) {
		$arrEventOptions = array('events' => array());
		update_option('amazonautolinks_events', $arrEventOptions);
		return;
	}
	
	// register actions 
	$i = 0;
	foreach($arrEventOptions['events'] as $strActionName => $strURL) {
		$i++;
		add_action($strActionName,'AmazonAutoLinks_CacheCategory');		// the first parameter is the action name to be registered
	}	
	
	// this is mostly for debugging. This message can be viewed at http://[site-address]/wp-admin/options.php
	// update_option('amazonautolinks_actionhook_notice', date("M d Y H:i:s", time() + 9*3600) . ': ' . $i . ' of actions is hooked.');
	AmazonAutoLinks_Log(date("M d Y H:i:s", time() + 9*3600) . ': ' . $i . ' of action(s) is hooked.', __FUNCTION__);
}	
function AmazonAutoLinks_Log($strMsg, $strFunc='', $strFileName='log.html') {

	return; // if you like to see the plugin workings, comment out this line and you'll find a log file in the plugin directory.
	if (!in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'))) 
		return;	

	// for debugging
	if ($strFunc=='') $strFunc = __FUNCTION__;
	$strPath = __DIR__ . '/' . $strFileName;
	if (!file_exists($strPath)) file_put_contents($strPath, '');	// create a file if not exist
	$strLog = date('l jS \of F Y h:i:s A') . ': ' . $strFunc . ': ' . $strMsg . '<br />' . PHP_EOL;
	$arrLines = file($strPath);
	$arrLines = array_reverse($arrLines);
	array_push($arrLines, $strLog);
	$arrLines = array_reverse($arrLines);
	$arrLines = array_splice($arrLines, 0, 100);   // extract last 20 elements
	file_put_contents($strPath, implode('', $arrLines));	
}
function AmazonAutoLinks_CacheCategory() {
	AmazonAutoLinks_Log(' called.', __FUNCTION__);
	// This function is triggered by the run-off shcedule event.
	// It builds caches for only one url per call since this function is assigned by all pre-fetch events.
	
	// instanciate class objects
	$oAALCatCache = new AmazonAutoLinks_CategoryCache(AMAZONAUTOLINKSKEY);
	$arrEventOptions = get_option(AMAZONAUTOLINKSKEY . '_events');
	
	// extract the first entry; the oldest job 
	$arrEvent = array_splice($arrEventOptions['events'], 0, 3);   // take out 3 elements from the beggining of the array
	if (count($arrEvent) == 0)	{
		echo '<!-- Amazon Auto Links: no events are scheduled. Returning. -->';
		return; // if nothing extracted, return
	}
		
	// build cache for this url; this array, $arrEvent only holds one element with a key of the action name and the value of url
	$i = 0;
	foreach($arrEvent as $strURL) 
		if ($oAALCatCache->cache_html($strURL)) $i++;
	
	echo '<!-- ' . __FUNCTION__ . ': ' . $i . ' number of page(s) are cached: -->';
	AmazonAutoLinks_Log($i . ' number of page(s) are cached', __FUNCTION__);
	
	// update the option since the oldest task is removed
	update_option(AMAZONAUTOLINKSKEY . '_events', $arrEventOptions);
	
	// this is mostly for debugging. This message can be viewed at http://[site-address]/wp-admin/options.php
	update_option('amazonautolinks_cronjob_notice', date("M d Y H:i:s", time() + 9*3600) . ': the cron job, ' . $strActionKey . ' is called.');
	
	// if there are remaining tasks, continue executing in the background
	if ( count($arrEventOptions['events']) > 0 ) 
		$oAALCatCache->run_in_background('Background Process: Keep fetching!');
	else 
		AmazonAutoLinks_Log('All done!', __FUNCTION__);
}


// for the plugin admin panel theming
function AmazonAutoLinks_CustomCSS() {
	global $wp_version;

	if ($_GET['page'] != AMAZONAUTOLINKSKEY)
		return;
		
	// if the option page of this plugin is loaded
	if (IsSet($_POST[AMAZONAUTOLINKSKEY]['tab202']['proceedbutton']) || IsSet($_POST[AMAZONAUTOLINKSKEY]['tab100']['proceedbutton'])) {

				$numTab = isset($_POST[AMAZONAUTOLINKSKEY]['tab202']['proceedbutton']) ? 202 : 100;
				$numImageSize = $_POST[AMAZONAUTOLINKSKEY]['tab' . $numTab]['imagesize'];
				$numIframeWidth =  $numImageSize * 2 + 480;		// $strFieldName = $this->pluginkey . '[tab' . $numTabnum . '][imagesize]'		
	
			if ( version_compare($wp_version, '3.1.9', "<" ) )  // if the WordPress version is below 3.2 
				$strIframeWidth = $numIframeWidth < 1180 ? 'width:100%;' : 'width:' . $numIframeWidth . 'px;';		// set the minimum width 
			else 				// if the WordPress version is above 3.2
				$strIframeWidth = $numIframeWidth < 1180 ? 'width:1180px;' : 'width:' . $numIframeWidth . 'px;';		// set the minimum width 

			echo '<style type="text/css">
				#wpcontent {
					height:100%;
					' . $strIframeWidth . '
				}
				#footer {
					' . $strIframeWidth . '
					color: #777;
					border-color: #DFDFDF;
				}    					
				</style>';				

	} else if ($_GET['tab'] == 400) 	// for the upgrading to pro tab; the table needs additional styles
		echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('/css/amazonautolinks_tab400.css', __FILE__). '">';
	
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
	global $strAALDirPath, $arrAALPHPfiles;		// needs to be global since the following create_function() needs the values.
	
	// Register classes
	$strAALDirPath = dirname(__FILE__) . '/classes/';
	$arrAALPHPfiles = array_map(create_function( '$a', 'return basename($a, ".php");' ), glob($strAALDirPath . '*.php'));
	spl_autoload_register(
		create_function('$class_name', '
			global $arrAALPHPfiles, $strAALDirPath;
			if (in_array($class_name, $arrAALPHPfiles)) 
				include($strAALDirPath . $class_name . ".php");' )
	);
	 
	// Define classes 
	$strClassNamePrefix = 'AmazonAutoLinks_';	// define a prefix of file name to avoid executing harmful code in file names.
	foreach ($arrAALPHPfiles as $strFileName) {
		
		// apply security filters
		if (substr($strFileName, 0, strlen($strClassNamePrefix)) != $strClassNamePrefix)
			continue;	// filter out files whhch don't start with the prefix
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