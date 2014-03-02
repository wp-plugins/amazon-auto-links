<?php
/*
	Plugin Name: Amazon Auto Links
	Plugin URI: http://en.michaeluno.jp/amazon-auto-links
	Description: Generates links of Amazon products just coming out today. You just pick categories and they appear even in JavaScript disabled browsers.
	Version: 2.0.4.1b
	Author: Michael Uno (miunosoft)
	Author URI: http://michaeluno.jp
	Text Domain: amazon-auto-links
	Domain Path: /language
	Requirements: WordPress >= 3.3 and PHP >= 5.2.4
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Run the bootstrap
include_once( dirname( __FILE__ ) . '/class_final/AmazonAutoLinks_Bootstrap.php' );
new AmazonAutoLinks_Bootstrap( __FILE__ );