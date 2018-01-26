<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://www.mailmunch.co
 * @since             2.0.0
 * @package           Mailchimp_Mailmunch
 *
 * @wordpress-plugin
 * Plugin Name:       MailChimp Forms by MailMunch
 * Plugin URI:        http://connect.mailchimp.com/integrations/mailmunch-email-list-builder
 * Description:       The MailChimp plugin allows you to quickly and easily add signup forms for your MailChimp lists. Popup, Embedded, Top Bar and a variety of different options available.
 * Version:           3.1.0
 * Author:            MailMunch
 * Author URI:        http://www.mailmunch.co
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       mailchimp-forms-by-mailmunch
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-mailchimp-mailmunch-activator.php
 */
function activate_mailchimp_mailmunch() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mailchimp-mailmunch-activator.php';
	Mailchimp_Mailmunch_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-mailchimp-mailmunch-deactivator.php
 */
function deactivate_mailchimp_mailmunch() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mailchimp-mailmunch-deactivator.php';
	Mailchimp_Mailmunch_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_mailchimp_mailmunch' );
register_deactivation_hook( __FILE__, 'deactivate_mailchimp_mailmunch' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-mailchimp-mailmunch.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    2.0.0
 */
function run_mailchimp_mailmunch() {

	$plugin = new Mailchimp_Mailmunch();
	$plugin->run();

}
run_mailchimp_mailmunch();
