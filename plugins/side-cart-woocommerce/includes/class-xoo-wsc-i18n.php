<?php
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    WooCommerce Side Cart
 */
class xoo_wsc_i18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {
		$domain = 'side-cart-woocommerce';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, WP_LANG_DIR . '/'.$domain.'-' . $locale . '.mo' ); //wp-content languages
		load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/languages/' ); // Plugin Languages
	}
}
