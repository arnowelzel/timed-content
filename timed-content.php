<?php
/*
Plugin Name: Timed Content
Text Domain: timed-content
Domain Path: /lang
Plugin URI: http://wordpress.org/plugins/timed-content/
Description: Plugin to show or hide portions of a Page or Post based on specific date/time characteristics.  These actions can either be processed either server-side or client-side, depending on the desired effect.
Author: K. Tough, Arno Welzel, Enrico Bacis
Version: 2.80
Author URI: http://wordpress.org/plugins/timed-content/
*/
defined( 'ABSPATH' ) or die();

require 'lib/class-customfieldsinterface.php';
require 'lib/class-timedcontentplugin.php';

define( 'TIMED_CONTENT_VERSION', '2.80' );
define( 'TIMED_CONTENT_SLUG', 'timed-content' );
define( 'TIMED_CONTENT_PLUGIN_URL', plugins_url() . '/' . TIMED_CONTENT_SLUG );
define( 'TIMED_CONTENT_SHORTCODE_CLIENT', 'timed-content-client' );
define( 'TIMED_CONTENT_SHORTCODE_SERVER', 'timed-content-server' );
define( 'TIMED_CONTENT_SHORTCODE_RULE', 'timed-content-rule' );
define( 'TIMED_CONTENT_TIME_ZERO', '1970-01-01 00:00:00 +000' );  // Start of Unix Epoch (32 bit)
define( 'TIMED_CONTENT_TIME_END', '2038-01-19 03:14:06 +000' );   // End of Unix Epoch (32 bit)
define( 'TIMED_CONTENT_RULE_TYPE', 'timed_content_rule' );
define( 'TIMED_CONTENT_RULE_POSTMETA_PREFIX', TIMED_CONTENT_RULE_TYPE . '_' );
define( 'TIMED_CONTENT_CSS', TIMED_CONTENT_PLUGIN_URL . '/css/timed-content.css' );
define( 'TIMED_CONTENT_CSS_DASHICONS', TIMED_CONTENT_PLUGIN_URL . '/css/dashicons/style.css' );
define( 'TIMED_CONTENT_JQUERY_UI_CSS', TIMED_CONTENT_PLUGIN_URL . '/css/jqueryui/1.10.3/themes/smoothness/jquery-ui.css' );
define( 'TIMED_CONTENT_JQUERY_UI_TIMEPICKER_JS', TIMED_CONTENT_PLUGIN_URL . '/js/jquery-ui-timepicker-0.3.3/jquery.ui.timepicker.min.js' );
define( 'TIMED_CONTENT_JQUERY_UI_TIMEPICKER_CSS', TIMED_CONTENT_PLUGIN_URL . '/js/jquery-ui-timepicker-0.3.3/jquery.ui.timepicker.css' );
define( 'TIMED_CONTENT_DATE_FORMAT_OUTPUT', 'Y-m-d H:i O' );
define( 'TIMED_CONTENT_FREQ_HOURLY', 0 );
define( 'TIMED_CONTENT_FREQ_DAILY', 1 );
define( 'TIMED_CONTENT_FREQ_WEEKLY', 2 );
define( 'TIMED_CONTENT_FREQ_MONTHLY', 3 );
define( 'TIMED_CONTENT_FREQ_YEARLY', 4 );

// Initialize plugin
$timed_content_plugin_instance = new TimedContentPlugin();
