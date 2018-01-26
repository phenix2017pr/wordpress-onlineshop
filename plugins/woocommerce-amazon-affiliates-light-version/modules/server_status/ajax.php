<?php
/*
* Define class WooZoneLiteServerStatus
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
!defined('ABSPATH') and exit;
if (class_exists('WooZoneLiteServerStatusAjax') != true) {
    class WooZoneLiteServerStatusAjax extends WooZoneLiteServerStatus
    {
    	public $the_plugin = null;
		private $module_folder = null;
		private $file_cache_directory = '/psp-page-speed';
		private $cache_lifetime = 60; // in seconds
		
		/*
        * Required __construct() function that initalizes the AA-Team Framework
        */
        public function __construct( $the_plugin=array() )
        {
        	$this->the_plugin = $the_plugin;
			$this->module_folder = $this->the_plugin->cfg['paths']['plugin_dir_url'] . 'modules/server_status/';
			
			// ajax  helper
			add_action('wp_ajax_WooZoneLiteServerStatusRequest', array( $this, 'ajax_request' ));
			add_action('wp_ajax_WooZoneLiteServerStatusOperation', array( $this, 'ajax_operation' ));
		}
		
		/*
		* ajax_request, method
		* --------------------
		*
		* this will create requests to 404 table
		*/
		public function ajax_request()
		{
			$return = array();
			$actions = isset($_REQUEST['sub_action']) ? explode(",", $_REQUEST['sub_action']) : '';
			 
			// Check Memory Limit
			if( in_array( 'check_memory_limit', array_values($actions)) ){
				
				$memory = $this->let_to_num( WP_MEMORY_LIMIT );
				$html = array();
            	if ( $memory < 127108864 ) {
            		$html[] = '<div class="WooZoneLite-message WooZoneLite-error">' . sprintf( __( '%s - We recommend setting memory to at least 128MB. See: <a href="%s" class="WooZoneLite-form-button WooZoneLite-form-button-info">Increasing memory allocated to PHP</a>', $this->the_plugin->localizationName ), size_format( $memory ), 'http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP' ) . '</div>';
            	} else {
            		$html[] = '<div class="WooZoneLite-message WooZoneLite-success">' . size_format( $memory ) . '</div>';
            	}

				$return = array(
					'status'	=> 'valid',
					'html' 		=> implode("\n", $html)
				);
			}
			
			// Export LOG
			if( in_array( 'export_log', array_values($actions)) ){
				
				$log = isset($_REQUEST['log']) ? $_REQUEST['log'] : '';
				$temp_file = tmpfile();
				fwrite( $temp_file, $log );
				fseek( $temp_file, 0 );
				
				header( 'Content-Type: application/octet-stream' );
				header( 'Content-Disposition: attachment; filename="WooZoneLite-logs.html"' );
				header( 'Content-Length: ' . strlen($log) );
				
				echo fread( $temp_file, strlen($log) );
				
				 // this removes the file
				fclose( $temp_file );
				
				die;
			}
			
			// Remote GET
			if( in_array( 'remote_get', array_values($actions)) ){
				
				$status = false;
				$msg = '';
				// WP Remote Get Check
				$params = array(
					'sslverify' 	=> false,
		        	'timeout' 		=> 20,
		        	'body'			=> isset($request) ? $request : array()
				);
				$response = wp_remote_post( 'http://webservices.amazon.com/AWSECommerceService/AWSECommerceService.wsdl', $params );
	 
				if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
	        		$msg = __('wp_remote_get() was successful - Webservices Amazon is working.', $this->the_plugin->localizationName );
	        		$status = true;
	        	} elseif ( is_wp_error( $response ) ) {
	        		$msg = __( 'wp_remote_get() failed. Webservices Amazon won\'t work with your server. Contact your hosting provider. Error:', $this->the_plugin->localizationName ) . ' ' . $response->get_error_message();
	        		$status = false;
	        	} else {
	            	$msg = __( 'wp_remote_get() failed. Webservices Amazon may not work with your server.', $this->the_plugin->localizationName );
	        		$status = false;
	        	}
				
				$return = array(
					'status'	=> ( $status == true ? 'valid' : 'valid' ),
					'html' 		=> ( $status == true ? '<div class="WooZoneLite-message WooZoneLite-success">' : '<div class="WooZoneLite-message WooZoneLite-error">' ) . $msg . '</div>' 
				);
        	}

			// check SOAP
			if( in_array( 'check_soap', array_values($actions)) ){
				
				$status = false;
				$msg = '';
 
				if ( extension_loaded('soap') || class_exists("SOAPClient") || class_exists("SOAP_Client") ) {
					$msg = __('Your server has the SOAP Client class enabled.', $this->the_plugin->localizationName );
					$status = true;
				} else {
	        		$msg = sprintf( __( 'Your server does not have the <a href="%s">SOAP Client</a> class enabled - some gateway plugins which use SOAP may not work as expected.', $this->the_plugin->localizationName ), 'http://php.net/manual/en/class.soapclient.php' );
	        		$status = false;
	        	}

				$return = array(
					'status'	=> ( $status == true ? 'valid' : 'valid' ),
					'html' 		=> ( $status == true ? '<div class="WooZoneLite-message WooZoneLite-success">' : '<div class="WooZoneLite-message WooZoneLite-error">' ) . $msg . '</div>' 
				);
			}
			
			// check SimpleXML
			if( in_array( 'check_simplexml', array_values($actions)) ){
				
				$status = false;
				$msg = '';
				
				if ( function_exists('simplexml_load_string') ) {
					$msg = __('Your server has the SimpleXML library enabled.', $this->the_plugin->localizationName );
					$status = true;
				} else {
	        		$msg = sprintf( __( 'Your server does not have the <a href="%s">SimpleXML</a> library enabled - some gateway plugins which use SimpleXML library may not work as expected.', $this->the_plugin->localizationName ), 'http://php.net/manual/en/book.simplexml.php' );
	        		$status = false;
	        	}

				$return = array(
					'status'	=> ( $status == true ? 'valid' : 'valid' ),
					'html' 		=> ( $status == true ? '<div class="WooZoneLite-message WooZoneLite-success">' : '<div class="WooZoneLite-message WooZoneLite-error">' ) . $msg . '</div>' 
				);
			}
			
			// active plugins
			if( in_array( 'active_plugins', array_values($actions)) ){
				$active_plugins = (array) get_option( 'active_plugins', array() );
									
     			if ( is_multisite() )
					$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );

				$wc_plugins = array();

				foreach ( $active_plugins as $plugin ) {

					$plugin_data    = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
					$dirname        = dirname( $plugin );
					$version_string = '';

					if ( ! empty( $plugin_data['Name'] ) ) {

						if ( strstr( $dirname, $this->the_plugin->localizationName ) ) {

							if ( false === ( $version_data = get_transient( $plugin . '_version_data' ) ) ) {
								$changelog = wp_remote_get( 'http://dzv365zjfbd8v.cloudfront.net/changelogs/' . $dirname . '/changelog.txt' );
								$cl_lines  = explode( "\n", wp_remote_retrieve_body( $changelog ) );
								if ( ! empty( $cl_lines ) ) {
									foreach ( $cl_lines as $line_num => $cl_line ) {
										if ( preg_match( '/^[0-9]/', $cl_line ) ) {

											$date         = str_replace( '.' , '-' , trim( substr( $cl_line , 0 , strpos( $cl_line , '-' ) ) ) );
											$version      = preg_replace( '~[^0-9,.]~' , '' ,stristr( $cl_line , "version" ) );
											$update       = trim( str_replace( "*" , "" , $cl_lines[ $line_num + 1 ] ) );
											$version_data = array( 'date' => $date , 'version' => $version , 'update' => $update , 'changelog' => $changelog );
											set_transient( $plugin . '_version_data', $version_data , 60*60*12 );
											break;
										}
									}
								}
							}

							if ( ! empty( $version_data['version'] ) && version_compare( $version_data['version'], $plugin_data['Version'], '!=' ) )
								$version_string = ' &ndash; <strong style="color:red;">' . $version_data['version'] . ' ' . __( 'is available', $this->the_plugin->localizationName ) . '</strong>';
						}

						$wc_plugins[] = $plugin_data['Name'] . ' ' . __( 'by', $this->the_plugin->localizationName ) . ' ' . $plugin_data['Author'] . ' ' . __( 'version', $this->the_plugin->localizationName ) . ' ' . $plugin_data['Version'] . $version_string;

					}
				}

				if ( sizeof( $wc_plugins ) > 0 ){
					$return = array(
						'status'	=> 'valid',
						'html' 		=> implode( ', <br/>', $wc_plugins ) 
					);
				}
			}

			// active modules of the plugin
			if( in_array( 'active_modules', array_values($actions)) ){
				
				$icon = array(
					    'advanced_search'		=> 		'WooZoneLite-icon-search',
					    'amazon'				=> 		'WooZoneLite-icon-amazon',
					    'amazon_debug' 			=> 		'WooZoneLite-icon-debug',
					    'asin_grabber'			=> 		'WooZoneLite-icon-asin_grabber',
					    'assets_download'		=> 		'WooZoneLite-icon-assets_dwl',
					    'auto_import'			=> 		'WooZoneLite-icon-auto_import',
					    'content_spinner'		=> 		'WooZoneLite-icon-content_spinner',
					    'cronjobs'				=> 		'WooZoneLite-icon-cronjobs',
					    'csv_products_import'	=> 		'WooZoneLite-icon-csv_import',
					    'insane_import'			=> 		'WooZoneLite-icon-insane_import',
					    'modules_manager'		=> 		'WooZoneLite-icon-modules',
					    'price_select'			=> 		'fa fa-money',
					    'product_in_post'		=> 		'fa fa-file-powerpoint-o',
					    'remote_support'		=> 		'WooZoneLite-icon-support',
					    'report'				=> 		'WooZoneLite-icon-woozonelite_report',
					    'server_status'			=> 		'WooZoneLite-icon-server_status',
					    'speed_optimization'			=> 		'WooZoneLite-icon-server_status',
					    'setup_backup'			=> 		'WooZoneLite-icon-setup_backup',
					    'stats_prod'			=> 		'WooZoneLite-icon-products_statistics',
					    'synchronization'		=> 		'WooZoneLite-icon-sync',
					    'woocustom'				=> 		'fa fa-pencil',
					    'dashboard'				=> 		'WooZoneLite-icon-dashboard',
					    'wizard'				=> 		'WooZoneLite-icon-magic-wand',
				);

				$active_modules = (array) $this->the_plugin->cfg['activate_modules'];

				$__modules = array();
				foreach ( $active_modules as $module => $status ) {

					$tryed_module = $this->the_plugin->cfg['modules'][ "$module" ];
					$moduleInfo = array();
					if( isset($tryed_module) && count($tryed_module) > 0 ) {
						$alias = $module;
						$moduleInfo = array(
							'title'			=> $tryed_module["$module"]['menu']['title'],
							'version'		=> $tryed_module["$module"]['version'],
							'icon'			=> '<i class="'.($icon["$alias"]).'"></i>', //$tryed_module["$module"]['menu']['icon'],
							'description'	=> isset($tryed_module["$module"]['description']) ? $tryed_module["$module"]['description'] : '',
							'url'			=> isset($tryed_module["$module"]['in_dashboard']['url']) ? $tryed_module["$module"]['in_dashboard']['url'] : ''
						);
						
						$title = '<span class="title">' . $moduleInfo['title'] . '</span>';
						if ( isset($moduleInfo['url']) && !empty($moduleInfo['url']) ) {
							$title = '<a href="' . $moduleInfo['url'] . '" class="title">' . $title . '</a>';
						}
						
						$__modules[] = '<div class="active_modules">
							' . $moduleInfo['icon']
							. $title
							. ',<span class="version">' . $moduleInfo['version'] . '</span>
							<span class="description">(' . $moduleInfo['description'] . ')</span>
						</div>';
					}
				}

				if ( sizeof( $__modules ) > 0 ){
					$return = array(
						'status'	=> 'valid',
						'html' 		=> implode( '', $__modules ) 
					);
				}
			}

			die(json_encode($return));
		}

		public function ajax_operation( $retType = 'die' ) {
            $action = isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : '';

            $ret = array(
                'status'		=> 'invalid',
                'html'		=> ''
            );
   
            if (!in_array($action, array(
            	'check_integrity_database',
			))) die(json_encode($ret));
			
			if ( 'check_integrity_database' == $action ) {

				$opStatus = $this->the_plugin->plugin_integrity_check( 'check_database', true );
				$opStatus_stat = $this->the_plugin->plugin_integrity_get_last_status( 'check_database' );
				
				$check_last_msg = '';
				if ( '' != trim($opStatus_stat['html']) ) {
					$check_last_msg = ( $opStatus_stat['status'] == true ? '<div class="WooZoneLite-message WooZoneLite-success">' : '<div class="WooZoneLite-message WooZoneLite-error">' ) . $opStatus_stat['html'] . '</div>';
				}

				$ret = array(
					'status'	=> 'valid', //( $opStatus_stat['status'] == true ? 'valid' : 'valid' ),
					'html' 		=> $check_last_msg
				);
			}

			//if ( $retType == 'die' ) die(json_encode($ret));
			//else return $ret;
			die(json_encode($ret));
		}
    }
}