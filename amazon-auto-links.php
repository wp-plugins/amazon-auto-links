<?php
/*
	Plugin Name: Amazon Auto Links
	Plugin URI: http://en.michaeluno.jp/amazon-auto-links
	Description: Generates links of Amazon products just coming out today. You just pick categories and they appear even in JavaScript disabled browsers.
	Version: 1.2.5.2
	Author: Michael Uno (miunosoft)
	Author URI: http://michaeluno.jp
	Text Domain: amazon-auto-links
	Domain Path: /lang
	Requirements: WordPress >= 3.0 and PHP >= 5.1.2
*/

// Define constants - they will be used in separate included files
define( 'AMAZONAUTOLINKSKEY', 'amazonautolinks' );		// used for the option key and form values. The text domain has been changed to amazon-auto-links since v1.1.9
if ( !defined( 'AMAZONAUTOLINKSPLUGINNAME' ) ) define( "AMAZONAUTOLINKSPLUGINNAME", "Amazon Auto Links" );
define( "AMAZONAUTOLINKSPLUGINFILEBASENAME", plugin_basename( __FILE__ ) );
define( "AMAZONAUTOLINKSPLUGINFILE", __FILE__ );
define( "AMAZONAUTOLINKSPLUGINDIR", dirname( __FILE__ ) );
define( "AMAZONAUTOLINKSPLUGINURL", plugins_url('', __FILE__ ) );

// define global variables
if ( isset( $arrAALDirPaths ) && is_array( $arrAALDirPaths ) ) array_push( $arrAALDirPaths, dirname( __FILE__ ) . '/classes/' );
else $arrAALDirPaths =  array( AMAZONAUTOLINKSPLUGINDIR . '/classes/' );

// load the loader
include AMAZONAUTOLINKSPLUGINDIR . '/inc/amazonautolinks_initial_load.php';

// uncomment the following function to clear all options and initialize to the default.
// AmazonAutoLinks_CleanOptions();

/**
 * @package     Amazon Auto Links
 * @copyright   Copyright (c) 2013, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
*/