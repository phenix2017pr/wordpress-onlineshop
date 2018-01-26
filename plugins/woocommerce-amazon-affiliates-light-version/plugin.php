<?php
/*
Plugin Name:	 	WZone Lite - WooCommerce Amazon Affiliates
Plugin URI: 		https://wordpress.org/plugins/woocommerce-amazon-affiliates-light-version
Description: 		Choose from over a million products & earn advertising fees from the 1’st internet retailer online! You can earn up to 10% advertising fees from the 1’st trusted e-commerce leader with minimal effort. This lite version of WZone allows you to import a large number of products directly from Amazon right into your Wordpress WooCommerce Store! EnjoY!
Version: 			2.0 Lite 
Author: 			AA-Team
Author URI: 		http://codecanyon.net/user/AA-Team/portfolio
Text Domain: 	    wzone
*/
! defined( 'ABSPATH' ) and exit;

define('WOOZONELITE_VERSION', '2.0');

// Derive the current path and load up WooZoneLite
$plugin_path = dirname(__FILE__) . '/';
if(class_exists('WooZoneLite') != true) {
	require_once($plugin_path . 'aa-framework/framework.class.php');
}

// Initalize the your plugin
$WooZoneLite = new WooZoneLite();

global $WooZone;
if( !is_object($WooZone) ){
	$WooZone = $WooZoneLite;
}

// Add an activation hook
register_activation_hook(__FILE__, array(&$WooZoneLite, 'activate'));

// load textdomain
add_action( 'plugins_loaded', 'woozonelite_load_textdomain' );
add_action( 'plugins_loaded', 'woozonelite_check_integrity' );

function woozonelite_load_textdomain() {  
	load_plugin_textdomain( 'woozonelite', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
function woozonelite_check_integrity() {
	$mainObj = WooZoneLite();
	return is_object($mainObj) ? $mainObj->plugin_integrity_check() : true;
}