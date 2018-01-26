<?php

/**
 * Admin Part of Plugin, dashboard and options.
 *
 * @package    WooCommerce Side Cart
 */
class xoo_wsc_Premium_Settings extends xoo_wsc_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0 
	 * @access   private
	 * @var      string    $xoo_wsc    The ID of this plugin.
	 */
	private $xoo_wsc;

	/**
	 * The ID of Advanced Settings.
	 *
	 * @since    1.0.0 
	 * @access   private
	 * @var      string    $group    The ID of Premium Settings.
	 */
	private $group;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @var      string    $xoo_wsc     The name of this plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct( $xoo_wsc ) {

		$this->xoo_wsc = $xoo_wsc;
		$this->group = $xoo_wsc.'-pm';
	}
}