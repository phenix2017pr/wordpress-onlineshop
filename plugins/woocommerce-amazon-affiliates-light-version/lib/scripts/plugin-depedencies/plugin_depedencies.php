<?php 
if (class_exists('aaTeamPluginDepedencies') != true) {
    class aaTeamPluginDepedencies
    {
    	/*
        * Some required plugin information
        */
        const VERSION = '1.0';

        /*
        * Store some helpers config
        */
		public $the_plugin = null;

		static protected $_instance;
		
		public $wp_filesystem = null;
		
		static private $debug = false;


		/*
        * Required __construct() function that initalizes the AA-Team Framework
        */
        public function __construct( $the_plugin )
        {
			$this->the_plugin = $the_plugin;

        	// load WP_Filesystem 
			include_once ABSPATH . 'wp-admin/includes/file.php';
		   	WP_Filesystem();
			global $wp_filesystem;
			$this->wp_filesystem = $wp_filesystem;
        }

		/**
	    * Singleton pattern
	    *
	    * @return WooZoneLiteSpinner Singleton instance
	    */
	    static public function getInstance()
	    {
	        if (!self::$_instance) {
	            self::$_instance = new self;
	        }

	        return self::$_instance;
	    }
		
		
		public function initDepedenciesPage()
		{
			// If the user can manage options, let the fun begin!
			if(is_admin() && current_user_can( 'manage_options' ) ){
				if(is_admin()){
					// Adds actions to hook in the required css and javascript
					add_action( "admin_print_styles", array( $this->the_plugin, 'admin_load_styles') );
					add_action( "admin_print_scripts", array( $this->the_plugin, 'admin_load_scripts') );
				}

				// create dashboard page
				add_action( 'admin_menu', array( $this, 'createDepedenciesPage' ) );

				// get fatal errors
				add_action ( 'admin_notices', array( $this->the_plugin, 'fatal_errors'), 10 );

				// get fatal errors
				add_action ( 'admin_notices', array( $this->the_plugin, 'admin_warnings'), 10 );
			}
			
			$this->the_plugin->load_modules( 'depedencies' );
		}
		
		public function createDepedenciesPage() {
			add_menu_page(
				$this->the_plugin->pluginName/* . __( ' Depedencies', $this->the_plugin->localizationName )*/,
				$this->the_plugin->pluginName/* . __( ' Depedencies', $this->the_plugin->localizationName )*/,
				'manage_options',
				$this->the_plugin->alias,
				array( $this, 'depedencies_manage_options_template' ),
				$this->the_plugin->cfg['paths']['plugin_dir_url'] . 'icon_16.png'
			);
		}
		
		public function depedencies_manage_options_template() {
			// Derive the current path and load up aaInterfaceTemplates
			$plugin_path = $this->the_plugin->cfg['paths']['freamwork_dir_path'];
			if(class_exists('aaInterfaceTemplates') != true) {
				require_once($plugin_path . 'settings-template.class.php');

				// Initalize the your aaInterfaceTemplates
				$aaInterfaceTemplates = new aaInterfaceTemplates($this->the_plugin->cfg);

				// try to init the interface
				$aaInterfaceTemplates->printBaseInterface( 'depedencies' );
			}
		}
		
		public function depedencies_plugin_redirect_valid() {
			//delete_option('WooZoneLite_depedencies_is_valid');
			//$site_url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}/{$_SERVER['REQUEST_URI']}";
			//header( "Location: $site_url" );
			delete_option('WooZoneLite_depedencies_is_valid');
			wp_redirect( get_admin_url() . 'admin.php?page=WooZoneLite' );
		}
		
		public function depedencies_plugin_redirect() {

			delete_option('WooZoneLite_depedencies_do_activation_redirect');
			wp_redirect( get_admin_url() . 'admin.php?page=WooZoneLite' );
		}
		
		public function verifyDepedencies() {
			ob_start();
		
			$ret = array('status' => 'valid', 'msg' => '');

			?>
			<div class="panel-body WooZoneLite-panel-body">
				
			<h3 class="WooZoneLite-need-to-install">
				 All of the bellow libraries must be enabled in order for our plugin to function right!
			</h3>
			<?php
			// Woocommerce
			if ( /*class_exists( 'Woocommerce' ) &&*/
				in_array( 'envato-wordpress-toolkit/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || is_multisite() ) {
			?>
			<div class="WooZoneLite-callout WooZoneLite-callout-success">
				 WooCommerce plugin installed
			</div>
			<?php
			}else{
				$ret['status'] = 'invalid';
			?>
			<div class="WooZoneLite-callout WooZoneLite-callout-danger">
				WooCommerce plugin not installed, in order the product to work please <a href="<?php echo admin_url('plugin-install.php?tab=search&s=woocommerce') ?>" traget="_blank">install WooCommerce wordpress plugin</a>.
			</div>
			<?php
			}

			// soap
			/*$soap_and_xml_installed = false;
			if ( extension_loaded('soap') ) {
				$soap_and_xml_installed = true;
			?>
			<div class="WooZoneLite-message WooZoneLite-success">
				SOAP extension installed on server
			</div>
			<?php
			}
			if ( function_exists('simplexml_load_string') ) {
				$soap_and_xml_installed = true;
			?>
			<div class="WooZoneLite-message WooZoneLite-success">
				SimpleXML extension installed on server
			</div>
			<?php
			}
			
			if ( !$soap_and_xml_installed ) {
				$ret['status'] = 'invalid';
			?>
			<div class="WooZoneLite-message WooZoneLite-error">
				None of SOAP or SimpleXML extensions are installed on your server, please talk to your hosting company and they will install it for you.
			</div>
			<?php
			}*/
			
			// curl
			if ( extension_loaded("curl") && function_exists('curl_init') ) {
			?>
			<div class="WooZoneLite-callout WooZoneLite-callout-success">
				cURL extension installed on server
			</div>
			<?php
			}else{
				$ret['status'] = 'invalid';
			?>
			<div class="WooZoneLite-callout WooZoneLite-callout-danger">
				cURL extension not installed on your server, please talk to your hosting company and they will install it for you.
			</div>
			<?php
			}
			?>
			<input type="button" value="Re-Check Depedencies" class="WooZoneLite-form-button WooZoneLite-form-button-info WooZoneLite-depedencies-check" style="margin-top: 10px;">
			
			</div>
			<?php
			if ( self::$debug ) $ret['status'] = 'invalid';
   
			$output = ob_get_contents();
			ob_end_clean();
			$ret['msg'] = $output;
			return $ret;
		}
    }
}