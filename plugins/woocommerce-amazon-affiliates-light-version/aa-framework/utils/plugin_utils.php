<?php
/*
* Define class WooZoneLite_PluginUtils
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
!defined('ABSPATH') and exit;
if (class_exists('WooZoneLite_PluginUtils') != true) {
    class WooZoneLite_PluginUtils
    {
        /*
        * Some required plugin information
        */
        const VERSION = '1.0';

        /*
        * Store some helpers config
        */
        public $the_plugin = null;
        public $amzHelper = null;

        static protected $_instance;
        
    
        /*
        * Required __construct() function that initalizes the AA-Team Framework
        */
        public function __construct( $parent )
        {
            $this->the_plugin = $parent;
            $this->amzHelper = $this->the_plugin->amzHelper;
        }
        
        /**
        * Singleton pattern
        *
        * @return Singleton instance
        */
        static public function getInstance( $parent )
        {
            if (!self::$_instance) {
                self::$_instance = new self($parent);
            }
            
            return self::$_instance;
        }
	
	
		/**
		 * Plugin Data & Status & Version Updates
		 */
        public function get_plugin_data()
        {
            $source = file_get_contents( $this->the_plugin->cfg['paths']['plugin_dir_path'] . "/plugin.php" );
            $tokens = token_get_all( $source );
            $data = array();
            if( trim($tokens[1][1]) != "" ){
                $__ = explode("\n", $tokens[1][1]);
                foreach ($__ as $key => $value) {
                    $___ = explode(": ", $value);
                    if( count($___) == 2 ){
                        $data[trim(strtolower(str_replace(" ", '_', $___[0])))] = trim($___[1]);
                    }
                }               
            }
  
			// For another way to implement it:
			//		see wp-admin/includes/update.php function get_plugin_data
			//		see wp-includes/functions.php function get_file_data
            return $data;  
        }

		/*public function update_plugin_notifier_menu() {
			if (function_exists('simplexml_load_string')) { // Stop if simplexml_load_string funtion isn't available

				// Don't display notification in admin bar if it's disabled or the current user isn't an administrator
				//if ( !is_super_admin() )
				//return;
				
				// Get the latest remote XML file on our server
				// The time interval for the remote XML cache in the database (21600 seconds = 6 hours)
				$xml = $this->get_latest_plugin_version( $this->the_plugin->notifier_cache_interval() );
				$latest_version = (string)$xml->latest_version;

				if ( is_admin() ) {
					// Read plugin current version from the main plugin file
					$plugin_data = get_plugin_data( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'plugin.php' );
	
					if( isset($plugin_data) && count($plugin_data) > 0 ){
						$current_version = (string)$plugin_data['Version'];
	
						// Compare current plugin version with the remote XML version
						//if (1) { //DEBUG
						if ( version_compare( $latest_version, $current_version, '>' ) ) {
						//if ( $latest_version > $current_version ) {

							$short_name = explode(' ', $plugin_data['Name']);
							$short_name = isset($short_name[0]) ? $short_name[0] : '';
							$short_name = $this->the_plugin->pluginName;
	
							add_dashboard_page(
								$short_name . __(' Plugin Updates', $this->the_plugin->localizationName),
								$short_name . __(' <span class="update-plugins count-1"><span class="update-count">New Updates</span></span>', $this->the_plugin->localizationName),
								'administrator',
								$this->the_plugin->alias . '-plugin-update-notifier',
								array( $this, 'update_notifier' )
							);
						}
					}
				} // end is_admin
			}
		}

		public function update_notifier() {
			$xml = $this->get_latest_plugin_version( $this->the_plugin->notifier_cache_interval() );
			
			// Read plugin current version from the main plugin file
			$plugin_data = get_plugin_data( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'plugin.php' );
			$plugin_folder = $this->the_plugin->plugin_details['folder'];
		?>

			<style type="text/css">
			.update-nag { display: none; }
			#instructions {max-width: 670px;}
			h3.title {margin: 30px 0 0 0; padding: 30px 0 0 0; border-top: 1px solid #ddd;}
			</style>

			<div class="wrap">

			<div id="icon-tools" class="icon32"></div>
			<h2><?php echo $plugin_data['Name'] ?> Plugin Updates</h2>
			<div id="message" class="updated below-h2"><p><strong>There is a new version of the <?php echo $plugin_data['Name'] ?> plugin available.</strong> You have version <?php echo $plugin_data['Version']; ?> installed. Update to version <?php echo $xml->latest_version; ?>.</p></div>
			<div id="instructions">
			<h3>Update Download and Instructions</h3>
			<p><strong>Please note:</strong> make a <strong>backup</strong> of the Plugin inside your WordPress installation folder <strong>/wp-content/plugins/<?php echo $plugin_folder; ?></strong></p>
			<p>To update the Plugin, login to <a href="http://www.codecanyon.net/?ref=AA-Team">CodeCanyon</a>, head over to your <strong>downloads</strong> section and re-download the plugin like you did when you bought it.</p>
			<p>Extract the zip's contents, look for the extracted plugin folder, and after you have all the new files upload them using FTP to the <strong>/wp-content/plugins/<?php echo $plugin_folder; ?></strong> folder overwriting the old ones (this is why it's important to backup any changes you've made to the plugin files).</p>
			<p>If you didn't make any changes to the plugin files, you are free to overwrite them with the new ones without the risk of losing any plugins settings, and backwards compatibility is guaranteed.</p>
			</div>
			<h3 class="title">Changelog</h3>
			<?php echo isset($xml->changelog) && !empty($xml->changelog) ? $xml->changelog : $this->changelog(); ?>

			</div>
		<?php
		}

		public function update_notifier_bar_menu() {
			if (function_exists('simplexml_load_string')) { // Stop if simplexml_load_string funtion isn't available
				global $wp_admin_bar;

				// Don't display notification in admin bar if it's disabled or the current user isn't an administrator
				if ( !is_super_admin() || !is_admin_bar_showing() )
				return;

				// Get the latest remote XML file on our server
				// The time interval for the remote XML cache in the database (21600 seconds = 6 hours)
				$xml = $this->get_latest_plugin_version( $this->the_plugin->notifier_cache_interval() );
				$latest_version = (string)$xml->latest_version;

				if ( is_admin() ) {
				 	// Read plugin current version from the main plugin file
					$plugin_data = get_plugin_data( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'plugin.php' );

					if( isset($plugin_data) && count($plugin_data) > 0 ){
						$current_version = (string)$plugin_data['Version'];

						// Compare current plugin version with the remote XML version
						//if (1) { //DEBUG
						if ( version_compare( $latest_version, $current_version, '>' ) ) {
						//if ( $latest_version > $current_version ) {

							$short_name = explode(' ', $plugin_data['Name']);
							$short_name = isset($short_name[0]) ? $short_name[0] : '';
							$short_name = $this->the_plugin->pluginName;
	
							$wp_admin_bar->add_menu(
								array(
									'id' => $this->the_plugin->alias.'_plugin_update_notifier',
									'title' => '<span>' . ( $short_name ) . __(' <span id="ab-updates">New Updates</span></span>', $this->the_plugin->localizationName),
									'href' => get_admin_url() . 'index.php?page=' . ( $this->the_plugin->alias ) . '-plugin-update-notifier'
								)
							);
						}
					}
				} // end is_admin
			}
		}

		public function get_latest_plugin_version($interval) {
			$responseType = 'base64';

			$base = array();
			$notifier_file_url = $this->the_plugin->plugin_row_meta('latest_ver_url') . $this->the_plugin->alias . '&action=version&rt=' . $responseType;
			$db_cache_field = $this->the_plugin->alias . '_notifier-cache';
			$db_cache_field_last_updated = $this->the_plugin->alias . '_notifier-cache-last-updated';
			$last = get_option( $db_cache_field_last_updated );
			$now = time();
   
			// check the cache
			if ( !$last || (( $now - $last ) > $interval) ) {
				// cache doesn't exist, or is old, so refresh it
				if( function_exists('curl_init') ) { // if cURL is available, use it...
					$ch = curl_init($notifier_file_url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_TIMEOUT, 10);
					$cache = curl_exec($ch);
					curl_close($ch);
				} else {
					// ...if not, use the common file_get_contents()
					$cache = file_get_contents($notifier_file_url);
				}
 
				if ($cache) {
					// we got good results
					update_option( $db_cache_field, $cache );
					update_option( $db_cache_field_last_updated, time() );
				}

				// read from the cache file
				$notifier_data = get_option( $db_cache_field );
			}
			else {
				// cache file is fresh enough, so read from it
				$notifier_data = get_option( $db_cache_field );
			}
    
			if ( 'xml' == $responseType ) {}
			else {
				$notifier_data = @unserialize( @base64_decode( $notifier_data ) );
				$notifier_data = '<?xml version="1.0" encoding="UTF-8"?><notifier><latest_version>' . ( isset($notifier_data['latest_version']) ? (string)$notifier_data['latest_version'] : '1.0' ) . '</latest_version><changelog></changelog></notifier>';
			}
			
			// Let's see if the $xml data was returned as we expected it to.
			// If it didn't, use the default 1.0 as the latest version so that we don't have problems when the remote server hosting the XML file is down
			if( strpos((string)$notifier_data, '<notifier>') === false ) {
				$notifier_data = '<?xml version="1.0" encoding="UTF-8"?><notifier><latest_version>1.0</latest_version><changelog></changelog></notifier>';
			}

			// Load the remote XML data into a variable and return it
			$xml = simplexml_load_string($notifier_data);

			return $xml;
		}

		public function changelog()
		{
			$html = array();
			$html[] = '<textarea style="height: 500px; width: 100%;">';
			$changelog_file = file_get_contents( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '/changelog.txt' );
			$html[] = $changelog_file;
			$html[] = '</textarea>';
			
			return implode("\n", $html);
		}*/
		
		public function plugin_row_meta_filter( $links, $file ) {
			$plugin_folder = $this->the_plugin->plugin_details['folder_index'];
   
			if ( $file == $plugin_folder ) {
				$row_meta = array(
					'docs'    => '<a href="' . esc_url( apply_filters( $this->the_plugin->alias.'_docs_url', $this->the_plugin->plugin_row_meta('docs_url') ) ) . '" title="' . esc_attr( __( 'View Documentation', $this->the_plugin->localizationName ) ) . '">' . __( 'Docs', $this->the_plugin->localizationName ) . '</a>',
					'support' => '<a href="' . esc_url( apply_filters( $this->the_plugin->alias.'_support_url', $this->the_plugin->plugin_row_meta('support_url') ) ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', $this->the_plugin->localizationName ) ) . '">' . __( 'Support Forum', $this->the_plugin->localizationName ) . '</a>',
				);
	
				return array_merge( $links, $row_meta );
			}
	
			return (array) $links;
		}
		
		/*public function update_plugins_overwrite( $transient ) {
	   
			if ( empty($transient->checked) ) {
				return $transient;
			}
			
			$plugin_folder = $this->the_plugin->plugin_details['folder_index'];

			// Read plugin current version from the main plugin file
			$plugin_data = get_plugin_data( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'plugin.php' );
			$current_version = (string)$plugin_data['Version'];
							
			$xml = $this->get_latest_plugin_version( $this->the_plugin->notifier_cache_interval() );
			$latest_version = (string)$xml->latest_version;
			
			// Compare current plugin version with the remote XML version
			if ( version_compare( $latest_version, $current_version, '>' ) ) {
			//if ( $latest_version > $current_version ) {
   
				$obj = new stdClass();
				$obj->slug = $this->the_plugin->alias;
				$obj->new_version = $latest_version;
				$obj->url = $this->the_plugin->plugin_row_meta('buy_url');
				$obj->package = '';
				$obj->name = $this->the_plugin->pluginName;
				$obj->plugin = $plugin_folder;
				$transient->response[ "$plugin_folder" ] = $obj;
			}
			return $transient;
		}
		
		public function plugins_api_overwrite( $false, $action, $arg ) {

			$responseType = 'base64';

			if ( !isset($arg->slug) || $arg->slug !== $this->the_plugin->alias ) {
				return $false;
			}
			
			$plugin_folder = $this->the_plugin->plugin_details['folder_index'];

			// Read plugin current version from the main plugin file
			$plugin_data = get_plugin_data( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'plugin.php' );

			// Get the latest remote XML file on our server
			$notifier_file_url = $this->the_plugin->plugin_row_meta('latest_ver_url') . $this->the_plugin->alias . '&action=info&rt=' . $responseType;
			{
				if( function_exists('curl_init') ) { // if cURL is available, use it...
					$ch = curl_init($notifier_file_url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_TIMEOUT, 10);
					$cache = curl_exec($ch);
					curl_close($ch);
				} else {
					// ...if not, use the common file_get_contents()
					$cache = file_get_contents($notifier_file_url);
				}
				
				if ( empty($cache) ) {
					return $false;
				}
				
				if ( 'xml' == $responseType ) {
					// Let's see if the $xml data was returned as we expected it to.
					// If it didn't, use the default 1.0 as the latest version so that we don't have problems when the remote server hosting the XML file is down
					if( strpos((string)$cache, '<notifier>') === false ) {
						return false;
					}

					// Load the remote XML data into a variable and return it
					$cache_ = simplexml_load_string($cache);
					$cache = array(
						'latest_version' 	=> isset($cache_->latest_version) ? $cache_->latest_version : '',
						'name' 				=> isset($cache_->name) ? $cache_->name : '',
						'last_updated' 		=> isset($cache_->last_updated) ? $cache_->last_updated : '',
						'description' 		=> isset($cache_->description) ? $cache_->description : '',
						'changelog' 		=> isset($cache_->changelog) ? $cache_->changelog : '',
					);
				}
				else {
					$cache = unserialize( base64_decode( $cache ) );
				}
				
				if ( !isset($cache['latest_version'], $cache['name'], $cache['changelog'])
					|| empty($cache['latest_version']) || empty($cache['name']) || empty($cache['changelog']) ) {
					return $false;
				}
				
				$array_pattern = array(
					'/^(#+.*)/m',
					'/^\n+|^[\t\s]*\n+/m',
					'/\n/',
				);
				$array_replace = array(
					'<h4>$1</h4>',
					'</div><div>',
					'</div><div>'
				);
				
				$cache['changelog'] = preg_replace( $array_pattern, $array_replace, $cache['changelog'] );
				$cache['changelog'] = '<div>' . $cache['changelog'] . '</div>';
				
				$obj = new stdClass();
				$obj->slug = $this->the_plugin->alias;
				$obj->new_version = $cache['latest_version'];
				$obj->name = $cache['name'];
				if ( isset($cache['last_updated']) ) {
					$obj->last_updated = $cache['last_updated'];
				}
				$obj->sections = array(
					'description'	=> isset($cache['description']) ? $cache['description'] : $plugin_data['Description'],
					'changelog'		=> $cache['changelog'],
				);
			}
			return $obj;
	 	}
	
		public function in_plugin_update_message( $plugin_data, $r ) {

			$plugin_folder = $this->the_plugin->plugin_details['folder_index'];
			
			// Read plugin current version from the main plugin file
			$plugin_data = get_plugin_data( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'plugin.php' );
			$sanitize_title = strtolower(str_replace(' ', '-', $plugin_data['Name']));
			$sanitize_title = preg_replace('/-(-+)/imu', '-', $sanitize_title);
  
			// remove "Automatic update is unavailable for this plugin" text
			echo '<style type="text/css" media="all">tr#'.$sanitize_title.' + tr.plugin-update-tr a.thickbox + em { display: none; }</style>';
			
			// verify validation!
			//if ( 1 ) {
			if ( $this->the_plugin->get_plugin_status() != 'valid_hash' ) {
				echo ' <a href="' . $this->the_plugin->plugin_row_meta('buy_url') . '">' . __( 'Download new version from Envato.', $this->the_plugin->localizationName ) . '</a>';
			} else {
				//echo '<a href="' . wp_nonce_url( admin_url( 'update.php?action=upgrade-plugin&plugin=' . $plugin_folder ), 'upgrade-plugin_' . $plugin_folder ) . '">' . sprintf( __( 'Update %s now.', $this->the_plugin->localizationName ), $this->the_plugin->pluginName ) . '</a>';
				echo ' <a href="' . $this->the_plugin->plugin_row_meta('buy_url') . '">' . sprintf( __( 'Download %s from Envato now.', $this->the_plugin->localizationName ), $this->the_plugin->pluginName ) . '</a>';
			}
		}*/
	}
}