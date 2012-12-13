<?php
/*
	Plugin Name: Amazon Auto Links
	Plugin URI: http://michaeluno.jp/en/amazon-auto-links
	Description: Generates links of Amazon products just coming out today. You just pick categories and they appear even in JavaScript disabled browsers.
	Version: 1.1.5
	Author: Michael Uno (miunosoft)
	Author URI: http://michaeluno.jp
	Text Domain: amazonautolinks
	Domain Path: /lang
	Requirements: WordPress >= 3.0 and PHP >= 5.1.2
*/

// Define constants
define( "AMAZONAUTOLINKSKEY", "amazonautolinks" );
define( "AMAZONAUTOLINKSPLUGINNAME", "Amazon Auto Links" );
define( "AMAZONAUTOLINKSPLUGINFILEBASENAME", plugin_basename( __FILE__ ) );
define( "AMAZONAUTOLINKSPLUGINFILE", __FILE__ );
define( "AMAZONAUTOLINKSPLUGINDIR", dirname( __FILE__ ) );
define( "AMAZONAUTOLINKSPLUGINURL", plugins_url('', __FILE__ ) );

include AMAZONAUTOLINKSPLUGINDIR . '/inc/amazonautolinks_initial_load.php';

// uncomment the following function to clear all options and initialize to the default.
// AmazonAutoLinks_CleanOptions();
