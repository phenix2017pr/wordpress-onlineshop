<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'YITH_YWZM_Plugin_FW_Loader' ) ) {

	/**
	 * Implements features related to an invoice document
	 *
	 * @class YITH_YWZM_Plugin_FW_Loader
	 * @package Yithemes
	 * @since   1.0.0
	 * @author  Lorenzo Giuffrida
	 */
	class YITH_YWZM_Plugin_FW_Loader {

		/**
		 * @var $_panel Panel Object
		 */
		protected $_panel;

		/**
		 * @var $_premium string Premium tab template file name
		 */
		protected $_premium = 'premium.php';

		/**
		 * @var string Premium version landing link
		 */
		protected $_premium_landing = 'http://yithemes.com/themes/plugins/yith-woocommerce-zoom-magnifier/';

		/**
		 * @var string Plugin official documentation
		 */
		protected $_official_documentation = 'http://yithemes.com/docs-plugins/yith-woocommerce-zoom-magnifier/';

		/**
		 * @var string Plugin panel page
		 */
		protected $_panel_page = 'yith_woocommerce_zoom-magnifier_panel';

		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function __construct() {
			/**
			 * Register actions and filters to be used for creating an entry on YIT Plugin menu
			 */
			add_action( 'admin_init', array( $this, 'register_pointer' ) );

			add_action( 'plugins_loaded', array( $this, 'plugin_fw_loader' ), 15 );

			//Add action links
			add_filter( 'plugin_action_links_' . plugin_basename( YITH_YWZM_DIR . '/' . basename( YITH_YWZM_FILE ) ), array(
				$this,
				'action_links'
			) );

			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 4 );

			//  Add stylesheets and scripts files
			add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );

			if ( ! defined( 'YITH_YWZM_PREMIUM' ) ) {
				//  Show plugin premium tab
				add_action( 'yith_zoom_magnifier_premium', array( $this, 'premium_tab' ) );
			} else {

				/**
				 * register plugin to licence/update system
				 */
				$this->licence_activation();
			}
		}


		/**
		 * Load YIT core plugin
		 *
		 * @since  1.0
		 * @access public
		 * @return void
		 * @author Andrea Grillo <andrea.grillo@yithemes.com>
		 */
		public function plugin_fw_loader() {
			if ( !defined( 'YIT_CORE_PLUGIN' ) ) {
				global $plugin_fw_data;
				if ( !empty( $plugin_fw_data ) ) {
					$plugin_fw_file = array_shift( $plugin_fw_data );
					require_once( $plugin_fw_file );
				}
			}
		}

		/**
		 * Add a panel under YITH Plugins tab
		 *
		 * @return   void
		 * @since    1.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 * @use     /Yit_Plugin_Panel class
		 * @see      plugin-fw/lib/yit-plugin-panel.php
		 */
		public function register_panel() {

			if ( ! empty( $this->_panel ) ) {
				return;
			}

			$admin_tabs['general'] = __( 'General', 'yith-woocommerce-zoom-magnifier' );

			if ( ! defined( 'YITH_YWZM_PREMIUM' ) ) {
				$admin_tabs['premium-landing'] = __( 'Premium Version', 'yith-woocommerce-zoom-magnifier' );
			} else {
				$admin_tabs['exclusions'] = __( 'Product exclusion list', 'yith-woocommerce-zoom-magnifier' );
			}

			$args = array(
				'create_menu_page' => true,
				'parent_slug'      => '',
				'page_title'       => 'Zoom magnifier',
				'menu_title'       => 'Zoom magnifier',
				'capability'       => 'manage_options',
				'parent'           => '',
				'parent_page'      => 'yit_plugin_panel',
				'page'             => $this->_panel_page,
				'admin-tabs'       => $admin_tabs,
				'options-path'     => YITH_YWZM_DIR . '/plugin-options'
			);

			/* === Fixed: not updated theme  === */
			if ( ! class_exists( 'YIT_Plugin_Panel_WooCommerce' ) ) {

				require_once( 'plugin-fw/lib/yit-plugin-panel-wc.php' );
			}

			$this->_panel = new YIT_Plugin_Panel_WooCommerce( $args );

			/** Add custom types actions and filters */
			YITH_YWZM_Custom_Types::get_instance();
		}

		/**
		 * Premium Tab Template
		 *
		 * Load the premium tab template on admin page
		 *
		 * @return   void
		 * @since    1.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 * @return void
		 */
		public function premium_tab() {
			$premium_tab_template = YITH_YWZM_TEMPLATE_DIR . '/admin/' . $this->_premium;
			if ( file_exists( $premium_tab_template ) ) {
				include_once( $premium_tab_template );
			}
		}

		/**
		 * Action Links
		 *
		 * add the action links to plugin admin page
		 *
		 * @param $links | links plugin array
		 *
		 * @return   mixed Array
		 * @since    1.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 * @return mixed
		 * @use plugin_action_links_{$plugin_file_name}
		 */
		public function action_links( $links ) {
			$links[] = '<a href="' . admin_url( "admin.php?page={$this->_panel_page}" ) . '">' . __( 'Settings', 'yith-woocommerce-zoom-magnifier' ) . '</a>';

			if ( defined( 'YITH_YWZM_FREE_INIT' ) ) {
				$links[] = '<a href="' . $this->_premium_landing . '" target="_blank">' . __( 'Premium Version', 'yith-woocommerce-zoom-magnifier' ) . '</a>';
			}

			return $links;
		}

		/**
		 * plugin_row_meta
		 *
		 * add the action links to plugin admin page
		 *
		 * @param $plugin_meta
		 * @param $plugin_file
		 * @param $plugin_data
		 * @param $status
		 *
		 * @return   Array
		 * @since    1.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 * @use plugin_row_meta
		 */
		public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
			if ( ( defined( 'YITH_YWZM_INIT' ) && ( YITH_YWZM_INIT == $plugin_file ) ) ||
			     ( defined( 'YITH_YWZM_FREE_INIT' ) && ( YITH_YWZM_FREE_INIT == $plugin_file ) )
			) {
				$plugin_meta[] = '<a href="' . $this->_official_documentation . '" target="_blank">' . __( 'Plugin Documentation', 'yith-woocommerce-zoom-magnifier' ) . '</a>';
			}

			return $plugin_meta;
		}

		public function register_pointer() {
			if ( ! class_exists( 'YIT_Pointers' ) ) {
				include_once( 'plugin-fw/lib/yit-pointers.php' );
			}

			$premium_message = defined( 'YITH_YWZM_PREMIUM' )
				? ''
				: __( 'YITH WooCommerce Zoom Magnifier is available in an outstanding PREMIUM version with many new options, discover it now.', 'yith-woocommerce-zoom-magnifier' ) .
				  ' <a href="' . $this->_premium_landing . '">' . __( 'Premium version', 'yith-woocommerce-zoom-magnifier' ) . '</a>';

			$args[] = array(
				'screen_id'  => 'plugins',
				'pointer_id' => 'yith_woocommerce_zoom-magnifier',
				'target'     => '#toplevel_page_yit_plugin_panel',
				'content'    => sprintf( '<h3> %s </h3> <p> %s </p>',
					__( 'YITH WooCommerce Zoom Magnifier', 'yith-woocommerce-zoom-magnifier' ),
					__( 'In YIT Plugins tab you can find  YITH WooCommerce Zoom Magnifier options.<br> From this menu you can access all settings of the YITH plugins activated.', 'yith-woocommerce-zoom-magnifier' ) . '<br>' . $premium_message
				),
				'position'   => array( 'edge' => 'left', 'align' => 'center' ),
				'init'       => defined( 'YITH_YWZM_PREMIUM' ) ? YITH_YWZM_INIT : YITH_YWZM_FREE_INIT
			);

			YIT_Pointers()->register( $args );
		}

		/**
		 * Get the premium landing uri
		 *
		 * @since   1.0.0
		 * @author  Andrea Grillo <andrea.grillo@yithemes.com>
		 * @return  string The premium landing link
		 */
		public function get_premium_landing_uri() {
			return defined( 'YITH_REFER_ID' ) ? $this->_premium_landing . '?refer_id=' . YITH_REFER_ID : $this->_premium_landing . '?refer_id=1030585';
		}

		//region    ****    licence related methods ****

		/**
		 * Add actions to manage licence activation and updates
		 */
		public function licence_activation() {
			if ( ! defined( 'YITH_YWZM_PREMIUM' ) ) {
				return;
			}

			add_action( 'wp_loaded', array( $this, 'register_plugin_for_activation' ), 99 );
			add_action( 'admin_init', array( $this, 'register_plugin_for_updates' ) );
		}

		/**
		 * Register plugins for activation tab
		 *
		 * @return void
		 * @since    2.0.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 */
		public function register_plugin_for_activation() {
			if ( ! class_exists( 'YIT_Plugin_Licence' ) ) {
				require_once YITH_YWZM_DIR . '/plugin-fw/licence/lib/yit-licence.php';
				require_once YITH_YWZM_DIR . '/plugin-fw/licence/lib/yit-plugin-licence.php';
			}
			YIT_Plugin_Licence()->register( YITH_YWZM_INIT, YITH_YWZM_SECRET_KEY, YITH_YWZM_SLUG );
		}

		/**
		 * Register plugins for update tab
		 *
		 * @return void
		 * @since    2.0.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 */
		public function register_plugin_for_updates() {
			if ( ! class_exists( 'YIT_Upgrade' ) ) {
				require_once 'plugin-fw/lib/yit-upgrade.php';
			}
			YIT_Upgrade()->register( YITH_YWZM_SLUG, YITH_YWZM_INIT );
		}
		//endregion
	}
}