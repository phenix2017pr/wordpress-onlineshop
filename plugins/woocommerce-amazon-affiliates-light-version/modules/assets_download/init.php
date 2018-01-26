<?php
/*
* Define class WooZoneLiteAssetDownload
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
!defined('ABSPATH') and exit;

if ( !class_exists('WooZoneLiteAssetDownload') ) {
    class WooZoneLiteAssetDownload
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
		
		private $settings;
		private $default_import;


        /*
        * Required __construct() function that initalizes the AA-Team Framework
        */
        public function __construct( $is_cron=false )
        {
        	global $WooZoneLite;

        	$this->the_plugin = $WooZoneLite;
			$this->module_folder = $this->the_plugin->cfg['paths']['plugin_dir_url'] . 'modules/assets_download/';
			$this->module = $this->the_plugin->cfg['modules']['assets_download'];
			
			$this->settings = $WooZoneLite->getAllSettings('array', 'amazon');
			
			$this->default_import = !isset($this->settings["default_import"])
                || ($this->settings["default_import"] == 'publish')
                ? 'publish' : 'draft';
			$this->default_import = strtolower($this->default_import);
  
			if (is_admin() && !$is_cron) {
	            add_action('admin_menu', array( &$this, 'adminMenu' ));
			}
			
			add_action('wp_ajax_WooZoneLite_download_asset', array( &$this, 'ajax_download_asset' ));
			add_action('wp_ajax_WooZoneLiteDeleteAssetsProducts', array( &$this, 'delete_products_asset' ));
			//$this->__test_assets();
        }

		/**
	    * Singleton pattern
	    *
	    * @return WooZoneLiteAssetDownload Singleton instance
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
    			$this->the_plugin->alias . " " . __('Assets Download', $this->the_plugin->localizationName),
	            __('Assets Download', $this->the_plugin->localizationName),
	            'manage_options',
	            $this->the_plugin->alias . "_assets_download",
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
?>
		<link rel='stylesheet' href='<?php echo $this->module_folder;?>app.assets_download.css' type='text/css' media='all' />
		<!-- <div id="WooZoneLite-wrapper" class="fluid wrapper-WooZoneLite WooZoneLite-asset-download"> -->
		<div id="<?php echo WooZoneLite()->alias?>" class="WooZoneLite-asset-download">
			
			<div class="<?php echo WooZoneLite()->alias?>-content">
				
				<?php
				// show the top menu
				WooZoneLiteAdminMenu::getInstance()->make_active('info|assets_download')->show_menu();
				?>
	
				<!-- Content -->
				<section class="WooZoneLite-main">
					
					<?php 
					echo WooZoneLite()->print_section_header(
						$this->module['assets_download']['menu']['title'],
						$this->module['assets_download']['description'],
						$this->module['assets_download']['help']['url']
					);
					?>
					
					<div class="panel panel-default WooZoneLite-panel">
						<div class="panel-heading WooZoneLite-panel-heading">
							<h2><?php _e('Assets Download', $this->the_plugin->localizationName);?></h2>
						</div>
						
						<div class="panel-body WooZoneLite-panel-body">
							
							<!-- Content Area -->
							 <h4 style="color:red;"><?php _e('Amazon Remote Images ACTIVE, you cannot download amazon images.', $this->the_plugin->localizationName);?></h4>
						</div>
					</div>
				</section>
			</div>
		</div>
		<script type="text/javascript" src="<?php echo $this->module_folder;?>app.assets_download.js" ></script>

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

		
		public function delete_products_asset()
		{
			$request = array(
				'products' => isset($_REQUEST['products']) ? $_REQUEST['products'] : array()
			);
			
			if( count($request['products']) > 0 ){
				
				foreach ($request['products'] as $prod_id ) {
					$this->product_assets_delete( $prod_id );
				}
				
				die( json_encode(array(
					'status' => 'valid'
				)) ); 
			}
			
			die( json_encode(array(
				'status'		=> 'invalid',
				'msg'			=> 'Unable to delete products assets'
			)) ); 
		}
			
		/**
		 * download assets
		 */
		public function ajax_download_asset() 
		{
            $this->the_plugin->timer_start(); // Start Timer

			$request = array(
				'id' 			=> isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0,
				'is_last_item'	=> isset($_REQUEST['is_last_item']) && $_REQUEST['is_last_item'] == 'yes' ? true : false,
				'is_first_item'	=> isset($_REQUEST['is_first_item']) && $_REQUEST['is_first_item'] == 'yes' ? true : false
			);
  
			$asset_id = $request['id'];
			if ( $asset_id == 0 ) {
				die( json_encode(array(
					'status'		=> 'invalid'
				)) );
			}
			
			$allowedRation = (int) $this->settings['ratio_prod_validate'];
			if ( $allowedRation <= 0 || $allowedRation > 100 ) $allowedRation = 90;
			
			$importProdStatus = $this->default_import;
 
			$asset = $this->get_asset_by_id( $asset_id, true, true );
			$asset = array_shift( $asset );
			$updAssetStat = $this->upd_asset_db( $asset, $request['is_first_item'] );
			if ( $request['is_last_item'] ) {
				$updProdStat = $this->upd_prod_poststable($asset->post_id, $importProdStatus, $allowedRation);
			}

			$msg = ''; $msg_last = '';
			if ( $updAssetStat!== false ) {
				if ( isset($updAssetStat['status']) && $updAssetStat['status']=='valid' ) {
					$msg = '"' . $asset->asset . '" (ID ' . $asset->id . ') ' . __( 'was successfully downloaded and resized in', $this->the_plugin->localizationName ) . ' <code>{execution_time}</code>.';
				} else {
					$msg = '"' . $asset->asset . '" (ID ' . $asset->id . ') ' . __( 'could not be downloaded and resized - duration: ', $this->the_plugin->localizationName ) . ' <code>{execution_time}</code>.<br /><span style="color: red;">' . $updAssetStat['status'] . '</span>';
				}
			} else {
				$msg = '"' . $asset->asset . '" (ID ' . $asset->id . ') ' . __( 'could not be downloaded and resized - duration: ', $this->the_plugin->localizationName ) . ' <code>{execution_time}</code>.';
			}
			die( json_encode(array(
				'status'		=> 'valid',
				'msg' 			=> $msg, //'"img_3_large" (ID 202) was successfully downloaded and resized in <code>{execution_time}</code>.',
				'msg_last'		=> $msg_last,
				'data'			=> $request['id']
			)) );
		}
		
		public function get_asset_by_id( $asset_id, $inprogress=false, $include_err=false, $include_invalid_post=false ) {
			return $this->get_asset_generic($asset_id, 1, 0, false, $inprogress, $include_err, $include_invalid_post);
		}
		
		public function get_asset_by_postid( $nb_dw, $post_id, $include_variations, $inprogress=false, $include_err=false, $include_invalid_post=false ) {
			return $this->get_asset_generic(0, $nb_dw, $post_id, $include_variations, $inprogress, $include_err, $include_invalid_post);
		}
		
		public function get_asset_multiple( $nb_dw='all', $inprogress=false, $include_err=false ) {
			return $this->get_asset_generic(0, $nb_dw, 0, true, $inprogress, $include_err, $include_invalid_post);
		}

		private function get_asset_generic( $asset_id=0, $nb_dw='all', $post_id=0, $include_variations=true, $inprogress=false, $include_err=false, $include_invalid_post=false ) {
			global $wpdb;
			
			$asset_id = (int) $asset_id;
			$post_id = (int) $post_id;

			$tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products', 'posts' => $wpdb->prefix . 'posts');

			if ( $include_err ) $__q_dw = "and (a.download_status in ('new', 'error') or isnull(a.download_status) or a.download_status = '')";
			else $__q_dw = "and a.download_status = 'new'";
			$q = "select a.id, a.post_id, a.asset, a.thumb, a.download_status, a.hash, a.media_id, a.image_sizes, b.title from " . $tables['assets'] . " as a left join " . $tables['products'] . " as b on a.post_id = b.post_id where 1=1 $__q_dw ";
            if ( !$include_invalid_post ) {
                $q = "select a.id, a.post_id, a.asset, a.thumb, a.download_status, a.hash, a.media_id, a.image_sizes, b.title from " . $tables['assets'] . " as a left join " . $tables['products'] . " as b on a.post_id = b.post_id left join " . $tables['posts'] . " as c on b.post_id = c.ID where 1=1 and !isnull(b.post_id) and !isnull(c.ID) $__q_dw ";
            }
			if ( is_int($asset_id) && $asset_id > 0 ) {
				$q .= "and a.id = '$asset_id' ";
			}
			if ( is_int($post_id) && $post_id > 0 ) {
				if ( $include_variations ) {
					$q .= "and ( a.post_id = '$post_id' or b.post_parent = '$post_id' ) ";
				} else {
					$q .= "and a.post_id = '$post_id' ";
				}
			}
			$q .= "order by a.id asc ";

			if ( $nb_dw == 'all' ) ;
			else {
				$nb_dw = (int) $nb_dw;
				$q .= "limit 0, $nb_dw";
			}
			$q .= ";";
			$res = $wpdb->get_results( $q, OBJECT );

			$ret = array();
			if (is_array($res) && count($res)>0) {
				foreach ($res as $k=>$v) {
					$ret["{$v->id}"] = $v;
				}
			}
			
			// all selected assets have in progress status now!
			if ( $inprogress && !empty($ret) ) {
				$idList = implode(', ', array_map(array($this, 'prepareForInList'), array_keys($ret)));
				$qUpdStat = "update " . $tables['assets'] . " as a set a.download_status = 'inprogress' where 1=1 and a.id in ( $idList );";
				$statUpdStat = $wpdb->query($qUpdStat);
				if ($statUpdStat=== false) {
				}
			}
			return $ret;
		}

		private function upd_asset_db( $asset, $first_item=false ) {
			global $wpdb;

			$tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products');

			if ( !is_object($asset) && is_int($asset) && ($asset > 0) ) {
				
				$q = "select a.id, a.post_id, a.asset, a.download_status, a.hash, a.media_id, b.title from " . $tables['assets'] . " as a left join " . $tables['products'] . " as b on a.post_id = b.post_id where 1=1 and a.id = $asset;";
				$asset = $wpdb->get_row( $q, OBJECT );
			}
  
			if ( empty($asset) ) return false;
			
			$asset_id = $asset->id;
			$post_id = $asset->post_id;

			$dwimg = $this->the_plugin->download_image($asset->asset, $asset->post_id, 'insert', $asset->title, 0);
			$dwStatus = false;
  
			if ( isset($dwimg['attach_id']) && $dwimg['attach_id'] > 0 ) { // image was downloaded and inserted as media in wp posts
				$dwStatus = true;
			
				// product featured image
				//if ( $first_item ) {
				//	update_post_meta($post_id, "_thumbnail_id", $dwimg['attach_id']);
				//} else {
					$current_thumb_id = get_post_meta($post_id, "_thumbnail_id", true);
					if ( empty($current_thumb_id) ) $current_thumb_id = 0;
					else $current_thumb_id = (int) $current_thumb_id;
					if ( $current_thumb_id == 0 || ( $current_thumb_id > $dwimg['attach_id'] ) ) {
						update_post_meta($post_id, "_thumbnail_id", $dwimg['attach_id']);
                    }
				//}

				// build product gallery
				$current_prod_gallery = get_post_meta($post_id, "_product_image_gallery", true);
				if ( empty($current_prod_gallery) ) $__current_prod_gallery = array();
				else $__current_prod_gallery = explode(',', $current_prod_gallery);
				$__current_prod_gallery = array_merge( $__current_prod_gallery, array($dwimg['attach_id']) );
				$__current_prod_gallery = array_unique($__current_prod_gallery);
				update_post_meta($post_id, "_product_image_gallery", implode(',', $__current_prod_gallery));
			}
			
			$mediaValues = (object) array(
				'download_status'		=> ( $dwStatus ? 'success' : 'error' ),
				'hash'				=> ( $dwStatus ? $dwimg['hash'] : null ),
				'media_id'			=> ( $dwStatus ? $dwimg['attach_id'] : 0 ),
				'msg'				=> ( $dwStatus ? 'success' : $dwimg['msg'] )
			);
			
			// update row in assets table
			$statUpdAsset = $wpdb->update(
				$tables['assets'],
				array(
					'download_status'	=> $mediaValues->download_status,
					'hash'				=> $mediaValues->hash,
					'media_id'			=> $mediaValues->media_id,
					'date_download'		=> date("Y-m-d H:i:s"),
					'msg'				=> $mediaValues->msg
				),
				array( 'id' => $asset_id ),
				array(
					'%s', '%s', '%d', '%s', '%s'
				),
				array( '%d' )
			);
			if ($statUpdAsset === false || !$dwStatus) {
				return array(
					'status'		=> 'invalid',
					'msg'			=> $mediaValues->msg
				);
			}
			
			// update row in products table
			/*$wpdb->update(
				$tables['products'],
				array(
					'nb_assets_done'		=> $nb_assets_done + 1
				),
				array( 'post_id' => $post_id ),
				array(
					'%d'
				),
				array( '%d' )
			);*/
			$qUpdProd = "update " . $tables['products'] . " as a set a.nb_assets_done = a.nb_assets_done + 1 where 1=1 and a.post_id = $post_id;";
			$statUpdProd = $wpdb->query($qUpdProd);

            if ( 1 ) {
                $this->the_plugin->add_last_imports('last_import_images_download', array(
                    'duration'      => $this->the_plugin->timer_end(),
                )); // End Timer & Add Report
            }

			if ($statUpdProd=== false) {
			}
			return array(
				'status'		=> 'valid',
				'msg'			=> sprintf( __('%s was successfully downloaded', $this->the_plugin->localizationName), $asset->asset ),
			);
		}

		public function upd_prod_poststable( $post_id, $new_status='draft', $allowedRatio='75' ) {
			global $wpdb;

			$tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products');

			$q = "select a.post_id, a.post_parent, (a.nb_assets_done / a.nb_assets) * 100 as ratio from " . $tables['products'] . " as a where 1=1 and ( a.post_id = $post_id or a.post_parent = $post_id );";
			$res = $wpdb->get_results( $q, OBJECT );

			$ret = array(); $ratioTotal = array();
			if (is_array($res) && count($res)>0) {
				foreach ($res as $k=>$v) {
					$key = empty($v->post_parent) ? $v->post_id : $v->post_parent;
					$ret["$key"] = $v;
					$ratioTotal[] = (int) $v->ratio;
				}
			}
			if ( empty($ret) ) return false;
			
			$ratioTotal = ( array_sum($ratioTotal) / count($ret) );
			$ratioTotal = number_format( $ratioTotal, 2 );

			// verify if ratio allow product update in wp posts table!
			if ( $ratioTotal < $allowedRatio ) {
				return false;
			}

			$idList = implode(', ', array_map(array($this, 'prepareForInList'), array_keys($ret)));

			$qUpdProd = "update " . $tables['products'] . " as a set a.status = 'success' where 1=1 and ( a.post_id in ( $idList ) or a.post_parent in ( $idList ) );";
			$statUpdProd = $wpdb->query($qUpdProd);
			if ($statUpdProd=== false) {
			}
			
			$qUpdStat = "update " . ($wpdb->prefix . 'posts') . " as a set a.post_status = '$new_status' where 1=1 and a.ID in ( $idList );";
			$statUpdStat = $wpdb->query($qUpdStat);
			if ($statUpdStat=== false) {
				return false;
			}
			return true;
		}
		
		public function verifyProdImageHash( $hash ) {
			global $wpdb;

			$tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products');

			$q = "select a.id, a.post_id, a.media_id from " . $tables['assets'] . " as a where 1=1 and a.hash regexp '$hash' limit 1;";
			$res = $wpdb->get_row( $q, OBJECT );
			
			if ( !empty($res) && isset($res->media_id) ) {
				$attach = wp_get_attachment_metadata( $res->media_id );
				$file = is_array($attach) && !empty($attach) && isset($attach['file']) ? $attach['file'] : '';
				
				// Find Upload dir path
				$uploads = wp_upload_dir();
				$uploads_path = $uploads['path'] . '';

				$image_path = $uploads_path . '/' . basename($file);
				if ( !empty($file) && $this->the_plugin->verifyFileExists($image_path) ) {
					$res->image_path = $image_path;
					return $res;
				}
			}
			return false;
		}
		
		public function cronjob( $pms, $return='die' ) {
		    $ret = array('status' => 'failed');

            $current_cron_status = $pms['status']; //'new'; //
            
            if ( $this->the_plugin->is_remote_images ) {
	            $ret = array_merge($ret, array(
	                'status'            => 'done',
	            ));
	            return $ret;
			}

			$cronNbImages = (int) $this->settings['cron_number_of_images'];
            $cronNbImages = $cronNbImages <= 0 || $cronNbImages > 500 ? 500 : $cronNbImages;
			$assetsList = $this->get_asset_multiple( $cronNbImages, true );
			if ( count($assetsList) <= 0 ) return $ret; //false;

            $cc = 1; $len = count($assetsList); 
            $durationQueue = array();

			$post_id_list = array();
			foreach( $assetsList as $k=>$asset ) {
                $this->the_plugin->timer_start(); // Start Timer

				$stat = $this->upd_asset_db( $asset );
                if ( isset($stat['status']) && $stat['status'] == 'valid' ) {
                    $durationQueue[] = $this->the_plugin->timer_end(); // End Timer
                }
				$post_id_list[] = $asset->post_id;
                
                if ( !empty($durationQueue) && ( ( count($durationQueue) % 10 == 0 ) || $cc == $len ) ) {
                    if ( 1 ) {
                        $this->the_plugin->add_last_imports('last_import_images_download', array(
                            'duration'      => round( array_sum($durationQueue) / count($durationQueue), 4 ),
                        )); // End Timer & Add Report
                    }
                    $durationQueue = array();
                }
                $cc++;
			}
			var_dump('<pre>products: ',$post_id_list,'</pre>');  
  
			// update product status for the above assets products!
			$allowedRation = (int) $this->settings['ratio_prod_validate'];
			if ( $allowedRation <= 0 || $allowedRation > 100 ) $allowedRation = 90;
			
			$importProdStatus = $this->default_import;

			if ( !empty($post_id_list) ) {
				foreach ($post_id_list as $key => $value) {
					$updProdStat = $this->upd_prod_poststable($value, $importProdStatus, $allowedRation);
					var_dump('<pre>',$value, $updProdStat,'</pre>'); 
				}
			}

            $ret = array_merge($ret, array(
                'status'            => 'done',
            ));
            return $ret;
		}

		public function product_assets_download( $post_id ) {
			//$ret = array('status' => 'failed');
            $ret = array(
                'status'        => 'invalid',
                'msg'          => '',
            );
			
			$assetsList = $this->get_asset_by_postid( 'all', $post_id, true );
			if ( count($assetsList) <= 0 ) {
				$ret = array_merge($ret, array(
					'msg'		=> __('no assets available for download', $this->the_plugin->localizationName),
				));
				return $ret;
			}
			$msg = array();

            $cc = 1; $len = count($assetsList); 
            $durationQueue = array();

			$post_id_list = array();
			foreach( $assetsList as $k=>$asset ) {
                $this->the_plugin->timer_start(); // Start Timer

				$stat = $this->upd_asset_db( $asset );
                if ( isset($stat['status']) && $stat['status'] == 'valid' ) {
                    $durationQueue[] = $this->the_plugin->timer_end(); // End Timer
                }
				if ( isset($stat['msg']) ) {
					$msg[] = $stat['msg'];
				} else {
					$msg[] = __('empty asset', $this->the_plugin->localizationName);
				}

				$post_id_list[] = $asset->post_id;
                
                if ( !empty($durationQueue) && ( ( count($durationQueue) % 10 == 0 ) || $cc == $len ) ) {
                    if ( 1 ) {
                        $this->the_plugin->add_last_imports('last_import_images_download', array(
                            'duration'      => round( array_sum($durationQueue) / count($durationQueue), 4 ),
                        )); // End Timer & Add Report
                    }
                    $durationQueue = array();
                }
                $cc++;
			}
			$post_id_list = array_unique( array_filter( $post_id_list ) );
			//var_dump('<pre>products: ',$post_id_list,'</pre>');  
  
			// update product status for the above assets products!
			$allowedRation = (int) $this->settings['ratio_prod_validate'];
			if ( $allowedRation <= 0 || $allowedRation > 100 ) $allowedRation = 90;
			
			$importProdStatus = $this->default_import;

			if ( !empty($post_id_list) ) {
				foreach ($post_id_list as $key => $value) {
					$updProdStat = $this->upd_prod_poststable($value, $importProdStatus, $allowedRation);
					//var_dump('<pre>',$value, $updProdStat,'</pre>'); 
				}
			}

            //$ret = array_merge($ret, array(
            //    'status'            => 'done',
            //));
			$ret = array_merge($ret, array(
				'status' 	=> 'valid',
				'msg'		=> implode('<br />', $msg),
			));
            return $ret;
		}
		
        public function product_assets_delete( $post_id ) {
            global $wpdb;
            
            $post_id = (int) $post_id;

            $tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products');

            $q = "select a.post_id from " . $tables['products'] . " as a where 1=1 ";
            $q .= " and ( a.post_id = '$post_id' or a.post_parent = '$post_id' ) ";
            $q .= " order by a.id asc ";
            $q .= ";";

            $res = $wpdb->get_results( $q, OBJECT );

            $ret = array();
            if (is_array($res) && count($res)>0) {
                foreach ($res as $k=>$v) {
                    $ret[] = $v->post_id;
                }
            }
            
            if ( !empty($ret) ) {
                $idList = implode(', ', array_map(array($this, 'prepareForInList'), array_values($ret)));

                $q = "delete from " . $tables['assets'] . " where 1=1 and post_id in ( $idList );";
                $res = $wpdb->query( $q );
                
                $q2 = "delete from " . $tables['products'] . " where 1=1 and post_id in ( $idList );";
                $res2 = $wpdb->query( $q2 );
            }
            return true;
        }


        /**
         * Debuging...
         */
		public function get_assets_bystatus($status) {
			global $wpdb;
			
			$tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products');
			
			$q = "select a.* from " . $tables['assets'] . " as a where 1=1 and a.download_status = '" . $status . "' order by a.id asc;";
			$res = $wpdb->get_results( $q, OBJECT );

			$ret = array();
			if (is_array($res) && count($res)>0) {
				foreach ($res as $k=>$v) {
					$ret["{$v->id}"] = $v;
				}
			}
			var_dump('<pre>', $ret, '</pre>'); die('debug...'); 
		}

        public function update_products_status_all() {
            global $wpdb;

            $tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products');
            
            $q = "select distinct(a.post_id) from " . $tables['products'] . " as a where 1=1 and status = 'new' order by a.post_id asc;";
            $res = $wpdb->get_results( $q, OBJECT );

            $ret = array();
            if (is_array($res) && count($res)>0) {
                foreach ($res as $k=>$v) {
                    $ret["{$v->post_id}"] = $v;
                }
            }
            $post_id_list = array_keys($ret);
            
            if ( empty($post_id_list) || !is_array($post_id_list) ) return false;
            
            $allowedRation = (int) $this->settings['ratio_prod_validate'];
            if ( $allowedRation <= 0 || $allowedRation > 100 ) $allowedRation = 90;
            
            $importProdStatus = $this->default_import;
            
            var_dump('<pre>', 'post_id', 'status','</pre>'); 
            foreach ($post_id_list as $key => $value) {
                // update product status for the above assets products!
                $updProdStat = $this->upd_prod_poststable($value, $importProdStatus, $allowedRation);
                var_dump('<pre>',$value, $updProdStat,'</pre>');  
            }
            echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
        }

		public function __restore_assets_bystatus($status='error') {
			global $wpdb;
			
			$tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products');
			
			$q = "update " . $tables['assets'] . " set download_status = 'new' where 1=1 and download_status = '" . $status . "';";
			$res = $wpdb->query( $q );
			var_dump('<pre>', $res, '</pre>'); die('debug...');  
		}
		
		public function __test_assets() {

			//$this->get_assets_bystatus('error');
			//$this->__restore_assets_bystatus('error');
			
			//$this->get_assets_bystatus('inprogress');
			//$this->__restore_assets_bystatus('inprogress');
			
			// $this->update_products_status_all();

			// $this->get_asset_by_id(6);
			$assets = $this->get_asset_by_postid('all', 43, true);
			if ( !empty($assets) ) {
				// array_shift($assets)
				foreach ($assets as $k=>$v) {
					// $this->upd_asset_db( $v );
				}
			}
			// $this->upd_prod_poststable(83, 'publish', 90);
		}

		
		/**
		 * Utils
		 */
		private function prepareForInList($v) {
			return "'".$v."'";
		}
    }
}

/*if ( !function_exists('WooZoneLiteAssetDownload_cronjob') ) {
function WooZoneLiteAssetDownload_cronjob() {
	// Initialize the WooZoneLiteAssetDownload class
	$amzaffAssetDownload = new WooZoneLiteAssetDownload();
	$amzaffAssetDownload->cronjob();
}
}*/

// Initialize the WooZoneLiteAssetDownload class
$WooZoneLiteAssetDownload = WooZoneLiteAssetDownload::getInstance();