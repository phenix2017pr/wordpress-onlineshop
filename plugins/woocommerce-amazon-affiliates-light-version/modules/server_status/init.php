<?php
/*
* Define class WooZoneLiteServerStatus
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
!defined('ABSPATH') and exit;

if (class_exists('WooZoneLiteServerStatus') != true) {
    class WooZoneLiteServerStatus
    {
        /*
        * Some required plugin information
        */
        const VERSION = '1.0';

        /*
        * Store some helpers config
        */
		public $the_plugin = null;

		private $module_folder = '';
		private $module = '';

		static protected $_instance;

        /*
        * Required __construct() function that initalizes the AA-Team Framework
        */
        public function __construct()
        {
        	global $WooZoneLite;

        	$this->the_plugin = $WooZoneLite;
			$this->module_folder = $this->the_plugin->cfg['paths']['plugin_dir_url'] . 'modules/server_status/';
			$this->module = $this->the_plugin->cfg['modules']['server_status'];

			if (is_admin()) {
	            add_action('admin_menu', array( &$this, 'adminMenu' ));
			}

			// load the ajax helper
			require_once( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'modules/server_status/ajax.php' );
			new WooZoneLiteServerStatusAjax( $this->the_plugin );
        }

		/**
	    * Singleton pattern
	    *
	    * @return WooZoneLiteServerStatus Singleton instance
	    */
	    static public function getInstance()
	    {
	        if (!self::$_instance) {
	            self::$_instance = new self;
	        }

	        return self::$_instance;
	    }

		/**
	    * Hooks
	    */
	    static public function adminMenu()
	    {
	       self::getInstance()
	    		->_registerAdminPages();
	    }

	    /**
	    * Register plug-in module admin pages and menus
	    */
		protected function _registerAdminPages()
    	{ 
    		add_submenu_page(
    			$this->the_plugin->alias,
    			$this->the_plugin->alias . " " . __('Check System status', $this->the_plugin->localizationName),
	            __('System Status', $this->the_plugin->localizationName),
	            'manage_options',
	            $this->the_plugin->alias . "_server_status",
	            array($this, 'display_index_page')
	        );

			return $this;
		}

		public function display_index_page()
		{
			$this->printBaseInterface();
		}
		
		/*
		* printBaseInterface, method
		* --------------------------
		*
		* this will add the base DOM code for you options interface
		*/
		private function printBaseInterface()
		{
			global $wpdb;
			
			$amz_settings = get_option( 'WooZoneLite_amazon' );
			$plugin_data = get_plugin_data( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'plugin.php' );
?>
		<script type="text/javascript" src="<?php echo $this->module_folder;?>app.server_status.js" ></script>
		<div id="<?php echo WooZoneLite()->alias?>">
			
			<div class="<?php echo WooZoneLite()->alias?>-content"> 

				<?php
				// show the top menu
				WooZoneLiteAdminMenu::getInstance()->make_active('info|server_status')->show_menu();
				?>

				<!-- Content -->
				<section class="<?php echo WooZoneLite()->alias?>-main">
					
					<?php 
					echo WooZoneLite()->print_section_header(
						$this->module['server_status']['menu']['title'],
						$this->module['server_status']['description'],
						$this->module['server_status']['help']['url']
					);
					?>
					
					<div class="panel panel-default WooZoneLite-panel">
						<div class="panel-body WooZoneLite-panel-body">

						<!-- Content Area -->
						<div id="WooZoneLite-content-area">
							<div class="WooZoneLite-grid_4">
	                        	<div class="WooZoneLite-panel">
									<div class="WooZoneLite-panel-content WooZoneLite-server-status">
										<table class="WooZoneLite-table" cellspacing="0">

												<thead>
													<tr>
														<th colspan="2"><?php _e( 'Modules', $this->the_plugin->localizationName ); ?></th>
													</tr>
												</thead>
										
												<tbody>
										         	<tr>
										         		<td><?php _e( 'Active Modules', $this->the_plugin->localizationName ); ?>:</td>
										         		<td><div class="WooZoneLite-loading-ajax-details" data-action="active_modules"></div></td>
										         	</tr>
												</tbody>


												<?php
													$opStatus_stat = $this->the_plugin->plugin_integrity_get_last_status( 'check_database' );
													
													$check_last_msg = '';
													if ( '' != trim($opStatus_stat['html']) ) {
														$check_last_msg = ( $opStatus_stat['status'] == true ? '<div class="WooZoneLite-message WooZoneLite-success">' : '<div class="WooZoneLite-message WooZoneLite-error">' ) . $opStatus_stat['html'] . '</div>';
													}
												?>
												<thead>
													<tr>
														<th colspan="2"><?php _e( 'Plugin Integrity', $this->the_plugin->localizationName ); ?></th>
													</tr>
												</thead>
										
												<tbody>
										         	<tr>
										         		<td><?php _e( 'Database', $this->the_plugin->localizationName ); ?>:</td>
										         		<td>
										         			<?php /*<div class="WooZoneLite-loading-ajax-details" data-action="check_integrity_database"></div>*/ ?>
										         			<div class="WooZoneLite-check-integrity-container">
										         				<a href="#check_integrity_database" class="WooZoneLite-form-button WooZoneLite-form-button-info" data-action="check_integrity_database">Check</a>
										         				<div class="WooZoneLite-response"><?php echo $check_last_msg; ?></div>
										         			</div>
										         		</td>
										         	</tr>
												</tbody>


						<?php
						$providers = $this->the_plugin->get_main_settings('all');
						?>

						<?php
						$html = array();
						$valueNo = 0; 
						foreach ($providers as $pkey => $pval) {
							$html[] = 	'<thead>
											<tr>
												<th colspan="2">' . $pval['title'] . '</th>
											</tr>
										</thead>';
							$html[] = 	'<tbody>';
							
							foreach ($pval['keys'] as $pkey2 => $pval2) {
								$html[] = 		'<tr>';
								$html[] =			'<td width="190">' . $pval2['title'] . ':</td>';
								$html[] =			'<td>';
								
								if ( is_array($pval2['value']) ) {
									foreach ($pval2['value'] as $key => $value) {
										
										if ( trim($value) != "") {
											$html[] = "<strong>" . $key . ":</strong> " . $value . "<br />";
										}
										else {
											$valueNo++; 
											if ($valueNo == count($pval2['value'])) {
												$html[] = '<div class="WooZoneLite-message WooZoneLite-error">' . sprintf( __( 'No Affiliate ID set. Please set one in the plugin tab Amazon Config.', $this->the_plugin->localizationName ) ) . '</div>';
											}
										}
									}
								}
								else {
									$html[] = $pval2['value'];
								}

								$html[] = 			'</td>';
								$html[]	=		'</tr>';
							}
							
							$html[] = 	'</tbody>';
						}
						?>

						<?php echo implode(PHP_EOL, $html); ?>
											
											<thead>
												<tr>
													<th colspan="2"><?php _e( 'WooZoneLite import settings', $this->the_plugin->localizationName ); ?></th>
												</tr>
											</thead>
											
											<tbody>
												<tr>
									                <td width="190"><?php _e( 'Request Type',$this->the_plugin->localizationName ); ?>:</td>
									                <td><?php echo $amz_settings['protocol'];?></td>
									            </tr>
									            <tr>
									                <td width="190"><?php _e( 'Amazon API location',$this->the_plugin->localizationName ); ?>:</td>
									                <td>webservices.amazon.<?php echo $amz_settings['country'];?></td>
									            </tr>
									            <tr>
									                <td width="190"><?php _e( 'On-site Cart',$this->the_plugin->localizationName ); ?>:</td>
									                <td><?php echo $amz_settings['onsite_cart'];?></td>
									            </tr>
									            <tr>
									                <td width="190"><?php _e( 'Download Item Attribute',$this->the_plugin->localizationName ); ?>:</td>
									                <td><?php echo $amz_settings['item_attribute'];?></td>
									            </tr>
									         
									         
									        </tbody> 
											
											<thead>
												<tr>
													<th colspan="2"><?php _e( 'Syncronize Capabilities Testing:', $this->the_plugin->localizationName ); ?></th>
												</tr>
											</thead>
									
											<tbody>
									            <tr>
									            	<td style="vertical-align: middle;">Import test:</td>
									                <td>
														<div class="WooZoneLite-import-products-test">
															<div class="WooZoneLite-test-timeline">
																<div class="WooZoneLite-one_step" id="stepid-step1">
																	<div class="WooZoneLite-step-status WooZoneLite-loading-inprogress"></div>
																	<span class="WooZoneLite-step-name">Step 1</span>
																</div>
																<div class="WooZoneLite-one_step" id="stepid-step2">
																	<div class="WooZoneLite-step-status"></div>
																	<span class="WooZoneLite-step-name">Step 2</span>
																</div>
																<div class="WooZoneLite-one_step" id="stepid-step3">
																	<div class="WooZoneLite-step-status"></div>
																	<span class="WooZoneLite-step-name">Step 3</span>
																</div>
																<div style="clear:both;"></div>
															</div>
															<table class="WooZoneLite-table WooZoneLite-logs" cellspacing="0">
																<tr id="logbox-step1">
																	<td width="80">Step 1:</td>
																	<td>
																		<div class="WooZoneLite-log-title">
																			Get product from Amazon.<?php echo $amz_settings['country'];?>
																			<a href="#" class="WooZoneLite-form-button WooZoneLite-form-button-info">View details +</a>
																		</div>
																		
																		<textarea class="WooZoneLite-log-details"></textarea>
																	</td>
																</tr>
																<tr id="logbox-step2">
																	<td width="80">Step 2:</td>
																	<td>
																		<div class="WooZoneLite-log-title">
																			Import the product into woocomerce
																			<a href="#" class="WooZoneLite-form-button WooZoneLite-form-button-info">View details +</a>
																		</div>
																		
																		<textarea class="WooZoneLite-log-details"></textarea>
																	</td>
																</tr>
																<tr id="logbox-step3">
																	<td width="80">Step 3:</td>
																	<td>
																		<div class="WooZoneLite-log-title">
																			Download images (<?php echo $amz_settings['number_of_images'];?>) for products
																			<a href="#" class="WooZoneLite-form-button WooZoneLite-form-button-info">View details +</a>
																		</div>
																		
																		<textarea class="WooZoneLite-log-details"></textarea>
																	</td>
																</tr>
															</table>
															<div class="WooZoneLite-begin-test-container">
																<label>Test with ASIN code</label>
																<input id="WooZoneLite-test-ASIN" value="B00KDRPW76" type="text" />
																<a href="#begin-test" class="WooZoneLiteStressTest WooZoneLite-form-button WooZoneLite-form-button-info">Begin the test</a>
															</div>
														</div>
													</td>
									            </tr>
											</tbody>
											
											<thead>
												<tr>
													<th colspan="2"><?php _e( 'Environment', $this->the_plugin->localizationName ); ?></th>
												</tr>
											</thead>
									
											<tbody>
												<tr>
									                <td width="190"><?php _e( 'Home URL',$this->the_plugin->localizationName ); ?>:</td>
									                <td><?php echo home_url(); ?></td>
									            </tr>
									            <tr>
									                <td><?php _e( 'WooZoneLite Version',$this->the_plugin->localizationName ); ?>:</td>
									                <td><?php echo $plugin_data['Version'];?></td>
									            </tr>
									            <tr>
									                <td><?php _e( 'WP Version',$this->the_plugin->localizationName ); ?>:</td>
									                <td><?php if ( is_multisite() ) echo 'WPMU'; else echo 'WP'; ?> <?php bloginfo('version'); ?></td>
									            </tr>
									            <tr>
									                <td><?php _e( 'Web Server Info',$this->the_plugin->localizationName ); ?>:</td>
									                <td><?php echo esc_html( $_SERVER['SERVER_SOFTWARE'] );  ?></td>
									            </tr>
									            <tr>
									                <td><?php _e( 'PHP Version',$this->the_plugin->localizationName ); ?>:</td>
									                <td><?php if ( function_exists( 'phpversion' ) ) echo esc_html( phpversion() ); ?></td>
									            </tr>
									            <tr>
	                                                <td><?php _e( 'MySQL Version',$this->the_plugin->localizationName ); ?>:</td>
	                                                <td><?php if ( function_exists( 'mysql_get_server_info' ) ) echo esc_html( (is_resource($wpdb->dbh)) ? mysql_get_server_info( $wpdb->dbh ) : $wpdb->db_version() ); ?></td>
	                                            </tr>
									            <tr>
									                <td><?php _e( 'WP Memory Limit',$this->the_plugin->localizationName ); ?>:</td>
									                <td><div class="WooZoneLite-loading-ajax-details" data-action="check_memory_limit"></div></td>
									            </tr>
									            <tr>
									                <td><?php _e( 'WP Debug Mode',$this->the_plugin->localizationName ); ?>:</td>
									                <td><?php if ( defined('WP_DEBUG') && WP_DEBUG ) echo __( 'Yes', $this->the_plugin->localizationName ); else echo __( 'No', $this->the_plugin->localizationName ); ?></td>
									            </tr>
									            <tr>
									                <td><?php _e( 'WP Max Upload Size',$this->the_plugin->localizationName ); ?>:</td>
									                <td><?php echo size_format( wp_max_upload_size() ); ?></td>
									            </tr>
									            <tr>
									                <td><?php _e('PHP Post Max Size',$this->the_plugin->localizationName ); ?>:</td>
									                <td><?php if ( function_exists( 'ini_get' ) ) echo size_format( $this->let_to_num( ini_get('post_max_size') ) ); ?></td>
									            </tr>
									            <tr>
									                <td><?php _e('PHP Time Limit',$this->the_plugin->localizationName ); ?>:</td>
									                <td><?php if ( function_exists( 'ini_get' ) ) echo ini_get('max_execution_time'); ?></td>
									            </tr>
									            <tr>
									                <td><?php _e('WP Remote GET',$this->the_plugin->localizationName ); ?>:</td>
									                <td><div class="WooZoneLite-loading-ajax-details" data-action="remote_get"></div></td>
									            </tr>
									            <tr>
									                <td><?php _e('SOAP Client',$this->the_plugin->localizationName ); ?>:</td>
									                <td><div class="WooZoneLite-loading-ajax-details" data-action="check_soap"></div></td>
									            </tr>
									            <tr>
									                <td><?php _e('SimpleXML library',$this->the_plugin->localizationName ); ?>:</td>
									                <td><div class="WooZoneLite-loading-ajax-details" data-action="check_simplexml"></div></td>
									            </tr>
											</tbody>
									
											<thead>
												<tr>
													<th colspan="2"><?php _e( 'Plugins', $this->the_plugin->localizationName ); ?></th>
												</tr>
											</thead>
									
											<tbody>
									         	<tr>
									         		<td><?php _e( 'Installed Plugins',$this->the_plugin->localizationName ); ?>:</td>
									         		<td><div class="WooZoneLite-loading-ajax-details" data-action="active_plugins"></div></td>
									         	</tr>
											</tbody>
									
											<thead>
												<tr>
													<th colspan="2"><?php _e( 'Settings', $this->the_plugin->localizationName ); ?></th>
												</tr>
											</thead>
									
											<tbody>
									
									            <tr>
									                <td><?php _e( 'Force SSL',$this->the_plugin->localizationName ); ?>:</td>
													<td><?php echo get_option( 'woocommerce_force_ssl_checkout' ) === 'yes' ? __( 'Yes', $this->the_plugin->localizationName ) : __( 'No', $this->the_plugin->localizationName ); ?></td>
									            </tr>
											</tbody>
											
											<thead>
												<tr>
													<th colspan="2"><?php _e( 'Woocommerce Dependencies - Needed for the cart option to work properly', $this->the_plugin->localizationName ); ?></th>
												</tr>
											</thead>
									
											<tbody>
												<?php
													$check_pages = array(
														_x( 'Cart Page', 'Page setting', 'woocommerce' ) => array(
																'option' => 'woocommerce_cart_page_id',
																'shortcode' => '[' . apply_filters( 'woocommerce_cart_shortcode_tag', 'woocommerce_cart' ) . ']'
															),
														_x( 'Checkout Page', 'Page setting', 'woocommerce' ) => array(
																'option' => 'woocommerce_checkout_page_id',
																'shortcode' => '[' . apply_filters( 'woocommerce_checkout_shortcode_tag', 'woocommerce_checkout' ) . ']'
															),
													);
										
													$alt = 1;
										
													foreach ( $check_pages as $page_name => $values ) {
										
														if ( $alt == 1 ) echo '<tr>'; else echo '<tr>';
										
														echo '<td>' . esc_html( $page_name ) . ':</td><td>';
										
														$error = false;
										
														$page_id = get_option( $values['option'] );
										
														// Page ID check
														if ( ! $page_id ) {
															echo '<div class="WooZoneLite-message WooZoneLite-error">' . __( 'Page not set', 'woocommerce' ) . '</div>';
															$error = true;
														} else {
										
															// Shortcode check
															if ( $values['shortcode'] ) {
																$page = get_post( $page_id );
										
																if ( empty( $page ) ) {
										
																	echo '<div class="WooZoneLite-message WooZoneLite-error">' . sprintf( __( 'Page does not exist', 'woocommerce' ) ) . '</div>';
																	$error = true;
										
																} else if ( ! strstr( $page->post_content, $values['shortcode'] ) ) {
										
																	echo '<div class="WooZoneLite-message WooZoneLite-error">' . sprintf( __( 'Page does not contain the shortcode: %s', 'woocommerce' ), $values['shortcode'] ) . '</div>';
																		$error = true;
											
																	}
																}
											
															}
											
															if ( ! $error ) echo '<div class="WooZoneLite-message WooZoneLite-success">#' . absint( $page_id ) . ' - ' . str_replace( home_url(), '', get_permalink( $page_id ) ) . '</div>';
											
															echo '</td></tr>';
											
															$alt = $alt * -1;
														}
													?>
												</tbody>											
												
												<!--tfoot>
													<tr>
														<th colspan="2">
															<a href="#" class="WooZoneLite-button blue WooZoneLite-export-logs">Export status log as file</a>
														</th>
													</tr>
												</tfoot-->
											</table>
					            		</div>
									</div>
								</div>
								<div class="clear"></div>
							</div>
						</div>
					</div>
				</section>
			</div>
		</div>

<?php
		}

		/*
		* ajax_request, method
		* --------------------
		*
		* this will create requesto to 404 table
		*/
		public function ajax_request()
		{
			global $wpdb;
			$request = array(
				'id' 			=> isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0
			);
			
			$asin = get_post_meta($request['id'], '_amzASIN', true);
			
			$sync = new wwcAmazonSyncronize( $this->the_plugin );
			$sync->updateTheProduct( $asin );
		}
    
		public function let_to_num($size) {
			if ( function_exists('wc_let_to_num') ) {
				return wc_let_to_num( $size );
			}

			$l = substr($size, -1);
			$ret = substr($size, 0, -1);
			switch( strtoupper( $l ) ) {
				case 'P' :
					$ret *= 1024;
				case 'T' :
					$ret *= 1024;
				case 'G' :
					$ret *= 1024;
				case 'M' :
					$ret *= 1024;
				case 'K' :
					$ret *= 1024;
			}
			return $ret;
		}
	}
}

// Initialize the WooZoneLiteServerStatus class
$WooZoneLiteServerStatus = WooZoneLiteServerStatus::getInstance();
