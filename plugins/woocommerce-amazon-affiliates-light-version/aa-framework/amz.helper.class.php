<?php
/**
 *	Author: AA-Team
 *	Name: 	http://codecanyon.net/user/AA-Team/portfolio
 *	
**/
! defined( 'ABSPATH' ) and exit;

if(class_exists('WooZoneLiteAmazonHelper') != true) {
	class WooZoneLiteAmazonHelper extends WooZoneLite
	{
		private $the_plugin = null;
		public $aaAmazonWS = null;
		public $amz_settings = array();
		
		static protected $_instance;
        
        const MSG_SEP = '—'; // messages html bullet // '&#8212;'; // messages html separator
		
		private static $provider = 'amazon';

		public $image_sizes = array(
			'SwatchImage'		=> 'swatch',
			'SmallImage'		=> 'small',
			'ThumbnailImage'	=> 'thumbnail',
			'TinyImage'			=> 'tiny',
			'MediumImage'		=> 'medium',
			'LargeImage'		=> 'large',
		);
		
		private $keysObj = null;

		public $current_country = array(
			'key'	=> '',
			'name'	=> '',
		);


        /**
         * The constructor
         */
		public function __construct( $the_plugin=array() ) 
		{
			$this->the_plugin = $the_plugin; 

			$this->init_settings( array(), true );

			// ajax actions
			add_action('wp_ajax_WooZoneLiteCheckAmzKeys', array( $this, 'check_amazon') );
			add_action('wp_ajax_WooZoneLiteImportProduct', array( $this, 'getProductDataFromAmazon' ), 10, 2);
			
			add_action('wp_ajax_WooZoneLiteStressTest', array( $this, 'stress_test' ));
		}
		
		public function init_settings( $params=array(), $init_setup=true ) {
			// verify amazon keys
			$this->the_plugin->verify_amazon_keys();

			// get all amazon settings options
			$this->amz_settings = $this->the_plugin->amz_settings;

			// create a instance for amazon WS connections
			if ( $init_setup ) {
				$this->setupAmazonWS( $params );
			}
			
			// aateam keys lib
			require_once( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '_keys/keys.php' );
			$this->keysObj = new aaWoozoneKeysLib( $this->the_plugin, array() );
		}
		
		public function setupAmazonWS( $params=array() )
		{
			$settings = $this->amz_settings;
			//var_dump('<pre>params',$params ,'</pre>'); var_dump('<pre>settings', $settings ,'</pre>'); echo __FILE__ . ":" . __LINE__;

			// load the amazon webservices client class
			require_once( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '/lib/scripts/amazon/aaAmazonWS.class.php' );

			$this->the_plugin->cur_provider = self::$provider;

			// create new amazon instance
			$this->aaAmazonWS = new aaAmazonWS(
                isset($params['AccessKeyID']) ? $params['AccessKeyID'] : $settings['AccessKeyID'],
                isset($params['SecretAccessKey']) ? $params['SecretAccessKey'] : $settings['SecretAccessKey'],
                isset($params['country']) ? $params['country'] : $settings['country'],
                isset($params['main_aff_id']) ? $params['main_aff_id'] : $this->the_plugin->main_aff_id()
			);
            $this->aaAmazonWS->set_the_plugin( $this->the_plugin, $settings );

            // current country
            $countries = $this->get_countries('country');
            $ckey = isset($params['country']) ? $params['country'] : $settings['country'];
            $cname = isset($countries["$ckey"]) ? $countries["$ckey"] : '';
            $this->current_country = array(
				'key'	=> $ckey,
				'name'	=> $cname,
            );
		}
		
		/**
	    	* Singleton pattern
	    	*
	    	* @return pspGoogleAuthorship Singleton instance
	    	*/
		static public function getInstance( $the_plugin=array() )
		{
			if (!self::$_instance) {
				self::$_instance = new self( $the_plugin );
			}

			return self::$_instance;
		}
		
		public function stress_test()
		{
			$action = isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : '';
			$return = array();

			$start = microtime(true);

			//header('HTTP/1.1 500 Internal Server Error');
			//exit();
			
			if (!isset($_SESSION)) {
                session_start(); 
			}
			
			if( $action == 'import_images' ){
				
				if( isset($_SESSION["WooZoneLite_test_product"]) && count($_SESSION["WooZoneLite_test_product"]) > 0 ){
					$product = $_SESSION["WooZoneLite_test_product"];

					$this->set_product_images( $product, $product['local_id'], 0, 1 );

					$return = array( 
						'status' => 'valid',
						'log' => "Images added for product: " . $product['local_id'],
						'execution_time' => number_format( microtime(true) - $start, 2),
					);
				}
				
				else{
					$return = array( 
						'status' => 'invalid',
						'log' => 'Unable to create the woocommerce product!'
					);
				}
			}
			
			if( $action == 'insert_product' ){
				if( isset($_SESSION["WooZoneLite_test_product"]) && count($_SESSION["WooZoneLite_test_product"]) > 0 ){
					$product = $_SESSION["WooZoneLite_test_product"];
					
					$insert_id = $this->the_plugin->addNewProduct( $product, array(
                        'import_images' => false,
                    ));
					if( (int) $insert_id > 0 ) {
						
						$_SESSION["WooZoneLite_test_product"]['local_id'] = $insert_id;
						$return = array( 
							'status' => 'valid',
							'log' => "New product added: " . $insert_id,
							'execution_time' => number_format( microtime(true) - $start, 2),
						);
					}
				}
				
				else{
					$return = array( 
						'status' => 'invalid',
						'log' => 'Unable to create the woocommerce product!'
					);
				}
			}

			if( $action == 'get_product_data' ){

				$asin = isset($_REQUEST['ASIN']) ? $_REQUEST['ASIN'] : '';
				if( $asin != "" ) {

					$provider = 'amazon';
					//$rsp = $this->the_plugin->get_ws_object( $provider )->api_make_request(array(
					$rsp = $this->api_make_request(array(
						'amz_settings'			=> $this->the_plugin->amz_settings,
						'from_file'				=> str_replace($this->the_plugin->cfg['paths']['plugin_dir_path'], '', __FILE__),
						'from_func'				=> __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
						'requestData'			=> array(
							'asin'					=> $asin,
						),
						'optionalParameters'	=> array(),
						'responseGroup'			=> 'Large,ItemAttributes,Offers,Reviews',
						'method'				=> 'lookup',
					));
					$product = $rsp['response'];
                    //$product = $this->aaAmazonWS->responseGroup('Large,ItemAttributes,Offers,Reviews')->optionalParameters(array('MerchantId' => 'All'))->lookup( $asin );
					
					$respStatus = $this->is_amazon_valid_response( $product );
					if ( $respStatus['status'] != 'valid' ) { // error occured!
							$return = array(
								'status' => 'invalid',
								'msg'	=> 'ASIN [' . $asin . '] - ' . 'Amazon Error: ' . $respStatus['code'] . ' - ' . $respStatus['msg'],
								'log'	=> $product
							);
					}
					//if($product['Items']["Request"]["IsValid"] == "True"){
					else {

                        $thisProd = isset($product['Items']['Item']) ? $product['Items']['Item'] : array();
						if (1) {
                            // build product data array
                            $retProd = array();
                            $retProd = $this->build_product_data( $thisProd );

							$return = array( 
								'status' => 'valid',
								'log' => $retProd,
								'execution_time' => number_format( microtime(true) - $start, 2),
							);
							
							// save the product into session, for feature using of it
							$_SESSION["WooZoneLite_test_product"] = $retProd;
						}
					}

				} else {
					$return = array(
						'status' => 'invalid',
						'msg'	=> 'Please provide a valid ASIN code!'
					);
				}
			}
			
			die( json_encode($return) );   
		}
		
		public function check_amazon()
		{
			$status = 'valid';
			$msg = '';
	        try {
	            // Do a test connection
				$provider = 'amazon';
				//$rsp = $this->the_plugin->get_ws_object( $provider )->api_make_request(array(
				$rsp = $this->api_make_request(array(
					'amz_settings'			=> $this->the_plugin->amz_settings,
					'from_file'				=> str_replace($this->the_plugin->cfg['paths']['plugin_dir_path'], '', __FILE__),
					'from_func'				=> __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
					'requestData'			=> array(
						//'category'					=> 'DVD',
						//'page'						=> 1,
						//'keyword'					=> 'Matrix',

						// fix july 2016 - Books works for all countries
						'category'					=> 'Books',
						'page'						=> 1,
						'keyword'					=> 'fantasy',
					),
					//'optionalParameters'	=> array(),
					'responseGroup'			=> 'Images',
					'method'				=> 'search',
				));
				$tryRequest = $rsp['response'];
	        	//$tryRequest = $this->aaAmazonWS->category('DVD')->page(1)->responseGroup('Images')->search("Matrix");
				
				$respStatus = $this->is_amazon_valid_response( $tryRequest );
				if ( $respStatus['status'] != 'valid' ) { // error occured!

					$msg = 'Amazon Error: ' . $respStatus['code'] . ' - ' . $respStatus['msg']; 
	                $status = 'invalid';
				}

	        } catch (Exception $e) {
	            // Check 
	            if (isset($e->faultcode)) {
	            	
					$msg = $e->faultcode . ": " . $e->faultstring; 
	                $status = 'invalid';
	            }
	        }

	        if ( ! empty($this->current_country['name']) ) {
        		$msg_country = '** Country: ' . $this->current_country['name'];
       			$msg_country .= ('' != $msg ? PHP_EOL.PHP_EOL : '');

        		$msg = $msg_country . $msg;
	        }
			
        	die(json_encode(array(
				'status' => $status,
				'msg' => $msg
			)));
		}
		
		private function convertMainAffIdInCountry( $main_add_id='' )
		{
			if( $main_add_id == 'com' ) return 'US';
			
			return strtoupper( $main_add_id );
		}
		
		public function getAmazonCategs()
		{
			$country = $this->convertMainAffIdInCountry( $this->amz_settings['main_aff_id'] );
			$csv = $categs = array();
		
			// try to read the plugin_root/assets/browsenodes.csv file
			// check if file exists
			if( !is_file( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'assets/browsenodes.csv' ) ){
				die( 'Unable to load file: ' . $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'assets/browsenodes.csv' );
			}

			$csv_file_content = file_get_contents( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'assets/browsenodes.csv' );
			if( trim($csv_file_content) != "" ){
				$rows = explode("\r", $csv_file_content);
				if( count($rows) > 0 ){
					foreach ($rows as $key => $value) {
						$csv[] = explode(",", $value);
					}
				}
			}
			 
			// find current country in first row 
			$pos = 0;
			if( count($csv[0]) > 0 ){
				foreach ($csv[0] as $key => $value) {
					if( strtoupper($country) == strtoupper($value) ){
						$pos = $key;
					}
				}
			}
			
			if( $pos > 0 && count($csv) > 0 ){
				foreach ($csv as $key => $value) {
					// skip the header row	
					if( $key == 0 ) continue;
					
					if( isset($value[$pos]) && trim($value[$pos]) != "" ){
						$categs[$value[0]] = $value[$pos];
					}
				}
			}
			
			return $categs;  
		}

		public function getAmazonItemSearchParameters()
		{
			$country = $this->convertMainAffIdInCountry( $this->amz_settings['main_aff_id'] );
			$csv = $categs = array();
			
			
			// try to read the plugin_root/assets/searchindexParam-{country}.csv file
			// check if file exists
			if( !is_file( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'assets/searchindexParam-' . ( $country ) . '.csv' ) ){
				die( 'Unable to load file: ' . $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'assets/searchindexParam-' . ( $country ) . '.csv' );
			}
			
        	$csv_file_content = file_get_contents( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'assets/searchindexParam-' . ( $country ) . '.csv' );
			if( trim($csv_file_content) != "" ){
				$rows = explode("\r", $csv_file_content);
				 
				if( count($rows) > 0 ){
					foreach ($rows as $key => $value) {
						$csv[] = explode(",", trim($value));
					}
				}
			}
			
			if( count($csv) > 0 ){
				foreach ($csv as $key => $value) {
					$categs[$value[0]] = explode(":", trim($value[1]));
				}
			}
			
			return $categs;  
		}
		
		public function getAmazonSortValues()
		{
			$country = $this->convertMainAffIdInCountry( $this->amz_settings['main_aff_id'] );
			$csv = $categs = array();
			
			
			// try to read the plugin_root/assets/searchindexParam-{country}.csv file
			// check if file exists
			if( !is_file( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'assets/sortvalues-' . ( $country ) . '.csv' ) ){
				die( 'Unable to load file: ' . $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'assets/sortvalues-' . ( $country ) . '.csv' );
			}
			
        	$csv_file_content = file_get_contents( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'assets/sortvalues-' . ( $country ) . '.csv' );
 			if( trim($csv_file_content) != "" ){
				$rows = explode("\r", $csv_file_content);
				 
				if( count($rows) > 0 ){
					foreach ($rows as $key => $value) {
						$csv[] = explode(",", trim($value));
					}
				}
			}
			
			if( count($csv) > 0 ){
				foreach ($csv as $key => $value) {
					$categs[$value[0]] = explode(":", trim($value[1]));
				}
			}
			  
			return $categs;  
		}
		
		public function browseNodeLookup( $nodeid )
		{
			$provider = 'amazon';
			//$rsp = $this->the_plugin->get_ws_object( $provider )->api_make_request(array(
			$rsp = $this->api_make_request(array(
				'amz_settings'			=> $this->the_plugin->amz_settings,
				'from_file'				=> str_replace($this->the_plugin->cfg['paths']['plugin_dir_path'], '', __FILE__),
				'from_func'				=> __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
				'requestData'			=> array(
					'nodeid'					=> $nodeid,
				),
				//'optionalParameters'	=> array(),
				'responseGroup'			=> 'BrowseNodeInfo',
				'method'				=> 'browseNodeLookup',
			));
			$ret = $rsp['response'];
			//$ret = $this->aaAmazonWS->responseGroup('BrowseNodeInfo')->browseNodeLookup( $nodeid );
            
            return $ret;
		}
		
		public function updateProductReviews( $post_id=0 )
		{
			$reviewsURL = '';

			// get product ASIN by post_id 
			$asin = get_post_meta( $post_id, '_amzASIN', true );
			
			$provider = 'amazon';
			//$rsp = $this->the_plugin->get_ws_object( $provider )->api_make_request(array(
			$rsp = $this->api_make_request(array(
				'amz_settings'			=> $this->the_plugin->amz_settings,
				'from_file'				=> str_replace($this->the_plugin->cfg['paths']['plugin_dir_path'], '', __FILE__),
				'from_func'				=> __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
				'requestData'			=> array(
					'asin'					=> $asin,
				),
				'optionalParameters'	=> array(),
				'responseGroup'			=> 'Reviews',
				'method'				=> 'lookup',
			));
			$product = $rsp['response'];
			//$product = $this->aaAmazonWS->responseGroup('Reviews')->optionalParameters(array('MerchantId' => 'All'))->lookup( $asin );
            
			$respStatus = $this->is_amazon_valid_response( $product );
			if ( $respStatus['status'] != 'valid' ) { // error occured!
				$msg = 'ASIN [' . $asin . '] - ' . 'Amazon Error: ' . $respStatus['code'] . ' - ' . $respStatus['msg'];
				return $reviewsURL;
			}

			//if($product['Items']["Request"]["IsValid"] == "True"){
			if (1) {
				$thisProd = isset($product['Items']['Item']) ? $product['Items']['Item'] : array();
				if (isset($product['Items']['Item']) && count($product['Items']['Item']) > 0){
					$reviewsURL = $thisProd['CustomerReviews']['IFrameURL'];
					if( trim($reviewsURL) != "" ){
						
						$tab_data = array();
						$tab_data[] = array(
							'id' => 'amzAff-customer-review',
							'content' => '<iframe src="' . ( $reviewsURL ) . '" width="100%" height="450" frameborder="0"></iframe>'
						); 
						
						update_post_meta( $post_id, 'amzaff_woo_product_tabs', $tab_data );
					}
				}
			}

			return $reviewsURL;
		}
		
        /**
         * Get Product From Amazon
         */
		public function getProductDataFromAmazon( $retType='die', $pms=array() ) {
			// require_once( $this->the_plugin->cfg['paths']["scripts_dir_path"] . '/shutdown-scheduler/shutdown-scheduler.php' );
			// $scheduler = new aateamShutdownScheduler();

            $this->the_plugin->timer_start(); // Start Timer

            $cross_selling = (isset($this->amz_settings["cross_selling"]) && $this->amz_settings["cross_selling"] == 'yes' ? true : false);

            $_msg = array();
			$ret = array(
                'status'									=> 'invalid',
                'msg'									=> '',
                'product_data'						=> array(),
                'show_download_lightbox'		=> false,
                'download_lightbox_html'		=> '',
                'product_id'							=> 0,
                'do_import'							=> true,
            );
			
			if ( !$this->the_plugin->can_import_products() ) {
                $ret = array_merge($ret, array(
                	'do_import'	=> false,
                    'msg'			=> self::MSG_SEP . ' <u>You can no longer import products using our demo keys.</u>!',
                ));
                if ( $retType == 'return' ) { return $ret; }
                else { die( json_encode( $ret ) ); }
			}
            
            //$asin = isset($_REQUEST['asin']) ? htmlentities($_REQUEST['asin']) : '';
            //$category = isset($_REQUEST['category']) ? htmlentities($_REQUEST['category']) : 'All';
            
            // build method parameters
            $requestData = array(
            	'ws'					=> self::$provider,
                'asin'                  => isset($_REQUEST['asin']) ? htmlentities($_REQUEST['asin']) : '',
                'do_import_product'     => 'yes',
                'from_cache'            => array(),
                'debug_level'           => isset($_REQUEST['debug_level']) ? (int) $_REQUEST['debug_level'] : 0,

                'from_module'           => 'default',
                'import_type'           => isset($this->amz_settings['import_type'])
                    && $this->amz_settings['import_type'] == 'asynchronous' ? 'asynchronous' : 'default',

                // bellow parameters are used in framework addNewProduct method
                'operation_id'          => '',

                'import_to_category'    => isset($_REQUEST['to-category']) ? trim($_REQUEST['to-category']) : 0,

                'import_images'         => isset($this->amz_settings["number_of_images"])
                    && (int) $this->amz_settings["number_of_images"] > 0
                    ? (int) $this->amz_settings["number_of_images"] : 'all',

                'import_variations'     => isset($this->amz_settings['product_variation'])
                    ? $this->amz_settings['product_variation'] : 'yes_5',

                'spin_at_import'        => isset($this->amz_settings['spin_at_import'])
                    && ($this->amz_settings['spin_at_import'] == 'yes') ? true : false,
                    
                'import_attributes'     => isset($this->amz_settings['item_attribute'])
                    && ($this->amz_settings['item_attribute'] == 'no') ? false : true,
            );

            foreach ($requestData as $rk => $rv) {
                //empty($rv) || ( isset($pms["$rk"]) && !empty($pms["$rk"]) )
                if ( 1 ) {
                    if ( isset($pms["$rk"]) ) {
                        $new_val = $pms["$rk"];
                        $requestData["$rk"] = $new_val;
                    }
                }
            }
            $requestData['asin'] = trim( $requestData['asin'] );
            
            // Import To Category
            if ( empty($requestData['import_to_category']) || ( (int) $requestData['import_to_category'] <= 0 ) ) {
                $requestData['import_to_category'] = 'amz';
            }
 
            // NOT using category from amazon!
            if ( (int) $requestData['import_to_category'] > 0 ) {
                $__categ = get_term( $requestData['import_to_category'], 'product_cat' );
                if ( isset($__categ->term_id) && !empty($__categ->term_id) ) {
                    $requestData['import_to_category'] = $__categ->term_id;
                } else {
                    $requestData['import_to_category'] = 'amz';
                }
                //$requestData['import_to_category'] = $__categ->name ? $__categ->name : 'Untitled';

                //$__categ2 = get_term_by('name', $requestData['import_to_category'], 'product_cat');
                //$requestData['import_to_category'] = $__categ2->term_id;
            }

            extract($requestData);

            // provided ASIN in invalid
			if( empty($asin) ){
                $ret = array_merge($ret, array(
                    'msg'           => self::MSG_SEP . ' <u>Import Product ASIN</u> : is invalid (empty)!',
                ));
                if ( $retType == 'return' ) { return $ret; }
                else { die( json_encode( $ret ) ); }
			}
			
            // check if product already imported 
            $your_products = $this->the_plugin->getAllProductsMeta('array', '_amzASIN');
            if( 0 ){
            //if( isset($your_products) && count($your_products) > 0 ){
                if( in_array($asin, $your_products) ){
                    
                    $ret = array_merge($ret, array(
                        'msg'           => self::MSG_SEP . ' <u>Import Product ASIN</u> <strong>'.$asin.'</strong> : already imported!',
                        'product_id'	=> -1,
                    ));
                    if ( $retType == 'return' ) { return $ret; }
                    else { die( json_encode( $ret ) ); }
                }
            }

            $isValidProduct = false;
            $_msg[] = self::MSG_SEP . ' <u>Import Product ASIN</u> <strong>'.$asin.'</strong>';

            // from cache
            if ( isset($from_cache) && $this->is_valid_product_data($from_cache) ) {
                $retProd = $from_cache;
                $isValidProduct = true;
                
                $_msg[] = self::MSG_SEP . ' product data returned from Cache';

                if ( 1 ) {
                    $this->the_plugin->add_last_imports('request_cache', array(
                        'duration'      => $this->the_plugin->timer_end(),
                    )); // End Timer & Add Report
                }
            }
 
            // from amazon
            if ( !$isValidProduct ) {
                try {

					$provider = 'amazon';
					//$rsp = $this->the_plugin->get_ws_object( $provider )->api_make_request(array(
					$rsp = $this->api_make_request(array(
						'amz_settings'			=> $this->the_plugin->amz_settings,
						'from_file'				=> str_replace($this->the_plugin->cfg['paths']['plugin_dir_path'], '', __FILE__),
						'from_func'				=> __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
						'requestData'			=> array(
							'asin'					=> $asin,
						),
						'optionalParameters'	=> array(),
						'responseGroup'			=> 'Large,ItemAttributes,OfferFull,Variations,Reviews,PromotionSummary,SalesRank',
						'method'				=> 'lookup',
					));
					$product = $rsp['response'];

        			// create request by ASIN
        			//$product = $this->aaAmazonWS->responseGroup('Large,ItemAttributes,OfferFull,Variations,Reviews,PromotionSummary,SalesRank')->optionalParameters(array('MerchantId' => 'All'))->lookup($asin);
                  	 
                    $respStatus = $this->is_amazon_valid_response( $product );
                    if ( $respStatus['status'] != 'valid' ) { // error occured!
          			    
          			    $_msg[] = 'Invalid '.self::$provider.' response ( ' . $respStatus['code'] . ' - ' . $respStatus['msg'] . ' )';
                        
                        $ret = array_merge($ret, array('msg' => implode('<br />', $_msg)));
                        if ( $retType == 'return' ) { return $ret; }
                        else { die( json_encode( $ret ) ); }
                
                    } else { // success!
        
        				$thisProd = $product['Items']['Item'];
        				if ( 1 ) {
    
                            // build product data array
                            $retProd = array(); 
                            $retProd = $this->build_product_data( $thisProd );
                            if ( $this->is_valid_product_data($retProd) ) {
                                $isValidProduct = true;
                                $_msg[] = 'Valid '.self::$provider.' response';
                            }
        
        					// DEBUG
        					if( $debug_level > 0 ) {
        					    ob_start();
        
        						if( $debug_level == 1) var_dump('<pre>', $retProd,'</pre>');
        						if( $debug_level == 2) var_dump('<pre>', $product ,'</pre>');
        
                                $ret = array_merge($ret, array('msg' => ob_get_clean()));
                                if ( $retType == 'return' ) { return $ret; }
                                else { die( json_encode( $ret ) ); }
        					}
        				}
        			}
    
                } catch (Exception $e) {
                    // Check 
                    if (isset($e->faultcode)) { // error occured!
    
                        //ob_start();
                        //var_dump('<pre>', 'Invalid '.self::$provider.' response (exception)', $e,'</pre>');
    
                        //$_msg[] = ob_get_clean();
						$_msg[] = 'Invalid '.self::$provider.' resp : ' . $e->faultcode .  ' : ' . (isset($e->faultstring) ? $e->faultstring : $e->getMessage());
                        
                        $ret = array_merge($ret, array('msg' => implode('<br />', $_msg)));
                        if ( $retType == 'return' ) { return $ret; }
                        else { die( json_encode( $ret ) ); }
                    }
                } // end try
            } // end from amazon
            
            // If valid product data retrieved -> Try to Import Product in Database
            if ( $isValidProduct ) {

                if ( 1 ) {
                    $this->the_plugin->add_last_imports('request_amazon', array(
                        'duration'      => $this->the_plugin->timer_end(),
                    )); // End Timer & Add Report
                }

                // do not import product - just return the product data array
                if( !isset($do_import_product) || $do_import_product != 'yes' ){
                    $ret = array_merge($ret, array(
                        'status'        => 'valid',
                        'product_data'  => $retProd,
                        'msg'           => implode('<br />', $_msg))
                    );
                    if ( $retType == 'return' ) { return $ret; }
                    else { die( json_encode( $ret ) ); }
                }
        
                // add product in database
                $args_add = $requestData;
                $insert_id = $this->the_plugin->addNewProduct( $retProd, $args_add );
                $insert_id = (int) $insert_id;
                $opStatusMsg = $this->the_plugin->opStatusMsgGet();

                // Successfully adding product in database
                if ( $insert_id > 0 ) {

                    $_msg[] = self::MSG_SEP . ' Successfully Adding product in database (with ID: <strong>'.$insert_id.'</strong>).';
                    $ret['status'] = 'valid';
					$ret['product_id'] = $insert_id;

                    if ( !empty($import_type) && $import_type=='default' ) {
                    	if ( !$this->the_plugin->is_remote_images ) {
	                        $ret = array_merge($ret, array(
	                            'show_download_lightbox'     => true,
	                            'download_lightbox_html'     => $this->the_plugin->download_asset_lightbox( $insert_id, $from_module, 'html' ),
							));
						}
                    }
                }
                // Error when trying to insert product in database
                else {
                    $_msg[] = self::MSG_SEP . ' Error Adding product in database.';
                }
                
                // detailed status from adding operation: successfull or with errors
                $_msg[] = $opStatusMsg['msg'];
                
                $ret = array_merge($ret, array('msg' => implode('<br />', $_msg)));
                if ( $retType == 'return' ) { return $ret; }
                else { die( json_encode( $ret ) ); }

            } else {

                $_msg[] = self::MSG_SEP . ' product data (from cache or '.self::$provider.') is not valid!';

                $ret = array_merge($ret, array('msg' => implode('<br />', $_msg)));
                if ( $retType == 'return' ) { return $ret; }
                else { die( json_encode( $ret ) ); }
            }

			// $scheduler->registerShutdownEvent(array($scheduler, 'getLastError'), true);
        }

        // verify if amazon response is valid!
        public function is_amazon_valid_response( $response, $operation='' ) {
            $ret = array(
                'status'			=> 'invalid',
                'msg'			=> 'unknown message.',
                'html'			=> 'unknown message.',
                'code'			=> -1,
            );

			$ul = 'Items'; $li = 'Item';
			switch ($operation) {
				case 'browseNodeLookup':
					$ul = 'BrowseNodes'; $li = 'BrowseNode';
					break;
					
				case 'cartThem':
					$ul = 'Cart'; //$ul = 'CartItems'; $li = 'CartItem';
					break;
			}
			
            // response probably set from a try-catch block
            if ( isset($response['status']) && ( 'invalid' == $response['status'] ) ) {

                $msg = isset($response['msg']) ? $response['msg'] : 'error not catched!';
                return array_merge($ret, array(
                    'msg'       => $msg,
                    'html'      => $msg,
                    'code'      => 1,
                ));
            }

            // parse amazon response
            if ( !isset($response["$ul"]['Request']['IsValid']) ) {
  
                $msg = 'invalid '.self::$provider.' response: probably request to amazon api was dropped!.';
                if ( isset($response['Error']['Code']) ) {
                    $msg = self::$provider.' error id: <bold>' . ( $response['Error']['Code'] ) . '</bold>: ' . ( $response['Error']['Message'] );

                }
                return array_merge($ret, array(
                    'msg'       => $msg,
                    'html'      => $msg,
                    'code'      => 1,
                ));
            }

            if ( $response["$ul"]['Request']['IsValid'] == 'False' ) {
        
                if ( isset($response["$ul"]['Request']['Errors']['Error']['Code']) ) {
                    $msg = self::$provider.' error id: <bold>' . ( $response["$ul"]['Request']['Errors']['Error']['Code'] ) . '</bold>: ' . ( $response["$ul"]['Request']['Errors']['Error']['Message'] );

                } else if ( is_array($response["$ul"]['Request']['Errors']['Error']) ) {
                    $_msg = array();
                    $_msg[] = self::$provider.' error id:';
                    foreach ($response["$ul"]['Request']['Errors']['Error'] as $err_key => $err_val) {
                        $_msg[] = '<bold>' . ( $err_val['Code'] ) . '</bold>: ' . ( $err_val['Message'] );
                    }
                    $msg = implode('<br />', $_msg);
                    
                } else {
                    $msg = 'unknown '.self::$provider.' error.';
                }
                return array_merge($ret, array(
                    'msg'       => $msg,
                    'html'      => $msg,
                    'code'      => 2, 
                ));
            }

            // No products found!
            //isset($response['Items']['Item']) && count($response['Items']['Item']) > 0

			$rules = array();
			if ( 'cartThem' == $operation ) {
				$rules[0] = !isset($response["$ul"]['CartItems']) 
					|| ( count($response["$ul"]['CartItems']) <= 0 )
	                || !isset($response["$ul"]['CartItems']["CartItem"])
	                || ( count($response["$ul"]['CartItems']["CartItem"]) <= 0 );
			}
			else {
				$rules[0] = ( count($response["$ul"]) <= 0 )
	                || !isset($response["$ul"]["$li"])
	                || ( count($response["$ul"]["$li"]) <= 0 );
			}

			if ( $rules[0] ) {

                $amz_code = '';
                if ( isset($response["$ul"]['Request']['Errors']['Error']['Code']) ) {
                    $amz_code = $response["$ul"]['Request']['Errors']['Error']['Code'];

                    $msg = self::$provider.' error id: <bold>' . ( $response["$ul"]['Request']['Errors']['Error']['Code'] ) . '</bold>: ' . ( $response["$ul"]['Request']['Errors']['Error']['Message'] );
                    switch ($response["$ul"]['Request']['Errors']['Error']['Code']) {
                        case 'AWS.ECommerceService.NoExactMatches':
                            $msg = 'Sorry, your search did not return any results.';
                            break;
                            
                        case 'AWS.ECommerceService.NoSimilarities':
                            $msg = 'Sorry, there are no similar items for this product.';
                            break;

                        case 'AWS.InvalidParameterValue':
                            break;
                    }

                } else if ( isset($response["$ul"]['Request']['Errors']['Error']) && is_array($response["$ul"]['Request']['Errors']['Error']) ) {
                    $_msg = array();
                    $_msg[] = self::$provider.' error id:';
                    foreach ($response["$ul"]['Request']['Errors']['Error'] as $err_key => $err_val) {
                        $_msg[] = '<bold>' . ( $err_val['Code'] ) . '</bold>: ' . ( $err_val['Message'] );
                    }
                    $msg = implode('<br />', $_msg);
                    
                } else {
                    $msg = 'no products found.';
                }
                return array_merge($ret, array(
                    'msg'       => $msg,
                    'html'      => $msg,
                    'code'      => 3,
                    'amz_code'  => $amz_code,
                ));
            }

            // success   
            return array_merge($ret, array(
                'status'        => 'valid',
                'msg'           => 'valid message.',
                'html'          => 'valid message.',
                'code'          => 0,
            ));
        }

        // product data is valid
        public function is_valid_product_data( $product=array(), $from='' ) {
            if ( empty($product) || !is_array($product) ) return false;
            
            $rules = isset($product['ASIN']) && !empty($product['ASIN']);
            $rules = $rules && 1;
            return $rules ? true : false;
        }

        // build single product data based on amazon request array
        public function build_product_data( $item=array(), $old_item=array() ) {

            // summarize product details
            $retProd = array(
                'ASIN'                  => isset($item['ASIN']) ? $item['ASIN'] : '',
                'ParentASIN'            => isset($item['ParentASIN']) ? $item['ParentASIN'] : '',
                
                'ItemAttributes'        => isset($item['ItemAttributes']) ? $item['ItemAttributes'] : '',
                'Title'                 => isset($item['ItemAttributes']['Title']) ? stripslashes($item['ItemAttributes']['Title']) : '',
                'SKU'                   => isset($item['ItemAttributes']['SKU']) ? $item['ItemAttributes']['SKU'] : '',
                'Feature'               => isset($item['ItemAttributes']['Feature']) ? $item['ItemAttributes']['Feature'] : '',
                'Brand'                 => isset($item['ItemAttributes']['Brand']) ? $item['ItemAttributes']['Brand'] : '',
                'Binding'               => isset($item['ItemAttributes']['Binding']) ? $item['ItemAttributes']['Binding'] : '',
                //'ListPrice'           => isset($item['ItemAttributes']['ListPrice']['FormattedPrice']) ? $item['ItemAttributes']['ListPrice']['FormattedPrice'] : '',
                
                'Variations'            => isset($item['Variations']) ? $item['Variations'] : array(),
                'VariationSummary'      => isset($item['VariationSummary']) ? $item['VariationSummary'] : array(),
                'BrowseNodes'           => isset($item['BrowseNodes']) ? $item['BrowseNodes'] : array(),
                'DetailPageURL'         => isset($item['DetailPageURL']) ? $item['DetailPageURL'] : '',
                'SalesRank'             => isset($item['SalesRank']) ? $item['SalesRank'] : 999999,

                'SmallImage'            => isset($item['SmallImage']['URL']) ? trim( $item['SmallImage']['URL'] ) : '',
                'LargeImage'            => isset($item['LargeImage']['URL']) ? trim( $item['LargeImage']['URL'] ) : '',

                'Offers'                => isset($item['Offers']) ? $item['Offers'] : '',
                'OfferSummary'          => isset($item['OfferSummary']) ? $item['OfferSummary'] : '',
                'EditorialReviews'      => isset($item['EditorialReviews']['EditorialReview']['Content'])
                    ? $item['EditorialReviews']['EditorialReview']['Content'] : '',
                    
				'hasGallery'			=> 'false',
            );
			
			// added by jimmy /2017-02-16
			$retProd['country'] = isset($this->amz_settings['country']) ? $this->amz_settings['country'] : '';
			if ( ! empty($retProd['DetailPageURL']) ) {
				$country = $this->the_plugin->get_country_from_url( $retProd['DetailPageURL'] );
				if ( !empty($country) ) {
					$retProd['country'] = $country;
				}
			}
			//var_dump('<pre>', $retProd['DetailPageURL'], $retProd['country'], '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;    
			
			// try to rebuid the description if is empty
			if( trim($retProd["EditorialReviews"]) == "" ){
				if( isset($item['EditorialReviews']['EditorialReview']) && count($item['EditorialReviews']['EditorialReview']) > 0 ){
					
					$new_description = array();
					foreach ($item['EditorialReviews']['EditorialReview'] as $desc) {
						if( isset($desc['Content']) && isset($desc['Source']) ){
							//$new_description[] = '<h3>' . ( $desc['Source'] ) . ':</h3>';
							$new_description[] = $desc['Content'] . '<br />';
						}
					}
				}
				
				if( isset($new_description) && count($new_description) > 0 ){
					$retProd["EditorialReviews"] = implode( "\n", $new_description );
				}
			}
			
            // CustomerReviews url
            if ( isset($item['CustomerReviews'], $item['CustomerReviews']['HasReviews'])
                && $item['CustomerReviews']['HasReviews'] ) {
                $retProd['CustomerReviewsURL'] = $item['CustomerReviews']['IFrameURL'];
            }

            // Images
            $retProd['images'] = $this->build_images_data( $item );
            if ( empty($retProd['images']['large']) ) {
                // no images found - if has variations, try to find first image from variations
                $retProd['images'] = $this->get_first_variation_image( $item );
            }
            
            if ( empty($retProd['SmallImage']) ) {
                if ( isset($retProd['images']['small']) && !empty($retProd['images']['small']) ) {
                    $retProd['SmallImage'] = $retProd['images']['small'][0];
                }
            }
            if ( empty($retProd['LargeImage']) ) {
                if ( isset($retProd['images']['large']) && !empty($retProd['images']['large']) ) {
                    $retProd['LargeImage'] = $retProd['images']['large'][0];
                }
            }

			// has gallery: get gallery images
			//if ( isset($item['ImageSets']) && count($item['ImageSets']) > 0 ) {
			//	foreach ( $item['ImageSets']["ImageSet"] as $key => $value ) {
			//		if ( isset($value['LargeImage']['URL']) ) {
			//			$retProd['hasGallery'] = 'true';
			//			break;
			//		}
			//	}
			//}
			// update 2016-july
			if ( !empty($retProd['images']['large']) && (count($retProd['images']['large']) > 1) ) {
				$retProd['hasGallery'] = 'true';
			}
            return $retProd;
        }

        public function build_images_data( $item=array(), $nb_images='all' ) {
            $retProd = array( 'large' => array(), 'small' => array(), 'sizes' => array() );

            //if ( isset($item['LargeImage']['URL']) ) {
            //   $retProd['large'][] = $item['LargeImage']['URL'];
            //}
            //if ( isset($item['SmallImage']['URL']) ) {
            //   $retProd['small'][] = $item['SmallImage']['URL'];
            //}
			$retProd = $this->build_current_image($item, $retProd);

            // get gallery images
            if (isset($item['ImageSets'], $item['ImageSets']['ImageSet']) && count($item['ImageSets']["ImageSet"]) > 0) {
                
                // hack if have only 1 item
                if( isset($item['ImageSets']['ImageSet']['SwatchImage']) ){
                    $_tmp = $item['ImageSets']["ImageSet"];
                    $item['ImageSets']["ImageSet"] = array();
                    $item['ImageSets']["ImageSet"][0] = $_tmp;  
                }

                $count = 0;
                foreach ($item['ImageSets']["ImageSet"] as $key => $value) {
                    
                    //if( isset($value['LargeImage']['URL']) ){
                    //    $retProd['large'][] = $value['LargeImage']['URL'];
                    //}
                    //if( isset($value['SmallImage']['URL']) ){
                    //    $retProd['small'][] = $value['SmallImage']['URL'];
                    //}
					$retProd = $this->build_current_image($value, $retProd);
                    $count++;
                }
            }

			// clean images
			//foreach ($retProd as $key => $val) {
			//	if ( in_array($key, array('large')) ) {
			//		// keep unique images
			//		$retProd["$key"] = @array_unique($retProd["$key"]);
			//		// remove empty array elements!
			//		$retProd["$key"] = @array_filter($retProd["$key"]);
			//	}
			//}
 
            return $retProd;
        }
		
        // if product is variation parent, get first variation child image as product image
        public function get_first_variation_image( $retProd ) {

            $images = array( 'large' => array(), 'small' => array(), 'sizes' => array() );

            if ( isset($retProd['Variations'], $retProd['Variations']['TotalVariations'], $retProd['Variations']['Item']) ) {
                $total = (int)$retProd['Variations']['TotalVariations'];
                
                $variations = array();
                if ($total <= 1 || isset($retProd['Variations']['Item']['ASIN'])) { // --fix 2015.03.19
                    $variations[] = $retProd['Variations']['Item'];
                } else {
                    $variations = (array) $retProd['Variations']['Item'];
                }
 
                // Loop through the variation
                foreach ($variations as $variation_item) {
                    
                    $images = $this->build_images_data( $variation_item );
                    if ( !empty($images['large']) ) {
                        return $images;
                    }
                } // end foreach
            }
            return $images;
        }

		private function _build_current_image( $item=array() ) {
			$current = array( 'large' => '', 'small' => '', 'sizes' => array() );

			$key2key = array('SmallImage' => 'small', 'LargeImage' => 'large');
			
			if ( ! is_array($item) || empty($item) ) {
				$item = array();
			}

			foreach ($item as $sizek => $sizev) {
				$sizek 	= (string) $sizek;

				if ( preg_match('/image$/iu', $sizek) ) {
					// large & small
					if ( in_array($sizek, array_keys($key2key)) && isset($sizev['URL']) ) {
						$__ = $key2key["$sizek"];
						$current["$__"] = $sizev['URL'];
					}

					// all sizes
					if ( isset($sizev['URL']) ) {
						$__ = strtolower( str_ireplace('image', '', $sizek) );
						if ( isset($current['sizes']["$__"]) ) continue 1;
						
						$url = isset($sizev['URL']) ? $sizev['URL'] : '';
						
						$width = 0;
						if ( isset($sizev['Width']) ) {
							$width = is_numeric($sizev['Width']) ? (int) $sizev['Width'] : ( isset($sizev['Width']['_']) ? (int) $sizev['Width']['_'] : 0 );
						}
						
						$height = 0;
						if ( isset($sizev['Height']) ) {
							$height = is_numeric($sizev['Height']) ? (int) $sizev['Height'] : ( isset($sizev['Height']['_']) ? (int) $sizev['Height']['_'] : 0 );
						}

						$current['sizes']["$__"] = array(
							'url'		=> $url,
							'width'		=> $width,
							'height'	=> $height,
						);
					}
				}
			}

			if ( !empty($current['large']) && empty($current['small']) ) {
				$current['small'] = $current['large'];
			}
			return $current;
		}

		private function build_current_image( $item=array(), $retProd=array() ) {
			$current = $this->_build_current_image( $item );
			if ( !isset($current['large']) || empty($current['large']) ) return $retProd;
			if ( in_array($current['large'], $retProd['large']) ) return $retProd;

			$index = count($retProd['large']);

			$retProd['large'][$index] = $current['large'];
			$retProd['small'][$index] = $current['small'];
			$retProd['sizes'][$index] = $current['sizes'];

			return $retProd;
		}

		/**
	     * Create the tags for the product
	     * @param array $Tags
	     */
		public function set_product_tags( $Tags='' )
	    {
	    	return array();
		}

		/**
	     * Create the categories for the product & the attributes
	     * @param array $browseNodes
	     */
	    public function set_product_categories( $browseNodes=array() )
	    {
	        // The woocommerce product taxonomy
	        $wooTaxonomy = "product_cat";
 
	        // Categories for the product
	        $createdCategories = array();
	        
	        // Category container
	        $categories = array();
	        
	        // Count the top browsenodes
	        $topBrowseNodeCounter = 0;
			
			if ( !isset($browseNodes['BrowseNode']) ) {
	        	// Delete the product_cat_children
	        	// This is to force the creation of a fresh product_cat_children
	        	//delete_option( 'product_cat_children' );
			
				return array();
			}

	        // Check if we have multiple top browseNode
	        if( is_array( $browseNodes['BrowseNode'] ) )
	        {
	        	// check if is has only one key
	        	if( isset($browseNodes["BrowseNode"]["BrowseNodeId"]) && trim($browseNodes["BrowseNode"]["BrowseNodeId"]) != "" ){
	        		$_browseNodes = $browseNodes["BrowseNode"];
	        		$browseNodes = array();
					$browseNodes['BrowseNode'][0] = $_browseNodes;
					unset($_browseNodes);
	        	}
    
	            foreach( $browseNodes['BrowseNode'] as $browseNode )
	            {
	                // Create a clone
	                $currentNode = $browseNode;
	
	                // Track the child layer
	                $childLayer = 0;
	
	                // Inifinite loop, since we don't know how many ancestral levels
	                while( true )
	                {
	                    $validCat = true;
	                    
	                    // Replace html entities
	                    $dmCatName = str_replace( '&', 'and', $currentNode['Name'] );
	                    $dmCatSlug = sanitize_title( str_replace( '&', 'and', $currentNode['Name'] ) );
						
						$dmCatSlug_id = '';
						if ( is_object($currentNode) && isset($currentNode->BrowseNodeId) )
	                    	$dmCatSlug_id = ($currentNode->BrowseNodeId);
						else if ( is_array($currentNode) && isset($currentNode['BrowseNodeId']) )
							$dmCatSlug_id = ($currentNode['BrowseNodeId']);

						// $dmCatSlug = ( !empty($dmCatSlug_id) ? $dmCatSlug_id . '-' . $dmCatSlug : $dmCatSlug );

	                    $dmTempCatSlug = sanitize_title( str_replace( '&', 'and', $currentNode['Name'] ) );
	                    
	                    if( $dmTempCatSlug == 'departments' ) $validCat = false;
	                    if( $dmTempCatSlug == 'featured-categories' ) $validCat = false;
	                   	if( $dmTempCatSlug == 'categories' ) $validCat = false;
						if( $dmTempCatSlug == 'products' ) $validCat = false;
	                    if( $dmTempCatSlug == 'all-products') $validCat = false;
	
	                    // Check if we will make the cat
	                    if( $validCat ) {
	                        $categories[0][] = array(
	                            'name' => $dmCatName,
	                            'slug' => $dmCatSlug
	                        );
	                    }
	
	                    // Check if the current node has a parent
	                    if( isset($currentNode['Ancestors']['BrowseNode']['Name']) )
	                    {
	                        // Set the next Ancestor as the current node
	                        $currentNode = $currentNode['Ancestors']['BrowseNode'];
	                        $childLayer++;
	                        continue;
	                    }
	                    else
	                    {
	                        // There's no more ancestors beyond this
	                        break;
	                    }
	                } // end infinite while
	                
	                // Increment the tracker
	                $topBrowseNodeCounter++;
	            } // end foreach
	        }
	        else
	        {
	            // Handle single branch browsenode
	            
	            // Create a clone
	            $currentNode = isset($browseNodes['BrowseNode']) ? $browseNodes['BrowseNode'] : array();
	            
	            // Inifinite loop, since we don't know how many ancestral levels
	            while (true) 
	            {
	                // Always true unless proven
	                $validCat = true;
	                
	                // Replace html entities
	                $dmCatName = str_replace( '&', 'and', $currentNode['Name'] );
	                $dmCatSlug = sanitize_title( str_replace( '&', 'and', $currentNode['Name'] ) );
					$dmCatSlug_id = $currentNode['BrowseNodeId'];
	                // $dmCatSlug = ( !empty($dmCatSlug_id) ? $dmCatSlug_id . '-' . $dmCatSlug : $dmCatSlug );  
	                
	                $dmTempCatSlug = sanitize_title( str_replace( '&', 'and', $currentNode['Name'] ) );
	                
					if( $dmTempCatSlug == 'departments' ) $validCat = false;
                    if( $dmTempCatSlug == 'featured-categories' ) $validCat = false;
                   	if( $dmTempCatSlug == 'categories' ) $validCat = false;
					if( $dmTempCatSlug == 'products' ) $validCat = false;
                    if( $dmTempCatSlug == 'all-products') $validCat = false;
	                
	                // Check if we will make the cat
	                if( $validCat ) {
	                    $categories[0][] = array(
	                        'name' => $dmCatName,
	                        'slug' => $dmCatSlug
	                    );
	                }
	
	                // Check if the current node has a parent
	                if (isset($currentNode['Ancestors']['BrowseNode']['Name'])) 
	                {
	                    // Set the next Ancestor as the current node
	                    $currentNode = $currentNode['Ancestors']['BrowseNode'];
	                    continue;
	                } 
	                else 
	                {
	                    // There's no more ancestors beyond this
	                    break;
	                }
	            } // end infinite while
	                
	        } // end if browsenode is an array
	        
	        // Tracker
	        $catCounter = 0;
	        
	        // Make the parent at the top
	        foreach( $categories as $category )
	        {
	            $categories[$catCounter] = array_reverse( $category );
	            $catCounter++;
	        }
	        
	        // Current top browsenode
	        $categoryCounter = 0;
	        
	        // Import only parent category from Amazon
			if( isset( $this->amz_settings["create_only_parent_category"] ) && $this->amz_settings["create_only_parent_category"] != '' && $this->amz_settings["create_only_parent_category"] == 'yes') {
				$categories = array( array( $categories[0][0] ) );
			}
			
			// Loop through each of the top browsenode
	        foreach( $categories as $category )
	        {
	            // The current node
	            $nodeCounter = 0;
	            // Loop through the array of the current browsenode
	            foreach( $category as $node )
	            {
	                // Check if we're at parent
	                if( $nodeCounter === 0 )
	                {                
	                    // Check if term exists
	                    $checkTerm = term_exists( str_replace( '&', 'and', $node['slug'] ), $wooTaxonomy );
	                    if( empty( $checkTerm ) )
	                    {
	                        // Create the new category
	                       $newCat = wp_insert_term( $node['name'], $wooTaxonomy, array( 'slug' => $node['slug'] ) );
	                       
	                       // Add the created category in the createdCategories
	                       // Only run when the $newCat is an error
	                       if( gettype($newCat) != 'object' ) {
	                       		$createdCategories[] = $newCat['term_id'];
	                       }       
	                    }
	                    else
	                    {
	                        // if term already exists add it on the createdCats
	                        $createdCategories[] = $checkTerm['term_id'];
	                    }
	                }
	                else
	                {  
	                    // The parent of the current node
	                    $parentNode = $categories[$categoryCounter][$nodeCounter - 1];
	                    // Get the term id of the parent
	                    $parent = term_exists( str_replace( '&', 'and', $parentNode['slug'] ), $wooTaxonomy );
	                    
	                    // Check if the category exists on the parent
	                    $checkTerm = term_exists( str_replace( '&', 'and', $node['slug'] ), $wooTaxonomy );
	                    
	                    if( empty( $checkTerm ) )
	                    {
	                        $newCat = wp_insert_term( $node['name'], $wooTaxonomy, array( 'slug' => $node['slug'], 'parent' => $parent['term_id'] ) );
	                        
	                        // Add the created category in the createdCategories
	                        $createdCategories[] = $newCat['term_id'];
	                    }
	                    else
	                    {
	                        $createdCategories[] = $checkTerm['term_id'];
	                    }
	                }
	                
	                $nodeCounter++;
	            } 
	    
	            $categoryCounter++;
	        } // End top browsenode foreach
	        
	        // Delete the product_cat_children
	        // This is to force the creation of a fresh product_cat_children
	        delete_option( 'product_cat_children' );
	        
	        $returnCat = array_unique($createdCategories);
	     
	        // return an array of term id where the post will be assigned to
	        return $returnCat;
	    }

		public function set_woocommerce_attributes( $itemAttributes=array(), $post_id ) 
		{
	        global $wpdb;
	        global $woocommerce;
	 
	        // convert Amazon attributes into woocommerce attributes
	        $_product_attributes = array();
	        $position = 0;
			
			$allowedAttributes = 'all';

			if ( isset($this->amz_settings['selected_attributes'])
				&& !empty($this->amz_settings['selected_attributes'])
				&& is_array($this->amz_settings['selected_attributes']) )
				$allowedAttributes = (array) $this->amz_settings['selected_attributes'];
				
	        foreach( $itemAttributes as $key => $value )
	        { 
	            if (!is_object($value)) 
	            {
	            	if ( is_array($allowedAttributes) ) {
						if ( !in_array($key, $allowedAttributes) ) {
							continue 1;
						}
					}
					
	                // Apparel size hack
	                if($key === 'ClothingSize') {
	                    $key = 'Size';
	                }
					// don't add list price,Feature,Title into attributes
					if( in_array($key, array('ListPrice', 'Feature', 'Title') ) ) continue;
	                
	                // change dimension name as woocommerce attribute name
	                $attribute_name = $this->the_plugin->cleanTaxonomyName(strtolower($key)); 
					
					// convert value into imploded array
					if( is_array($value) ) {
						$value = $this->the_plugin->multi_implode( $value, ', ' ); 
					}
					
					// Clean
					$value = $this->the_plugin->cleanValue( $value );
					 
					// if is empty attribute don't import
					if( trim($value) == "" ) continue;
					
	                $_product_attributes[$attribute_name] = array(
	                    'name' => $attribute_name,
	                    'value' => $value,
	                    'position' => $position++,
	                    'is_visible' => 1,
	                    'is_variation' => 0,
	                    'is_taxonomy' => 1
	                );
					
	                $this->add_attribute( $post_id, $key, $value );
	            }
	        }
	        
	        // update product attribute
	        update_post_meta($post_id, '_product_attributes', $_product_attributes);
			
			$this->attrclean_clean_all( 'array' ); // delete duplicate attributes
			
	        // refresh attribute cache
	        //$dmtransient_name = 'wc_attribute_taxonomies';
	        //$dmattribute_taxonomies = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies");
	        //set_transient($dmtransient_name, $dmattribute_taxonomies);
	    }
	
	    // add woocommrce attribute values
	    public function add_attribute($post_id, $key, $value) 
	    { 
	        global $wpdb;
	        global $woocommerce;
			 
	        // get attribute name, label
	        if ( isset($this->amz_settings['attr_title_normalize']) && $this->amz_settings['attr_title_normalize'] == 'yes' )
	        	$attribute_label = $this->attrclean_splitTitle( $key );
			else
				$attribute_label = $key;
	        $attribute_name = $this->the_plugin->cleanTaxonomyName($key, false);

	        // set attribute type
	        $attribute_type = 'select';
	        
	        // check for duplicates
	        $attribute_taxonomies = $wpdb->get_var("SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = '".esc_sql($attribute_name)."'");
	        
	        if ($attribute_taxonomies) {
	            // update existing attribute
	            $wpdb->update(
                    $wpdb->prefix . 'woocommerce_attribute_taxonomies', array(
		                'attribute_label' => $attribute_label,
		                'attribute_name' => $attribute_name,
		                'attribute_type' => $attribute_type,
		                'attribute_orderby' => 'name'
                    ), array('attribute_name' => $attribute_name)
	            );
	        } else {
	            // add new attribute
	            $wpdb->insert(
	                $wpdb->prefix . 'woocommerce_attribute_taxonomies', array(
	                	'attribute_label' => $attribute_label,
	                	'attribute_name' => $attribute_name,
	                	'attribute_type' => $attribute_type,
	                	'attribute_orderby' => 'name'
	                )
	            );
	        }

	        // avoid object to be inserted in terms
	        if (is_object($value))
	            return;
	
	        // add attribute values if not exist
	        $taxonomy = $this->the_plugin->cleanTaxonomyName($attribute_name);
			
	        if( is_array( $value ) )
	        {
	            $values = $value;
	        }
	        else
	        {
	            $values = array($value);
	        }
  
	        // check taxonomy
	        if( !taxonomy_exists( $taxonomy ) ) 
	        {
	            // add attribute value
	            foreach ($values as $attribute_value) {
	            	$attribute_value = (string) $attribute_value;

	                if (is_string($attribute_value)) {
	                    // add term
	                    //$name = stripslashes($attribute_value);
						$name = $this->the_plugin->cleanValue( $attribute_value ); // 2015, october 28 - attributes bug update!
	                    $slug = sanitize_title($name);
						
	                    if( !term_exists($name) ) {
	                        if( trim($slug) != '' && trim($name) != '' ) {
	                        	$this->the_plugin->db_custom_insert(
	                        		$wpdb->terms,
	                        		array(
	                        			'values' => array(
		                                	'name' => $name,
		                                	'slug' => $slug
										),
										'format' => array(
											'%s', '%s'
										)
	                        		),
	                        		true
	                        	);
	                            /*$wpdb->insert(
                                    $wpdb->terms, array(
		                                'name' => $name,
		                                'slug' => $slug
                                    )
	                            );*/
	
	                            // add term taxonomy
	                            $term_id = $wpdb->insert_id;
	                        	$this->the_plugin->db_custom_insert(
	                        		$wpdb->term_taxonomy,
	                        		array(
	                        			'values' => array(
		                                	'term_id' => $term_id,
		                                	'taxonomy' => $taxonomy
										),
										'format' => array(
											'%d', '%s'
										)
	                        		),
	                        		true
	                        	);
	                            /*$wpdb->insert(
                                    $wpdb->term_taxonomy, array(
		                                'term_id' => $term_id,
		                                'taxonomy' => $taxonomy
                                    )
	                            );*/
								$term_taxonomy_id = $wpdb->insert_id;
								$__dbg = compact('taxonomy', 'attribute_value', 'term_id', 'term_taxonomy_id');
								//var_dump('<pre>1: ',$__dbg,'</pre>');
	                        }
	                    } else {
	                        // add term taxonomy
	                        $term_id = $wpdb->get_var("SELECT term_id FROM {$wpdb->terms} WHERE name = '".esc_sql($name)."'");
	                        $this->the_plugin->db_custom_insert(
	                        	$wpdb->term_taxonomy,
	                        	array(
	                        		'values' => array(
		                           		'term_id' => $term_id,
		                           		'taxonomy' => $taxonomy
									),
									'format' => array(
										'%d', '%s'
									)
	                        	),
	                        	true
	                        );
	                        /*$wpdb->insert(
                           		$wpdb->term_taxonomy, array(
		                            'term_id' => $term_id,
		                            'taxonomy' => $taxonomy
                                )
	                        );*/
							$term_taxonomy_id = $wpdb->insert_id;
							$__dbg = compact('taxonomy', 'attribute_value', 'term_id', 'term_taxonomy_id');
							//var_dump('<pre>1c: ',$__dbg,'</pre>');
	                    }
	                }
	            }
	        }
	        else 
	        {
	            // get already existing attribute values
	            $attribute_values = array();
	            /*$terms = get_terms($taxonomy, array('hide_empty' => true));
				if( !is_wp_error( $terms ) ) {
	            	foreach ($terms as $term) {
	                	$attribute_values[] = $term->name;
	            	}
				} else {
					$error_string = $terms->get_error_message();
					var_dump('<pre>',$error_string,'</pre>');  
				}*/
				$terms = $this->the_plugin->load_terms($taxonomy);
	            foreach ($terms as $term) {
	               	$attribute_values[] = $term->name;
	            }
	            
	            // Check if $attribute_value is not empty
	            if( !empty( $attribute_values ) )
	            {
	                foreach( $values as $attribute_value ) 
	                {
	                	$attribute_value = (string) $attribute_value;
						$attribute_value = $this->the_plugin->cleanValue( $attribute_value ); // 2015, october 28 - attributes bug update!
	                    if( !in_array( $attribute_value, $attribute_values ) ) 
	                    {
	                        // add new attribute value
	                        $__term_and_tax = wp_insert_term($attribute_value, $taxonomy);
							$__dbg = compact('taxonomy', 'attribute_value', '__term_and_tax');
							//var_dump('<pre>1b: ',$__dbg,'</pre>');
	                    }
	                }
	            }
	        }
	
	        // Add terms
	        if( is_array( $value ) )
	        {
	            foreach( $value as $dm_v )
	            {
	            	$dm_v = (string) $dm_v;
	                if( !is_array($dm_v) && is_string($dm_v)) {
	                	$dm_v = $this->the_plugin->cleanValue( $dm_v ); // 2015, october 28 - attributes bug update!
	                    $__term_and_tax = wp_insert_term( $dm_v, $taxonomy );
						$__dbg = compact('taxonomy', 'dm_v', '__term_and_tax');
						//var_dump('<pre>2: ',$__dbg,'</pre>');
	                }
	            }
	        }
	        else
	        {
	        	$value = (string) $value;
	            if( !is_array($value) && is_string($value) ) {
	            	$value = $this->the_plugin->cleanValue( $value ); // 2015, october 28 - attributes bug update!
	                $__term_and_tax = wp_insert_term( $value, $taxonomy );
					$__dbg = compact('taxonomy', 'value', '__term_and_tax');
					//var_dump('<pre>2b: ',$__dbg,'</pre>');
	            }
	        }
			
	        // wp_term_relationships (object_id to term_taxonomy_id)
	        if( !empty( $values ) )
	        {
	            foreach( $values as $term )
	            {
	            	
	                if( !is_array($term) && !is_object( $term ) )
	                { 
	                    $term = sanitize_title($term);
	                    
	                    $term_taxonomy_id = $wpdb->get_var( "SELECT tt.term_taxonomy_id FROM {$wpdb->terms} AS t INNER JOIN {$wpdb->term_taxonomy} as tt ON tt.term_id = t.term_id WHERE t.slug = '".esc_sql($term)."' AND tt.taxonomy = '".esc_sql($taxonomy)."'" );
  
	                    if( $term_taxonomy_id ) 
	                    {
	                        $checkSql = "SELECT * FROM {$wpdb->term_relationships} WHERE object_id = {$post_id} AND term_taxonomy_id = {$term_taxonomy_id}";
	                        if( !$wpdb->get_var($checkSql) ) {
	                            $wpdb->insert(
	                                    $wpdb->term_relationships, array(
			                                'object_id' => $post_id,
			                                'term_taxonomy_id' => $term_taxonomy_id
	                                    )
	                            );
	                        }
	                    }
	                }
	            }
	        }
	    }

		/**
		 * Product Price - from Amazon
		 */
		public function productAmazonPriceIsZero( $thisProd ) {
			$multiply_factor =  ($this->amz_settings['country'] == 'co.jp') ? 1 : 0.01;
  
			$price_setup = (isset($this->amz_settings["price_setup"]) && $this->amz_settings["price_setup"] == 'amazon_or_sellers' ? 'amazon_or_sellers' : 'only_amazon');
			//$offers_from = ( $price_setup == 'only_amazon' ? 'Amazon' : 'All' );
			
            $prodprice = array('regular_price' => '');
 
			// list price
			$offers = array(
				'ListPrice' 					=> isset($thisProd['ItemAttributes']['ListPrice']['Amount']) ? ($thisProd['ItemAttributes']['ListPrice']['Amount'] * $multiply_factor ) : '',
				'LowestNewPrice' 		=> isset($thisProd['Offers']['Offer']['OfferListing']['Price']['Amount']) ? ($thisProd['Offers']['Offer']['OfferListing']['Price']['Amount'] * $multiply_factor) : '',
				'Offers'						=> isset($thisProd['Offers']) ? $thisProd['Offers'] : array()
			);
  
			if( $price_setup == 'amazon_or_sellers' && isset($thisProd['OfferSummary']['LowestNewPrice']['Amount']) ) {
				$offers['LowestNewPrice'] = ($thisProd['OfferSummary']['LowestNewPrice']['Amount'] * $multiply_factor);
			}

			// regular/
			$prodprice['regular_price'] = $offers['ListPrice'];

			// regular/ if we don't have a regular price or lowest new price from offer is greater than current list price
			if( 
				((float)$offers['ListPrice'] == 0.00)
				|| ($offers['LowestNewPrice'] > $offers['ListPrice'])
			) {
				$prodprice['regular_price'] = $offers['LowestNewPrice'];
			}

			// regular/ if still don't have any regular price, try to get from VariationSummary (ex: Apparel category)
			if( !isset($prodprice['regular_price']) || (float)$prodprice['regular_price'] == 0.00 ) {
				$prodprice['regular_price'] = isset($thisProd['VariationSummary']['LowestPrice']['Amount']) ? ( $thisProd['VariationSummary']['LowestPrice']['Amount'] * $multiply_factor ) : '';
			}
  
			if ( empty($prodprice['regular_price']) || (float)$prodprice['regular_price'] <= 0.00 ) return true;
			return false;
		}

		public function productPriceUpdate( $thisProd, $post_id='', $return=true )
		{
			$multiply_factor =  ($this->amz_settings['country'] == 'co.jp') ? 1 : 0.01;

            $price_setup = (isset($this->amz_settings["price_setup"]) && $this->amz_settings["price_setup"] == 'amazon_or_sellers' ? 'amazon_or_sellers' : 'only_amazon');
            //$offers_from = ( $price_setup == 'only_amazon' ? 'Amazon' : 'All' );

			// if any of regular | sale price set to auto => no product price syncronization!
			$priceStatus = $this->productPriceGetRegularSaleStatus( $post_id );
			if ( $priceStatus['regular'] == 'selected' || $priceStatus['sale'] == 'selected' ) {
				if( $return == true ) {
					die(json_encode(array(
						'status' => 'valid',
						'data'		=> array(
							'_sale_price' => woocommerce_price( get_post_meta($post_id, '_regular_price', true) ),
							'_regular_price' => woocommerce_price( get_post_meta($post_id, '_sale_price', true) ),
							'_price_update_date' => date('F j, Y, g:i a', get_post_meta($post_id, '_price_update_date', true))
						)
					)));
				}
				return true;
			} // end priceStatus

			// list price
			$offers = array(
				'ListPrice' 						=> isset($thisProd['ItemAttributes']['ListPrice']['Amount']) ? ($thisProd['ItemAttributes']['ListPrice']['Amount'] * $multiply_factor ) : '',
				'LowestNewPrice' 			=> isset($thisProd['Offers']['Offer']['OfferListing']['Price']['Amount']) ? ($thisProd['Offers']['Offer']['OfferListing']['Price']['Amount'] * $multiply_factor) : '',
				'LowestNewSalePrice' 	=> isset($thisProd['Offers']['Offer']['OfferListing']['SalePrice']['Amount']) ? ($thisProd['Offers']['Offer']['OfferListing']['SalePrice']['Amount'] * $multiply_factor) : false,
				'LowestPrice' 				=> isset($thisProd['VariationSummary']['LowestSalePrice']['Amount']) ? ($thisProd['VariationSummary']['LowestSalePrice']['Amount'] * $multiply_factor) : '',
				'Offers'							=> isset($thisProd['Offers']) ? $thisProd['Offers'] : array()
			);
  
			if( $price_setup == 'amazon_or_sellers' && isset($thisProd['OfferSummary']['LowestNewPrice']['Amount']) ) {
				$offers['LowestNewPrice'] = ($thisProd['OfferSummary']['LowestNewPrice']['Amount'] * $multiply_factor);
			}

			// get current product meta, update the values of prices and update it back
			$product_meta = get_post_meta( $post_id, '_product_meta', true );
			$product_meta = ! is_array($product_meta) ? array('product' => array()) : $product_meta;

			// regular/
			$product_meta['product']['regular_price'] = $offers['ListPrice'];
			$product_meta = ! is_array($product_meta) ? array('product' => array()) : $product_meta;

			// regular/ if we don't have a regular price or lowest new price from offer is greater than current list price
			if( 
				((float)$offers['ListPrice'] == 0.00)
				|| ($offers['LowestNewPrice'] > $offers['ListPrice'])
			) {
				$product_meta['product']['regular_price'] = $offers['LowestNewPrice'];
			}

			// regular/ if still don't have any regular price, try to get from VariationSummary (ex: Apparel category)
			if( !isset($product_meta['product']['regular_price']) || (float)$product_meta['product']['regular_price'] == 0.00 ) {
				$product_meta['product']['regular_price'] = isset($thisProd['VariationSummary']['LowestPrice']['Amount']) ? ( $thisProd['VariationSummary']['LowestPrice']['Amount'] * $multiply_factor ) : '';
			}

			// sale/ from Offers or OfferSummary
			if( isset($offers['LowestNewPrice']) ) {
				$product_meta['product']['sales_price'] = $offers['LowestNewPrice'];
				// if offer price is higher than regular price, delete the offer
				if( $offers['LowestNewPrice'] >= $product_meta['product']['regular_price'] ){
					unset($product_meta['product']['sales_price']);
				}
			}
			// sale/ from Offers or OfferSummary - for variation child
			if( $price_setup == 'amazon_or_sellers' || empty($product_meta['product']['sales_price']) ) {
				if( isset($offers['LowestNewSalePrice']) && (false !== $offers['LowestNewSalePrice']) ) {
					$product_meta['product']['sales_price'] = $offers['LowestNewSalePrice']; 
					// if offer price is higher than regular price, delete the offer
					if( $offers['LowestNewSalePrice'] >= $product_meta['product']['regular_price'] ){
						unset($product_meta['product']['sales_price']);
					}
				}
			}

			// sale/ from VariationSummary (ex: Apparel category)
			if( isset($offers['LowestPrice']) && empty($product_meta['product']['sales_price']) ) {
				$product_meta['product']['sales_price'] = $offers['LowestPrice']; 
				// if offer price is higher than regular price, delete the offer
				if( $offers['LowestPrice'] >= $product_meta['product']['regular_price'] ){
					unset($product_meta['product']['sales_price']);
				}
			}
			
			// set product price metas!
			if ( isset($product_meta['product']['sales_price']) && !empty($product_meta['product']['sales_price']) ) {
				update_post_meta($post_id, '_sale_price', $product_meta['product']['sales_price']);
				$this->productPriceSetRegularSaleMeta($post_id, 'sale', array(
					'auto' => number_format( (float)($product_meta['product']['sales_price']), 2, '.', '')
				));
			} else { // new sale price is 0
				update_post_meta($post_id, '_sale_price', '');
				$this->productPriceSetRegularSaleMeta($post_id, 'sale', array(
					'auto' => ''
				));
			}
			update_post_meta($post_id, '_price_update_date', time());
			update_post_meta($post_id, '_regular_price', $product_meta['product']['regular_price']);
			$this->productPriceSetRegularSaleMeta($post_id, 'regular', array(
				'auto' => number_format((float)($product_meta['product']['regular_price']), 2, '.', '')
			));
			update_post_meta($post_id, '_price', (isset($product_meta['product']['sales_price']) && trim($product_meta['product']['sales_price']) != "" ? $product_meta['product']['sales_price'] : $product_meta['product']['regular_price']));

			// set product price extra metas!
			$retExtra = $this->productPriceSetMeta( $thisProd, $post_id, 'return' );

			if( $return == true ) {
				die(json_encode(array(
					'status' => 'valid',
					'data'		=> array(
						'_sale_price' => isset($product_meta['product']['sales_price']) ? woocommerce_price($product_meta['product']['sales_price']) : '-',
						'_regular_price' => woocommerce_price($product_meta['product']['regular_price']),
						'_price_update_date' => date('F j, Y, g:i a', time())
					)
				)));
			}
		}

        public function get_productPrice( $thisProd )
        {
            $multiply_factor =  ($this->amz_settings['country'] == 'co.jp') ? 1 : 0.01;
            
            $price_setup = (isset($this->amz_settings["price_setup"]) && $this->amz_settings["price_setup"] == 'amazon_or_sellers' ? 'amazon_or_sellers' : 'only_amazon');
            //$offers_from = ( $price_setup == 'only_amazon' ? 'Amazon' : 'All' );
            
            $ret = array(
                'status'                => 'valid',
                '_price'                => '',
                '_sale_price'           => '',
                '_regular_price'        => '',
                '_price_update_date'    => '',
                '_currency'					=> '',
            );
 
            // list price
            $offers = array(
                'ListPrice'         				=> isset($thisProd['ItemAttributes']['ListPrice']['Amount']) ? ($thisProd['ItemAttributes']['ListPrice']['Amount'] * $multiply_factor ) : '',
                'LowestNewPrice'   		=> isset($thisProd['Offers']['Offer']['OfferListing']['Price']['Amount']) ? ($thisProd['Offers']['Offer']['OfferListing']['Price']['Amount'] * $multiply_factor) : '',
                'LowestNewSalePrice' 	=> isset($thisProd['Offers']['Offer']['OfferListing']['SalePrice']['Amount']) ? ($thisProd['Offers']['Offer']['OfferListing']['SalePrice']['Amount'] * $multiply_factor) : false,
                'LowestPrice'       			=> isset($thisProd['VariationSummary']['LowestSalePrice']['Amount']) ? ($thisProd['VariationSummary']['LowestSalePrice']['Amount'] * $multiply_factor) : '',
                'Offers'            				=> isset($thisProd['Offers']) ? $thisProd['Offers'] : array()
            );
 
            if( $price_setup == 'amazon_or_sellers' && isset($thisProd['OfferSummary']['LowestNewPrice']['Amount']) ) {
                $offers['LowestNewPrice'] = ($thisProd['OfferSummary']['LowestNewPrice']['Amount'] * $multiply_factor);
            }

            // get current product meta, update the values of prices and update it back
            $product_meta = array('product' => array());

			// regular
            $product_meta['product']['regular_price'] = $offers['ListPrice'];
			$product_meta['product']['currency'] = isset($thisProd['ItemAttributes']['ListPrice']['CurrencyCode']) ? $thisProd['ItemAttributes']['ListPrice']['CurrencyCode'] : '';
    
            // regular/ if we don't have a regular price or lowest new price from offer is greater than current list price
            if( 
                ((float)$offers['ListPrice'] == 0.00)
                || ($offers['LowestNewPrice'] > $offers['ListPrice'])
            ) {
                $product_meta['product']['regular_price'] = $offers['LowestNewPrice'];

				// currency
				$product_meta['product']['currency'] = isset($thisProd['Offers']['Offer']['OfferListing']['Price']['CurrencyCode']) ? $thisProd['Offers']['Offer']['OfferListing']['Price']['CurrencyCode'] : $product_meta['product']['currency'];
				if( $price_setup == 'amazon_or_sellers' && isset($thisProd['OfferSummary']['LowestNewPrice']['Amount']) ) {
					$product_meta['product']['currency'] = isset($thisProd['OfferSummary']['LowestNewPrice']['CurrencyCode']) ? $thisProd['OfferSummary']['LowestNewPrice']['CurrencyCode'] : $product_meta['product']['currency'];
				}
            }
  
            // regular/ if still don't have any regular price, try to get from VariationSummary (ex: Apparel category)
            if( !isset($product_meta['product']['regular_price']) || (float)$product_meta['product']['regular_price'] == 0.00 ) {
                $product_meta['product']['regular_price'] = isset($thisProd['VariationSummary']['LowestPrice']['Amount']) ? ( $thisProd['VariationSummary']['LowestPrice']['Amount'] * $multiply_factor ) : '';

				// currency
				$product_meta['product']['currency'] = isset($thisProd['VariationSummary']['LowestPrice']['CurrencyCode']) ? $thisProd['VariationSummary']['LowestPrice']['CurrencyCode'] : $product_meta['product']['currency'];
            }

			// sale/ from Offers or OfferSummary
            if( isset($offers['LowestNewPrice']) ) {
                $product_meta['product']['sales_price'] = $offers['LowestNewPrice']; 
                // if offer price is higher than regular price, delete the offer
                if( $offers['LowestNewPrice'] >= $product_meta['product']['regular_price'] ){
                    unset($product_meta['product']['sales_price']);
                }
            }
			// sale/ from Offers or OfferSummary - for variation child
			if( $price_setup == 'amazon_or_sellers' || empty($product_meta['product']['sales_price']) ) {
				if( isset($offers['LowestNewSalePrice']) && (false !== $offers['LowestNewSalePrice']) ) {
					$product_meta['product']['sales_price'] = $offers['LowestNewSalePrice']; 
					// if offer price is higher than regular price, delete the offer
					if( $offers['LowestNewSalePrice'] >= $product_meta['product']['regular_price'] ){
						unset($product_meta['product']['sales_price']);
					}
				}
			}

			// sale/ from VariationSummary (ex: Apparel category)
            if( isset($offers['LowestPrice']) && empty($product_meta['product']['sales_price']) ) {
                $product_meta['product']['sales_price'] = $offers['LowestPrice']; 
                // if offer price is higher than regular price, delete the offer
                if( $offers['LowestPrice'] >= $product_meta['product']['regular_price'] ){
                    unset($product_meta['product']['sales_price']);
                }
            }

            // set product price metas!
            $ret['_currency'] = $product_meta['product']['currency'];
            if ( isset($product_meta['product']['sales_price']) && !empty($product_meta['product']['sales_price']) ) {
                $ret['_sale_price'] = $product_meta['product']['sales_price'];
            } else { // new sale price is 0
                $ret['_sale_price'] = '';
            }
            $ret['_price_update_date'] = time();
            $ret['_regular_price'] = $product_meta['product']['regular_price'];
            $ret['_price'] = (isset($product_meta['product']['sales_price']) && trim($product_meta['product']['sales_price']) != ""
            	? $product_meta['product']['sales_price'] : $product_meta['product']['regular_price']);

            return $ret;
        }
	
        /**
         * Product Variations
         */
		public function set_woocommerce_variations( $retProd, $post_id, $variationNumber ) 
		{
	        global $woocommerce;
			
            $ret = array(
                'status'        => 'valid',
                'msg'           => '',
                'nb_found'      => 0,
                'nb_parsed'     => 0,
            );

			//$var_mode = '';
			$VariationDimensions = array();
			 
			// convert $variationNumber into number
			if( $variationNumber == 'yes_all' ){
				$variationNumber = 500; // 500 variations per product is enough
			}
			elseif( $variationNumber == 'no' ){
				$variationNumber = 0;
			}
            else{
                $variationNumber = explode(  "_", $variationNumber );
                $variationNumber = end( $variationNumber );
            }
            $variationNumber = (int) $variationNumber;
            
            $status = 'valid';
            if ( empty($variationNumber)
                || !isset($retProd['Variations']['TotalVariations']) || $retProd['Variations']['TotalVariations'] <= 0 ) {

                $status = 'invalid';
                return array_merge($ret, array(
                    'status'    => $status,
                    'msg'       => sprintf( $status . ': no variations found (number of variations setting: %s).', $variationNumber ),
                ));
            }

            $offset = 0; 
	        if ( $status == 'valid' ) { // status is valid

                $this->the_plugin->timer_start(); // Start Timer

	            // its not a simple product, it is a variable product
	            wp_set_post_terms($post_id, 'variable', 'product_type', false);
				  
	            // initialize the variation dimensions array
	            if (count($retProd['Variations']['VariationDimensions']['VariationDimension']) == 1) {
	                $VariationDimensions[$retProd['Variations']['VariationDimensions']['VariationDimension']] = array();
	            } else {
	                // Check if VariationDimension is given
	                if(count($retProd['Variations']['VariationDimensions']['VariationDimension']) > 0 ) {
	                    foreach ($retProd['Variations']['VariationDimensions']['VariationDimension'] as $dim) {
	                        $VariationDimensions[$dim] = array();
	                    }
	                }
	            }
                
                $ret['nb_found'] = $retProd['Variations']['TotalVariations'];
	            
	            // loop through the variations
	            //if (count($retProd['Variations']['Item']) == 1) {
	            if ($retProd['Variations']['TotalVariations'] == 1) { // --fix 2015.03.19

	                $variation_item = $retProd['Variations']['Item'];
					if ( is_array($variation_item) ) {
						$variation_item['country'] = $retProd['country'];
					}
	                $VariationDimensions = $this->variation_post( $variation_item, $post_id, $VariationDimensions );
	                //$var_mode = 'create';
                    $offset ++;
	            } else {
	            	
	                // if the variation still has items 
	                //$var_mode = 'variation';
					$cc = 0;
					
	                // Loop through the variation
	                for( $cc = 1; $cc <= $variationNumber; $cc++ )
	                {
	                    // Check if there are still variations
	                    if( $offset > ((int)$retProd['Variations']['TotalVariations'] - 1) ) {
	                        break;
	                    }
	                    //else if ( $offset == ((int)$retProd['Variations']['TotalVariations'] - 1) ) {
	                    //    //$var_mode = 'create';
	                    //}
	                    
	                    // Get the specifc variation 
	                    $variation_item = $retProd['Variations']['Item'][$offset];
						if ( is_array($variation_item) ) {
							$variation_item['country'] = $retProd['country'];
						}

	                    // Create the variation post
	                    $VariationDimensions = $this->variation_post( $variation_item, $post_id, $VariationDimensions );
	                    
	                    // Increase the offset
	                    $offset++;
	                }
	            }
 
	            $tempProdAttr = get_post_meta( $post_id, '_product_attributes', true );
  
	            foreach( $VariationDimensions as $name => $values )
	            {
	                if($name != '') {
	                    $dimension_name = $this->the_plugin->cleanTaxonomyName(strtolower($name));

	                	// convert value into imploded array
						if( is_array($values) ) {
							$values = $this->the_plugin->multi_implode( $values, ', ' ); 
						}

						// Clean
						$values = $this->the_plugin->cleanValue( $values );

	                    $tempProdAttr[$dimension_name] = array(
	                        'name' => $dimension_name,
	                        'value' => '', //$values, // 2015, october 28 - attributes bug update!
	                        'position' => 0,
	                        'is_visible' => 1,
	                        'is_variation' => 1,
	                        'is_taxonomy' => 1,
	                    );
						
	                    //$this->add_attribute( $post_id, $name, $values );
	                }
	            }

	            //update_post_meta($post_id, '_product_attributes', serialize($tempProdAttr));
	            // 2015-08-26 fix/ remove double serialize
	            
	            update_post_meta($post_id, '_product_attributes', $tempProdAttr);
                
                if ( $offset > 0 ) {
                    $this->the_plugin->add_last_imports('last_import_variations', array(
                        'duration'      => $this->the_plugin->timer_end(),
                        'nb_items'      => $offset,
                    )); // End Timer & Add Report
                }
	        } // end status is valid

            // status
            $ret['nb_parsed'] = $offset;

            $status = array();
            $status[] = $variationNumber > 0;
            $status[] = empty($ret['nb_found']) || empty($ret['nb_parsed']);
            $status = $status[0] && $status[1] ? 'invalid' : 'valid';

            return array_merge($ret, array(
                'status'    => $status,
                'msg'       => sprintf( $status . ': %s product variations added from %s variations found (number of variations setting: %s).', $ret['nb_parsed'], $ret['nb_found'], $variationNumber ),
            ));
	    }
		
		public function variation_post( $variation_item, $post_id, $VariationDimensions ) 
		{
	        global $woocommerce, $wpdb;

			$variation_post = get_post( $post_id, ARRAY_A );

			if ( ! is_array($variation_item) || empty($variation_item) ) {
				$variation_item = array();
			}

			$variation_item__ = array_merge_recursive($variation_item, array(
				'__parent_asin'			=> isset($variation_item['ParentASIN']) ? $variation_item['ParentASIN'] : '',
				'__parent_content'	=> $variation_post['post_content'],
			));
			$product_desc = $this->the_plugin->product_build_desc($variation_item__, false);
			$excerpt = isset($product_desc['short']) ? $product_desc['short'] : '';
			$desc = isset($product_desc['desc']) ? $product_desc['desc'] : '';

			// ::
			// update variation parent with desc,excerpt from variation child if found!
			$desc_used = array();
			$args_update = array();
			$args_update['ID'] = $post_id;

			$desc_used = array(
				'date_done'				=> date("Y-m-d H:i:s"), // only for debug purpose
			);

			if ( !empty($desc) ) {
				$__post_content = $variation_post['post_content'];
				$__post_content = preg_replace('/\[gallery\]/imu', '', $__post_content);
				$__post_content = preg_replace('/\[amz_corss_sell asin\=".*"\]/imu', '', $__post_content); // [amz_corss_sell asin="B01G7TG6SW"]
				$__post_content = trim( $__post_content );

				if ( $__post_content == '' ) {
					$args_update['post_content'] = $desc;

					$desc_used = array(
						'child_asin'					=> isset($variation_item['ASIN']) ? $variation_item['ASIN'] : '',
					);
				}
			}
			if ( !empty($excerpt) ) {
				$__post_content = $variation_post['post_excerpt'];
				$__post_content = trim( $__post_content );

				if ( $__post_content == '' ) {
					$args_update['post_excerpt'] = $excerpt;

					//$desc_used = array(
					//	'child_asin'					=> isset($variation_item['ASIN']) ? $variation_item['ASIN'] : '',
					//);
				}
			}

			// update the post if needed
			if(count($args_update) > 1){ // because ID is allways the same!
				wp_update_post( $args_update );

				if ( !empty($desc_used) && isset($desc_used['child_asin']) ) {
					update_post_meta( $post_id, '_amzaff_desc_used', $desc_used );
				}
			}

			// ::
			// insert variation child
	        $variation_post['post_title'] = isset($variation_item['ItemAttributes']['Title']) ? $variation_item['ItemAttributes']['Title'] : '';
			$variation_post['post_content'] = $desc;
			$variation_post['post_excerpt'] = $excerpt;
			$variation_post['post_status'] = 'publish';
	        $variation_post['post_type'] = 'product_variation';
	        $variation_post['post_parent'] = $post_id;
	        unset( $variation_post['ID'] );

	        $variation_post_id = wp_insert_post( $variation_post );

			$images = array();
			$images['Title'] = isset($variation_item['ItemAttributes']['Title']) ? $variation_item['ItemAttributes']['Title'] : uniqid();
            $images['images'] = $this->build_images_data( $variation_item );

			$this->set_product_images( $images, $variation_post_id, $post_id, 1 );

			// set the product price
			$this->productPriceUpdate( $variation_item, $variation_post_id, false );
			
			// than update the metapost
			$this->set_product_meta_options( $variation_item, $variation_post_id, true );
			 
	        // Compile all the possible variation dimensions         
	        if(is_array($variation_item['VariationAttributes']['VariationAttribute']) && isset($variation_item['VariationAttributes']['VariationAttribute'][0]['Name'])) {
	        	
	            foreach ($variation_item['VariationAttributes']['VariationAttribute'] as $va) {

					if ( isset($va['Value']) && !empty($va['Value']) ) {
						// Clean
						$va['Value'] = $this->the_plugin->cleanValue( $va['Value'] );
	
		                $this->add_attribute( $post_id, $va['Name'], $va['Value'] );
	
		                $curarr = $VariationDimensions[$va['Name']];
		                $curarr[$va['Value']] = $va['Value'];
						
		                $VariationDimensions[$va['Name']] = $curarr;
		        
		                $dimension_name = $this->the_plugin->cleanTaxonomyName(strtolower($va['Name']));
		                update_post_meta($variation_post_id, 'attribute_' . $dimension_name, sanitize_title($va['Value']));
					}  
	            }
	        } else {
	        	$var_item = $variation_item['VariationAttributes']['VariationAttribute'];
	            $dmName = isset($var_item['Name']) ? $var_item['Name'] : '';
	            $dmValue = isset($var_item['Value']) ? $var_item['Value'] : '';
				
				if ( !empty($dmValue) ) {
					// Clean
					$dmValue = $this->the_plugin->cleanValue( $dmValue );
	
		            $this->add_attribute( $post_id, $dmName, $dmValue );
		                
		            $curarr = $VariationDimensions[$dmName];
		            $curarr[$dmValue] = $dmValue;
		            $VariationDimensions[$dmName] = $curarr;
		        
		            $dimension_name = $this->the_plugin->cleanTaxonomyName(strtolower($dmName));
		            update_post_meta($variation_post_id, 'attribute_' . $dimension_name, sanitize_title($dmValue));
				}
	        }
	            
	        // refresh attribute cache
	        $dmtransient_name = 'wc_attribute_taxonomies';
	        $dmattribute_taxonomies = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies");
	        set_transient($dmtransient_name, $dmattribute_taxonomies);
            
            // status messages
            $this->the_plugin->opStatusMsgSet(array(
                'msg'       => 'variation inserted with ID: ' . $variation_post_id,
            ));
	
	        return $VariationDimensions;
	    }
		
        /**
         * Product Images
         */
		public function set_product_images( $retProd, $post_id, $parent_id=0, $number_of_images='all' )
		{
		    $ret = array(
                'status'        => 'valid',
                'msg'           => '',
                'nb_found'      => 0,
                'nb_parsed'     => 0,
            );

            $retProd["images"]['large'] = @array_unique($retProd["images"]['large']);
            $retProd["images"]['large'] = @array_filter($retProd["images"]['large']); // remove empty array elements!
            
            $status = 'valid';
            if ( empty($retProd["images"]['large']) ) {
                $status = 'invalid';
                return array_merge($ret, array(
                    'status'    => $status,
                    'msg'       => sprintf( $status . ': no images found (number of images setting: %s).', $number_of_images ),
                ));
            }
            $ret['nb_found'] = count($retProd["images"]['large']);
            
            if( (int) $number_of_images > 0 ){
                $retProd['images']['large'] = array_slice($retProd['images']['large'], 0, (int) $number_of_images);
            }

			$productImages = array();
			
			// try to download the images
			if ( $status == 'valid' ) {
			    //if ( 1 ) {
                //    $this->the_plugin->timer_start(); // Start Timer
                //}

				$step = 0;
				
				// product variation - ONLY 1 IMAGE PER VARIATION
				if ( $parent_id > 0 ) {
					$retProd["images"]['large'] = array_slice($retProd["images"]['large'], 0, 1);
				}
				
				// insert the product into db if is not duplicate
				$amz_prod_status = $this->the_plugin->db_custom_insert(
	               	$this->the_plugin->db->prefix . 'amz_products',
	               	array(
	               		'values' => array(
							'post_id' 		=> $post_id, 
							'post_parent' 	=> $parent_id,
							'title' 		=> isset($retProd["Title"]) ? $retProd["Title"] : 'untitled',
							'type' 			=> (int) $parent_id > 0 ? 'variation' : 'post',
							'nb_assets'		=> count($retProd["images"]['large'])
						),
						'format' => array(
							'%d',
							'%d',
							'%s',
							'%s',
							'%d' 
						)
	                ),
	                true
	            );
				/*$amz_prod_status = $this->the_plugin->db->insert( 
					$this->the_plugin->db->prefix . 'amz_products', 
					array( 
						'post_id' => $post_id, 
						'post_parent' => $parent_id,
						'title' => isset($retProd["Title"]) ? $retProd["Title"] : 'untitled',
						'type' => (int) $parent_id > 0 ? 'variation' : 'post',
						'nb_assets' => count($retProd["images"]['large'])
					), 
					array( 
						'%d',
						'%d',
						'%s',
						'%s',
						'%d' 
					) 
				);*/
			
				foreach ($retProd["images"]['large'] as $key => $value){

					$thumb = isset($retProd["images"]['small'][$key]) ? $retProd["images"]['small'][$key] : $value;
					$image_sizes = isset($retProd["images"]['sizes'][$key]) ? $retProd["images"]['sizes'][$key] : array();
					$this->the_plugin->db_custom_insert(
						$this->the_plugin->db->prefix . 'amz_assets',
						array(
							'values' => array(
								'post_id' 		=> $post_id,
								'asset' 		=> $value,
								'thumb' 		=> $thumb,
								'date_added'	=> date( "Y-m-d H:i:s" ),
								'image_sizes'	=> serialize($image_sizes)
							), 
							'format' => array( 
								'%d',
								'%s',
								'%s',
								'%s',
								'%s'
							)
						),
						true
					);
					/*$this->the_plugin->db->insert( 
						$this->the_plugin->db->prefix . 'amz_assets', 
						array(
							'post_id' => $post_id,
							'asset' => $value,
							'thumb' => $retProd["images"]['small'][$key],
							'date_added' => date( "Y-m-d H:i:s" )
						), 
						array( 
							'%d',
							'%s',
							'%s',
							'%s'
						) 
					);*/
					
					//$ret = $this->the_plugin->download_image($value, $post_id, 'insert', $retProd['Title'], $step);
					//if(count($ret) > 0){
					//	$productImages[] = $ret;
					//}
					$step++;
				}
                
                // execute only for product, not for a variation child
                //if ( $parent_id <= 0 && count($retProd["images"]['large']) > 0 ) {
                //    $this->the_plugin->add_last_imports('last_import_images', array(
                //        'duration'      => $this->the_plugin->timer_end(),
                //        'nb_items'      => isset($retProd["images"]['large']) ? (int) count($retProd["images"]['large']) : 0,
                //    )); // End Timer & Add Report
                //}
			}

            // status
            $ret['nb_parsed'] = $step;

            $status = array();
            $status[] = ( (string) $number_of_images === 'all' ) || ( (int) $number_of_images > 0 );
            $status[] = empty($ret['nb_found']) || empty($ret['nb_parsed']);
            $status = $status[0] && $status[1] ? 'invalid' : 'valid';

			//if ( $this->the_plugin->is_remote_images ) {
			//	$setRemoteImgStatus = $this->build_remote_images( $post_id );
			//}

            return array_merge($ret, array(
                'status'    => $status,
                'msg'       => sprintf( $status . ': %s product assets prepared in database from %s images found (number of images setting: %s).', $ret['nb_parsed'], $ret['nb_found'], $number_of_images ),
            ));

			// add gallery to product
			//$productImages = array(); // remade in assets module!
			//if(count($productImages) > 0){
			//	$the_ids = array();
			//	foreach ($productImages as $key => $value){
			//		$the_ids[] = $value['attach_id'];
			//	}
				
			//	// Add the media gallery image as a featured image for this post
			//	update_post_meta($post_id, "_thumbnail_id", $productImages[0]['attach_id']);
			//	update_post_meta($post_id, "_product_image_gallery", implode(',', $the_ids));
			//}
		}


		/**
		 * Remote amazon images
		 */
		public function build_remote_images( $post_id ) {
			global $wpdb;
			
			$tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products');

		    $ret = array(
                'status'        => 'valid',
                'msg'           => '',
                'nb_found'      => 0,
                'nb_parsed'     => 0,
            );

			$this->the_plugin->timer_start(); // Start Timer

			// get rows from assets table
			$assetsList = $this->get_asset_by_postid( 'all', $post_id, true, true );
			if ( count($assetsList) <= 0 ) {
                $status = 'invalid';
                return array_merge($ret, array(
                    'status'    => $status,
                    'msg'       => $status . ': no images found (for remote).',
                ));
			}

			$status = 'valid';
			$ret['nb_found'] = count($assetsList);
			foreach ($assetsList as $k => $asset) {

				$asset_id = $asset->id;

				$createStatus = $this->create_attachment( $asset );
				if ( $createStatus ) $ret['nb_parsed']++;

				// update row in assets table
				$statUpdAsset = $wpdb->update(
					$tables['assets'],
					array(
						'download_status'	=> 'remote',
						'msg'				=> 'remote'
					),
					array( 'id' => $asset_id ),
					array(
						'%s', '%s'
					),
					array( '%d' )
				);
				if ($statUpdAsset === false) {
				}
			}

			$this->the_plugin->add_last_imports('last_import_images_remote', array(
				'duration'      => $this->the_plugin->timer_end(),
				'nb_items'      => count($assetsList),
			)); // End Timer & Add Report

            return array_merge($ret, array(
                'status'    => $status,
                'msg'       => sprintf( $status . ': %s product assets prepared in database (for remote) from %s images found.', $ret['nb_parsed'], $ret['nb_found'] ),
            ));
		}

		// asset mandatory fields: post_id, asset, image_sizes
		public function create_attachment( $asset ) {
			// Add image in the media library
			$post_id	 = isset($asset->post_id) ? $asset->post_id : 0;
			$image_path  = isset($asset->asset) ? $asset->asset : '';
			if ( empty($post_id) || empty($image_path) ) return false;

			$wp_filetype = wp_check_filetype( basename( $image_path ), null );

			$image_name = preg_replace( '/\.[^.]+$/', '', basename( $image_path ) );
			$rename_image = isset($this->amz_settings["rename_image"]) ? $this->amz_settings["rename_image"] : 'product_title';
			if ( 'product_title' == $rename_image ) {
				$image_name = isset($asset->title) && !empty($asset->title) ? $asset->title : $image_name;
			}
			//else {
			//	$image_name = uniqid();
			//}
			$image_name = sanitize_file_name($image_name);
			$image_name = preg_replace("/[^a-zA-Z0-9-]/", "", $image_name);
			$image_name = substr($image_name, 0, 200);

			$attachment = array(
				// 'guid' 			=> $image_url,
				'post_mime_type' => $wp_filetype['type'],
				'post_title'     => $image_name,
				'post_content'   => '',
				'post_status'    => 'inherit'
			);

			// insert row in wp_posts & insert meta_key '_wp_attached_file' in wp_postmeta
			$attach_id = wp_insert_attachment( $attachment, $image_path, $post_id  );
			//$attach_id = 2526; //DEBUG!
			if ( !$attach_id ) return false;
   
			// insert meta_key '_wp_attachment_metadata' in wp_postmeta
			$this->set_attachment_metadata( $attach_id, $asset );

			// build attachment parent metadata
			$dwimg = array(
				'attach_id' 		=> $attach_id,
				'image_path' 		=> $image_path,
				//'hash'				=> $hash
			);

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

			// product gallery
			$current_prod_gallery = get_post_meta($post_id, "_product_image_gallery", true);
			if ( empty($current_prod_gallery) ) $__current_prod_gallery = array();
			else $__current_prod_gallery = explode(',', $current_prod_gallery);
			$__current_prod_gallery = array_merge( $__current_prod_gallery, array($dwimg['attach_id']) );
			$__current_prod_gallery = array_unique($__current_prod_gallery);
			update_post_meta($post_id, "_product_image_gallery", implode(',', $__current_prod_gallery));

			return true;			
		}

		private function set_attachment_metadata( $attach_id, $asset ) {
			$image_sizes = isset($asset->image_sizes) ? maybe_unserialize($asset->image_sizes) : array();
			if ( empty($image_sizes) || !is_array($image_sizes) ) {
				$image_sizes = array();
				$image_sizes['large'] = array(
					'url'			=> $asset->asset,
					'width'			=> 500,
					'height'		=> 500,
				);
				$image_sizes['thumbnail'] = array(
					'url'			=> $asset->thumb,
					'width'			=> 45,
					'height'		=> 45,
				);
			}
 
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			//$attach_data = wp_generate_attachment_metadata( $attach_id, $image_path );

			$attach_data = array(
				'file'			=> 0,
				'width'			=> 0,
				'height'		=> 0,
				'sizes'			=> array(),
				'image_meta' 	=> array(
					'aperture' => '0',
					'credit' => '',
					'camera' => '',
					'caption' => '',
					'created_timestamp' => '0',
					'copyright' => '',
					'focal_length' => '0',
					'iso' => '0',
					'shutter_speed' => '0',
					'title' => '',
					'orientation' => '0',
					'keywords' => array (),
				),
			);
			$original = $this->_choose_image_original( 'large', $image_sizes );
			if ( !empty($original) ) {
				//$wp_filetype = wp_check_filetype( basename( $original['url'] ), null );
				$attach_data = array_replace_recursive($attach_data, array(
					'file'			=> $original['url'],
					'width'			=> $original['width'],
					'height'		=> $original['height'],
					//'mime-type'		=> $wp_filetype['type'],
				));
			}
 
			$wp_sizes = $this->the_plugin->get_image_sizes();
			//var_dump('<pre>', 'attach_id', $attach_id, 'image_sizes', $image_sizes, 'wp_sizes', $wp_sizes, '</pre>');
			//var_dump('<pre>', '---------------------------------', '</pre>'); 
			foreach ($wp_sizes as $size => $props) {

				//var_dump('<pre>', $size, '---------------','</pre>');
				$found_size = $this->_choose_image_size_from_amazon( $props, $image_sizes );
				//var_dump('<pre>',$found_size,'</pre>');

				if ( !empty($found_size) ) {
					$wp_filetype = wp_check_filetype( basename( $found_size['url'] ), null );
					$attach_data['sizes']["$size"] = array(
						'file'			=> basename( $found_size['url'] ),
						'width'			=> $found_size['width'],
						'height'		=> $found_size['height'],
						'mime-type'		=> $wp_filetype['type'],
					);
				}
			}
			//var_dump('<pre>', $attach_data, '</pre>'); die('debug...');
			wp_update_attachment_metadata( $attach_id, $attach_data );
		}
		
		private function _choose_image_original( $size_alias='large', $image_sizes=array() ) {
			if ( empty($image_sizes) ) return false;

			// selected size as original
			if ( isset($image_sizes["$size_alias"]) ) {
				return $image_sizes["$size_alias"];
			}

			// we try to find biggest image by width
			$current = array('url' => '', 'width' => 0, 'height' => 0);
			foreach ($image_sizes as $_size => $props) {
				if ( (int) $props['width'] <= (int) $current['width'] ) {
					continue 1;
				}
				$current = $props;
			}
			return $current;
		}

		private function _choose_image_size_from_amazon( $size, $image_sizes=array() ) {
			if ( empty($image_sizes) ) return false;

			$diff = array();
			foreach ($image_sizes as $_size => $props) {
				// found exact width
				if ( (int) $size['width'] == (int) $props['width'] ) {
					return $props;
				}
				$diff["$_size"] = (int) $props['width'] - (int) $size['width'];
			}
			$positive = array_filter( $diff, array($this, '_positive') );
			$negative = array_filter( $diff, array($this, '_negative') );
  
			$found = false; $found_pos = false; $found_neg = false;
			if ( !empty($positive) ) {
				$found_pos = min( $positive );
			}
			if ( !empty($negative) ) {
				$found_neg = max( $negative );
			}
  
			if ( !empty($found_pos) && !empty($found_neg) ) {
				if ( $found_pos > 100 && ( $found_pos > ceil(3 * abs($found_neg)) ) ) {
					$found = $found_neg;
				} else {
					$found = $found_pos;
				}
			}
			else if ( !empty($found_pos) ) {
				$found = $found_pos;
			}
			else if ( !empty($found_neg) ) {
				$found = $found_neg;
			}
			if ( empty($found) ) return false;

			$found_size = array_search( $found, $diff );
			if ( empty($found_size) ) return false;
			return $image_sizes["$found_size"];
		}

		// you can: from php 4 use create_function; from php 5.3 use anonymous function
		private function _positive( $v ) {
			return $v >= 0;
		}
		private function _negative( $v ) {
			return $v < 0;
		}


        /**
         * Product Metas
         */
		public function set_product_meta_options( $retProd, $post_id, $is_variation=true )
		{
			if ( $is_variation == false ) {
				$tab_data = array();
				$tab_data[] = array(
					'id' => 'amzAff-customer-review',
					'content' => '<iframe src="' . ( isset($retProd['CustomerReviewsURL']) ? urldecode($retProd['CustomerReviewsURL']) : '' ) . '" width="100%" height="450" frameborder="0"></iframe>'
				);	
			}

			// update the metapost
			if ( isset($retProd['SKU']) )update_post_meta($post_id, '_sku', $retProd['SKU']);
			update_post_meta($post_id, '_amzASIN', $retProd['ASIN']);
			update_post_meta($post_id, '_visibility', 'visible');
			update_post_meta($post_id, '_downloadable', 'no');
			update_post_meta($post_id, '_virtual', 'no');
			update_post_meta($post_id, '_stock_status', 'instock');
			update_post_meta($post_id, '_backorders', 'no');
			update_post_meta($post_id, '_manage_stock', 'no');
			update_post_meta($post_id, '_amzaff_country', $retProd['country']); // added by jimmy /2017-02-16
			update_post_meta($post_id, '_product_url', home_url('/?redirectAmzASIN=' . $retProd['ASIN'] ));
			if ( isset($retProd['SalesRank']) ) update_post_meta($post_id, '_sales_rank', $retProd['SalesRank']);

			// product is imported using aa-team demo keys
			if ( $is_variation == false ) {
				if ( $this->the_plugin->do_remote_amazon_request() ) {
					update_post_meta($post_id, '_amzaff_aateam_keys', 1);
				}
			}
			
			if ( $is_variation == false ) {
				update_post_meta($post_id, '_product_version', $this->the_plugin->get_woocommerce_version()); // 2015, october 28 - attributes bug repaired!

				update_option('_transient_wc_product_type_' . $post_id, 'external');
				wp_set_object_terms( $post_id, 'external', 'product_type' );
				if( isset($retProd['CustomerReviewsURL']) && @trim($retProd['CustomerReviewsURL']) != "" ) 
					update_post_meta( $post_id, 'amzaff_woo_product_tabs', $tab_data );
			}
		}


		/**
		 * Assets download methods
		 */
		public function get_asset_by_id( $asset_id, $inprogress=false, $include_err=false, $include_invalid_post=false ) {
			require( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '/modules/assets_download/init.php' );
			$WooZoneLiteAssetDownloadCron = new WooZoneLiteAssetDownload();
			
			return $WooZoneLiteAssetDownloadCron->get_asset_by_id( $asset_id, $inprogress, $include_err, $include_invalid_post );
		}
		
		public function get_asset_by_postid( $nb_dw, $post_id, $include_variations, $inprogress=false, $include_err=false, $include_invalid_post=false ) {
			require( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '/modules/assets_download/init.php' );
			$WooZoneLiteAssetDownloadCron = new WooZoneLiteAssetDownload();
			
			$ret = $WooZoneLiteAssetDownloadCron->get_asset_by_postid( $nb_dw, $post_id, $include_variations, $inprogress, $include_err, $include_invalid_post );
            return $ret;
		}

		public function get_asset_multiple( $nb_dw='all', $inprogress=false, $include_err=false, $include_invalid_post=false ) {
			require( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '/modules/assets_download/init.php' );
			$WooZoneLiteAssetDownloadCron = new WooZoneLiteAssetDownload();
			
			return $WooZoneLiteAssetDownloadCron->get_asset_multiple( $nb_dw, $inprogress, $include_err, $include_invalid_post );
		}
		
		
		/**
		 * Category Slug clean duplicate & Other Bug Fixes
		 */
		public function category_slug_clean_all( $retType = 'die' ) {
			global $wpdb;
			
			$q = "SELECT 
 a.term_id, a.name, a.slug, b.parent, b.count
 FROM {$wpdb->terms} AS a
 LEFT JOIN {$wpdb->term_taxonomy} AS b ON a.term_id = b.term_id
 WHERE 1=1 AND b.taxonomy = 'product_cat'
;";
			$res = $wpdb->get_results( $q );
			if ( !$res || !is_array($res) ) {
				$ret['status'] = 'valid';
				$ret['msg_html'] = __('could not retrieve category slugs!', $this->the_plugin->localizationName);
				if ( $retType == 'die' ) die(json_encode($ret));
				else return $ret;
			}
			
			$upd = 0;
			foreach ($res as $key => $value) {
				$term_id = $value->term_id;
				$name = $value->name;
				$slug = $value->slug;

				$__arr = explode( "-" , $slug );
				$__arr = array_unique( $__arr );
				$slug = implode( "-" , $__arr );

				// execution/ update
				$q_upd = "UPDATE {$wpdb->terms} AS a SET a.slug = '%s' 
 WHERE 1=1 AND a.term_id = %s;";
 				$q_upd = sprintf( $q_upd, $slug, $term_id );
				$res_upd = $wpdb->query( $q_upd );

				if ( !empty($res_upd) ) $upd++;
			}
			
			$ret['status'] = 'valid';
			$ret['msg_html'] = $upd . __(' category slugs updated!', $this->the_plugin->localizationName);
			if ( $retType == 'die' ) die(json_encode($ret));
			else return $ret;
		}
		
		public function clean_orphaned_amz_meta_all( $retType = 'die' ) {
			global $wpdb;
			
			$ret = array();
			
			//DELETE a FROM wp_postmeta AS a LEFT OUTER JOIN wp_posts AS b ON a.post_id=b.ID WHERE a.meta_key='_amzASIN' AND (b.ID IS NULL OR b.post_type NOT IN ('product', 'product_variation'));
			
			//$get_amzASINS = $wpdb->get_results("SELECT a.meta_id, a.post_id FROM ". $wpdb->postmeta ." AS a LEFT OUTER JOIN ". $wpdb->posts ." AS b ON a.post_id=b.ID WHERE a.meta_key='_amzASIN' AND b.ID IS NULL");
			$get_amzASINS = $wpdb->get_results("SELECT a.meta_id, a.post_id FROM ". $wpdb->postmeta ." AS a LEFT OUTER JOIN ". $wpdb->posts ." AS b ON a.post_id=b.ID WHERE a.meta_key='_amzASIN' AND (b.ID IS NULL OR b.post_type NOT IN ('product', 'product_variation'))");
			// @2015, october 29 future update/bug fix: a.meta_key='_amzASIN' should be replaced with something like a.meta_key regexp '^(_amzASIN|_amzaff_)'
			
			$deleteMetaASINS = array();
			foreach ($get_amzASINS as $meta_id) {
				$deleteMetaASINS[] = $meta_id->meta_id;
			}
			if( count($deleteMetaASINS) > 0 ) {
				$deleteInvalidAmzMeta = $wpdb->query("DELETE FROM ".$wpdb->postmeta." WHERE meta_id IN (".(implode(',', $deleteMetaASINS)).")");
			}
			
			if( count($deleteMetaASINS) > 0 && $deleteInvalidAmzMeta > 0 ) {
				$ret['status'] = 'valid';
				$ret['msg_html'] = $deleteInvalidAmzMeta . ' orphaned amz meta cleared.';
			}elseif( count($deleteMetaASINS) == 0 ) {
				$ret['status'] = 'valid';
				$ret['msg_html'] = 'No orphaned amz meta to clean.';
			}else{
				$ret['status'] = 'invalid';
				$ret['msg_html'] = 'Error clearing orphaned amz meta.';
			}
			  
			if ( $retType == 'die' ) die(json_encode($ret));
			else return $ret;
		}

        public function clean_orphaned_prod_assets_all( $retType = 'die' ) {
            global $wpdb;
            
            $ret = array(
                'status'        => 'invalid',
                'msg_html'      => 'found and deleted: %s orphaned products, %s assets associated to orphaned products.'
            );
            
            $tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products', 'posts' => $wpdb->prefix . 'posts');
            
            //SELECT COUNT(a.post_id) FROM wp_amz_products AS a LEFT JOIN wp_posts AS b ON a.post_id = b.ID WHERE 1=1 AND ISNULL(b.ID);
            $nb_products = (int) $wpdb->get_var("SELECT COUNT(a.post_id) as nb FROM ". $tables['products'] ." AS a LEFT JOIN ". $wpdb->posts ." AS b ON a.post_id = b.ID WHERE 1=1 AND ISNULL(b.ID);");
            
            //SELECT COUNT(a.post_id) FROM wp_amz_assets AS a LEFT JOIN wp_amz_products AS b ON a.post_id = b.post_id WHERE 1=1 AND ISNULL(b.post_id);
            $nb_assets = (int) $wpdb->get_var("SELECT COUNT(a.post_id) as nb FROM ". $tables['assets'] ." AS a LEFT JOIN ". $tables['products'] ." AS b ON a.post_id = b.post_id WHERE 1=1 AND ISNULL(b.post_id);");
            
            $ret['status'] = 'valid';
            $ret['msg_html'] = sprintf( $ret['msg_html'], (int) $nb_products, (int) $nb_assets);
 
            if ( $nb_products > 0 ) {
                //delete a FROM wp_amz_products AS a LEFT JOIN wp_posts AS b ON a.post_id = b.ID WHERE 1=1 AND ISNULL(b.ID);
                $delete_products = $wpdb->query("delete a FROM " . $tables['products'] . " as a LEFT JOIN " . $wpdb->posts . " AS b ON a.post_id = b.ID WHERE 1=1 AND ISNULL(b.ID);");
            }
            if ( $nb_assets > 0 ) {
                //delete a FROM wp_amz_assets AS a LEFT JOIN wp_amz_products AS b ON a.post_id = b.post_id WHERE 1=1 AND ISNULL(b.post_id);
                $delete_assets = $wpdb->query("delete a FROM " . $tables['assets'] . " as a LEFT JOIN " . $tables['products'] . " AS b ON a.post_id = b.post_id WHERE 1=1 AND ISNULL(b.post_id);");
            }
            //var_dump('<pre>', $delete_products, $delete_assets, '</pre>'); die('debug...'); 
            
            if ( $retType == 'die' ) die(json_encode($ret));
            else return $ret;
        }

        public function clean_orphaned_prod_assets_all_wp( $retType = 'die' ) {
            global $wpdb;
            
            $ret = array(
                'status'        => 'invalid',
                'msg_html'      => '<div><span style="display: inline-block; width: 25rem;">orphaned posts</span>:<span style="display: inline-block; margin-left: 1.5rem;"><span>found = %s</span> | <span style="font-weight: bold; color: red;">deleted = %s</span></span></div>      <div><span style="display: inline-block; width: 25rem;">postmeta associated to orphaned posts</span>:<span style="display: inline-block; margin-left: 1.5rem;"><span style="font-weight: bold; color: red;">deleted = %s</span></span></div>'
            );

			$sql_chunk_limit = 100;            

            $tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products', 'posts' => $wpdb->prefix . 'posts', 'postmeta' => $wpdb->prefix . 'postmeta');

			$amzimgpath = $this->the_plugin->get_amazon_images_path();

			$ids = array(); $nbprods = 0; $nbprods_del = 0; $nbmetas_del = 0;

			/*
			select
			# 	count(p.ID) as nbfound
				p.*
			#	p.ID, p.guid, p.post_title
			 from wp_posts as p
			 	left join wp_posts as p2 on p.post_parent = p2.ID
			 	left join wp_postmeta as pm on ( p.ID = pm.post_id and pm.meta_key = '_wp_attached_file' )
			 where 1=1
				and isnull(p2.ID)
			 	and p.post_type = 'attachment'
			 	and p.post_mime_type regexp 'image'
				and (
					#\/product\/|attachment_id
					p.guid regexp '\/product\/'
					or
					pm.meta_value regexp 'images-amazon.'
				)
			 order by p.ID ASC;
			*/
			$sql = "
			select
				p.ID
			from " . $wpdb->posts . " as p
			 	left join " . $wpdb->posts . " as p2 on p.post_parent = p2.ID
			 	left join " . $wpdb->postmeta . " as pm on ( p.ID = pm.post_id and pm.meta_key = '_wp_attached_file' )
			where 1=1
				and isnull(p2.ID)
			 	and p.post_type = 'attachment'
			 	and p.post_mime_type regexp 'image'
				and (
					p.guid regexp '\/product\/'
					or
					pm.meta_value regexp '" . $amzimgpath . "' 
				)
			order by p.ID ASC;
			";
			//var_dump('<pre>',$sql,'</pre>');
            $res = $wpdb->get_results( $sql, OBJECT_K );
			if ( $res && is_array($res) ) {
				$ids = array_keys( $res );
				$nbprods = count( $ids );
			}
     
            if ( $nbprods > 0 ) {
            	// clean posts from wp_posts
				$sql_del = "
				delete
					p
				from " . $wpdb->posts . " as p
				 	left join " . $wpdb->posts . " as p2 on p.post_parent = p2.ID
				 	left join " . $wpdb->postmeta . " as pm on ( p.ID = pm.post_id and pm.meta_key = '_wp_attached_file' )
				where 1=1
					and isnull(p2.ID)
				 	and p.post_type = 'attachment'
				 	and p.post_mime_type regexp 'image'
					and (
						p.guid regexp '\/product\/'
						or
						pm.meta_value regexp '" . $amzimgpath . "' 
					);
				";
				//var_dump('<pre>',$sql_del,'</pre>');
                $nbprods_del = (int) $wpdb->query( $sql_del );

                // clean metas from wp_postmeta
                $nbmetas_del = array();
	            foreach (array_chunk($ids, $sql_chunk_limit, true) as $current) {
	
	                $currentP = implode(',', array_map(array($this->the_plugin, 'prepareForInList'), $current));
	
					if (1) {
		                $sql_ = "delete pm from " . $wpdb->postmeta . " as pm where 1=1 and pm.post_id IN ($currentP);";
						//var_dump('<pre>',$sql_,'</pre>');
		                $res_ = $wpdb->query( $sql_ );
		                //$res_ = rand(10, 50); //debugging purpose...
						$nbmetas_del[] = (int) $res_;
					}
				}
				$nbmetas_del = (int) array_sum( $nbmetas_del );
            }
            //var_dump('<pre>', $nbprods, $nbprods_del, $nbmetas_del, '</pre>'); die('debug...');

            $ret['status'] = 'valid';
            $ret['msg_html'] = sprintf( $ret['msg_html'], $nbprods, $nbprods_del, $nbmetas_del );
            
            if ( $retType == 'die' ) die(json_encode($ret));
            else return $ret;
        }

		public function fix_product_attributes_all( $retType = 'die' ) {
			global $wpdb;
			
			$ret = array(
				'status'		=> 'valid',
				'msg_html'		=> array(), 
			);
			
			$themetas = array('_product_attributes', '_product_version');
			foreach ($themetas as $themeta) { // foreach metas

				$q = "select * from $wpdb->postmeta as pm where 1=1 and meta_key regexp '$themeta' and post_id in ( select p.ID from $wpdb->posts as p left join $wpdb->postmeta as pm2 on p.ID = pm2.post_id where 1=1 and pm2.meta_key='_amzASIN' and !isnull(p.ID) and p.post_type in ('product') );";
				$res = $wpdb->get_results( $q );
				if ( !$res || !is_array($res) ) {
					//$ret['status'] = 'valid';
					if ( !is_array($res) ) {
						$ret['msg_html'][] = sprintf( __('%s fix: no products needed attributes fixing!', $this->the_plugin->localizationName), $themeta );
					} else {
						$ret['msg_html'][] = sprintf( __('%s fix: cannot retrieve products for attributes fixing!', $this->the_plugin->localizationName), $themeta );
					}
					//if ( $retType == 'die' ) die(json_encode($ret));
					//else return $ret;
				}
				else {
					$upd = 0;
					foreach ($res as $key => $value) {
						if ( '_product_attributes' == $themeta ) {
							$__ = maybe_unserialize($value->meta_value);
							$__ = maybe_unserialize($__);
							
							// execution/ update
							//$__ = serialize($__);
							//$q_upd = "UPDATE $wpdb->postmeta AS pm SET pm.meta_value = '%s' WHERE 1=1 AND pm.meta_id = %s;";
			 				//$q_upd = sprintf( $q_upd, $__, $value->meta_id );
							//$res_upd = $wpdb->query( $q_upd );
							
							$__orig = $__;
							if ( !empty($__) && is_array($__) ) {
								foreach ($__ as $k => $v) {
									if ( isset($v['is_visible'], $v['is_variation'], $v['is_taxonomy']) ) {
										if ( ($v['is_visible'] == '1') && ($v['is_variation'] == '1') && ($v['is_taxonomy'] == '1') ) {
											$__["$k"]['value'] = '';
										}
									}
								}
							}
			  
							$res_upd = update_post_meta($value->post_id, $themeta, $__);
			  				add_post_meta($value->post_id, '_amzaff_orig'.$themeta, $__orig, true);
							if ( !empty($res_upd) ) $upd++;
						}
						else {
							$__ = $this->the_plugin->force_woocommerce_product_version($value->meta_value, '2.4.0', '9.9.9');
							
							$res_upd = update_post_meta($value->post_id, $themeta, $__);
							if ( !empty($res_upd) ) $upd++;
						}
					}
					
					//$ret['status'] = 'valid';
					$ret['msg_html'][] = sprintf( __('%s fix: %s products needed attributes fixing!', $this->the_plugin->localizationName), $themeta, $upd );
				}
			} // end foreach themetas

			$ret['msg_html'] = implode('<br />', $ret['msg_html']);
			if ( $retType == 'die' ) die(json_encode($ret));
			else return $ret;
		}

		public function fix_node_childrens( $retType = 'die' ) {
			global $wpdb;
			
			$action = isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : '';
   
			$ret = array(
				'status'		=> 'valid',
				'msg_html'		=> array(), 
			);
			
			if ( 'fix_node_childrens' == $action ) {
				$sql = "DELETE FROM $wpdb->options WHERE option_name LIKE 'WooZoneLite_node_children_%';";  
				$query = $wpdb->query($sql);
				
				$ret['msg_html'][] = 'Operation executed successfully.';
			}
			
			$ret['msg_html'] = implode('<br />', $ret['msg_html']);
			if ( $retType == 'die' ) die(json_encode($ret));
			else return $ret;
		}

		public function fix_issues( $retType = 'die' ) {
			global $wpdb;
   
			$action = isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : '';
   
			$ret = array(
				'status'		=> 'valid',
				'msg_html'		=> array(), 
			);
			
			if ( 'fix_issue_request_amazon' == $action ) {
				delete_option('WooZoneLite_insane_last_reports');
				$ret['msg_html'][] = 'Operation executed successfully.';
			}

			if ( 'reset_products_stats' == $action ) {
				$tposts = $wpdb->posts;
				$tpostmeta = $wpdb->postmeta;

				$queries = array(
					"delete from $tpostmeta where 1=1 and meta_key in ('_amzaff_hits', '_amzaff_addtocart', '_amzaff_redirect_to_amazon');",
					"delete from $tpostmeta where 1=1 and meta_key in ('_amzaff_hits_prev', '_amzaff_addtocart_prev', '_amzaff_redirect_to_amazon_prev');",

				);
				$stat = 0;
				foreach ($queries as $query) {
					$stat += $wpdb->query( $query );
				}

				$ret['msg_html'][] = sprintf(
					'Deleted: %s postmetas.',
					$stat
				);
			}
			
			if ( 'unblock_cron' == $action ) {
				$cron_class = new WooZoneLiteCronjobs($this->the_plugin);
				
				$unblock = $cron_class->run('unblock_crons');
				
				$ret['msg_html'][] = is_array($unblock) ? $unblock['status'] : $unblock;
			}
			
			if ( 'sync_restore_status' == $action ) {
				$what = isset($_REQUEST['what']) ? $_REQUEST['what'] : '';
				$opStat = $this->issue_sync_restore( $what );

				$html = array();				
				if ( empty($what) || 'verify' == $what ) {
					$html[] = sprintf(
						'Found: %s products, %s product variations.',
						$opStat['prods']['parents'],
						$opStat['prods']['variations']
					);
					
					$html[] = '&nbsp;&nbsp;';
					$html[] = '<input type="button" class="WooZoneLite-form-button-small WooZoneLite-form-button-primary" style="height: 3.8rem; background-color: #2980b9;" id="fix_issue_sync-fix_now_doit" value="' . ( __('DO IT', $WooZoneLite->localizationName) ) . '">';
					$html[] = '&nbsp;&nbsp;';
					$html[] = '<input type="button" class="WooZoneLite-form-button-small WooZoneLite-form-button-primary" style="height: 3.8rem; background-color: #c0392b;" id="fix_issue_sync-fix_now_cancel" value="' . ( __('Cancel', $WooZoneLite->localizationName) ) . '">';
				}
				else {
					$html[] = sprintf(
						'Updated: %s products, %s product variations.',
						$opStat['prods']['parents'],
						$opStat['prods']['variations']
					);
				}
				
				$ret['msg_html'][] = implode('', $html);
			}

			if ( 'options_prefix_change' == $action ) {
				$what = isset($_REQUEST['what']) ? $_REQUEST['what'] : '';

				// update cronjobs prefix
				$this->the_plugin->update_cronjobs();
   
				// update options prefix
				if ( 'use_new' == $what ) {
					$opStat = $this->the_plugin->update_options_prefix( 'use_new' );
				}
				else { // use_old
					$opStat = $this->the_plugin->update_options_prefix( 'use_old' );
				}
				$ret['msg_html'][] = $opStat['msg'];
			}

			$ret['msg_html'] = implode('<br />', $ret['msg_html']);
			if ( $retType == 'die' ) die(json_encode($ret));
			else return $ret;
		}

		public function issue_sync_restore( $what='' ) {
			global $wpdb;
			
			$ret = array(
				'status'		=> 'invalid',
				'prods'			=> array(
					'parents'		=> 0,
					'variations'	=> 0,
				),
			);
			
			$do_verify = empty($what) || 'verify' == $what ? true : false;
			if ( !$do_verify ) {
				$post_status = isset($_REQUEST['post_status']) ? $_REQUEST['post_status'] : 'publish';
			}
			
			$sql_chunk_limit = 1000;
			$tposts = $wpdb->posts;
			$tpostmeta = $wpdb->postmeta;
			
			// get parent products (from trash)
			$sql = "select p.ID from $tposts as p left join $tpostmeta as pm on p.ID = pm.post_id where 1=1 and pm.meta_key='_amzASIN' and p.post_type = 'product' and p.post_status = 'trash' and !isnull(pm.post_id);";
			$res = $wpdb->get_results( $sql, OBJECT_K  );
			$ids = array_keys( $res );
			
            // get product variations (only childs, no parents) (from trash)
            $ids_childs = array();
            foreach (array_chunk($ids, $sql_chunk_limit, true) as $current) {

                $currentP = implode(',', array_map(array($this->the_plugin, 'prepareForInList'), $current));

				if ( $do_verify ) {
	                $sql_ = "select p.ID from $tposts as p left join $tpostmeta as pm on p.ID = pm.post_id where 1=1 and pm.meta_key='_amzASIN' and p.post_type = 'product_variation' and p.post_status = 'trash' and !isnull(pm.post_id) and p.post_parent > 0 and p.post_parent IN ($currentP);";
	                $res_ = $wpdb->get_results( $sql_, OBJECT_K );
	                $ids_childs = $ids_childs + $res_;
                }
				else {
	                $sql_ = "update $tposts as p left join $tpostmeta as pm on p.ID = pm.post_id set p.post_status = '$post_status' where 1=1 and pm.meta_key='_amzASIN' and p.post_type = 'product_variation' and p.post_status = 'trash' and !isnull(pm.post_id) and p.post_parent > 0 and p.post_parent IN ($currentP);";
	                $res_ = $wpdb->query( $sql_ );
					$ids_childs[] = (int) $res_;
				}
			}
			
			if ( $do_verify ) {
				$ids_childs = array_keys( $ids_childs );
			}
			else {
				$ids_childs = (int) array_sum( $ids_childs );
			}
			
			if ( !$do_verify ) {
				$sql = "update $tposts as p left join $tpostmeta as pm on p.ID = pm.post_id set p.post_status = '$post_status' where 1=1 and pm.meta_key='_amzASIN' and p.post_type = 'product' and p.post_status = 'trash' and !isnull(pm.post_id);";
				$res = $wpdb->query( $sql );
				$ids = (int) $res;
			}
            //var_dump('<pre>', $ids, $ids_childs, '</pre>'); die('debug...');
			
			return array_merge($ret, array(
				'prods' => array(
					'parents'		=> $do_verify ? count($ids) : $ids,
					'variations'	=> $do_verify ? count($ids_childs) : $ids_childs,
				)
			));
		}

		// new version: from 2017-04-28
		public function delete_zeropriced_products_all( $retType = 'die' ) {
			global $wpdb;
			
			@ini_set('memory_limit', '512M');
			@ini_set('max_execution_time', 0);
			@set_time_limit(0); // infinte

			$ret = array(
				'status'			=> 'invalid',
				'html'			=> '',
				'nb_total'		=> 0,
				'nb_done'		=> 0,
				'nb_remained' => 0,
			);

			$query = "
select p.ID from {$wpdb->posts} as p
	left join {$wpdb->postmeta} as pm on p.ID = pm.post_id
	left join {$wpdb->postmeta} as pm2 on p.ID = pm2.post_id
	left join {$wpdb->postmeta} as pm3 on p.ID = pm3.post_id
	where 1=1
		and p.post_type = 'product' and p.post_status != 'trash'
		and ( pm.meta_key = '_amzASIN' and pm.meta_value != '' )
		and ( pm2.meta_key = '_regular_price' and pm2.meta_value = '' )
		and ( pm3.meta_key = '_price' and pm3.meta_value = '' )
	order by p.ID asc
;
			";
			$res = $wpdb->get_results( $query, OBJECT_K );
			//var_dump('<pre>', $res, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$ret['nb_total'] = count($res);

			$cc = 0;
			foreach ($res as $post_id => $val) {
				wp_trash_post( $post_id );
				$cc++;
				if ( $cc >= 10 ) break 1;
			}

			$ret['nb_done'] = $cc;
			$ret['nb_remained'] = (int) ( $ret['nb_total'] - $ret['nb_done'] );
			$ret['nb_remained'] = $ret['nb_remained'] >= 0 ? $ret['nb_remained'] : 0;

			$ret['status'] = 'valid';
			if( ! $cc ) {
				$ret['msg_html'] = 'No zero priced posts found.';
			} else {
				$ret['msg_html'] = sprintf( '<strong>%s</strong> posts moved to trash! <strong>%s</strong> posts remained to be moved to trash.', $ret['nb_done'], $ret['nb_remained'] );
			}

			if ( $retType == 'die' ) die(json_encode($ret));
			else return $ret;
		}

		// old version
		public function __delete_zeropriced_products_all( $retType = 'die' ) {
			$ret = array();
			$args = array();
			$args['post_type'] = 'product';

			$args['meta_key'] = '_amzASIN';
			$args['meta_value'] = '';
			$args['meta_compare'] = '!=';

			// show all posts
			//$args['fields'] = 'ids';
			$args['posts_per_page'] = '-1';

			$loop = new WP_Query( $args );
			$cc = 0;
			$ret = array();
			while ( $loop->have_posts() ) : $loop->the_post();
				global $post;

				$post = (int) $post->ID;

				$sale_price = get_post_meta( $post, '_sale_price', true );
				$regular_price = get_post_meta( $post, '_regular_price', true );    
				$price = get_post_meta( $post, '_price', true );
			
				if( $regular_price == '' && $price == '' ){
					$cc++;
					//if regular price is not set or it`s zero, put the post into trash 
					wp_trash_post( $post );
				}
			endwhile;

			$ret['status'] = 'valid';
			if( $cc == 0 ) {
				$ret['msg_html'] = 'No zero priced posts found.';
			} else {
				$ret['msg_html'] = $cc.' posts moved to trash!';
			}

			if ( $retType == 'die' ) die(json_encode($ret));
			else return $ret;
		}


		/**
		 * Attributes clean duplicate
		 */
		public function attrclean_getDuplicateList() {
			global $wpdb;

			// $q = "SELECT COUNT(a.term_id) AS nb, a.name, a.slug FROM {$wpdb->terms} AS a WHERE 1=1 GROUP BY a.name HAVING nb > 1;";
			$q = "SELECT COUNT(a.term_id) AS nb, a.name, a.slug, b.term_taxonomy_id, b.taxonomy, b.count FROM {$wpdb->terms} AS a
 LEFT JOIN {$wpdb->term_taxonomy} AS b ON a.term_id = b.term_id
 WHERE 1=1 AND b.taxonomy REGEXP '^pa_' GROUP BY a.name, b.taxonomy HAVING nb > 1
 ORDER BY a.name ASC
;";
			$res = $wpdb->get_results( $q );
			if ( !$res || !is_array($res) ) return false;
			
			$ret = array();
			foreach ($res as $key => $value) {
				$name = $value->name;
				$taxonomy = $value->taxonomy;
				$ret["$name@@$taxonomy"] = $value;
			}
			return $ret;
		}
		
		public function attrclean_getTermPerDuplicate( $term_name, $taxonomy ) {
			global $wpdb;
			
			$q = "SELECT a.term_id, a.name, a.slug, b.term_taxonomy_id, b.taxonomy, b.count FROM {$wpdb->terms} AS a
 LEFT JOIN {$wpdb->term_taxonomy} AS b ON a.term_id = b.term_id
 WHERE 1=1 AND a.name=%s AND b.taxonomy=%s ORDER BY a.slug ASC;";
 			$q = $wpdb->prepare( $q, $term_name, $taxonomy );
			$res = $wpdb->get_results( $q );
			if ( !$res || !is_array($res) ) return false;
			
			$ret = array();
			foreach ($res as $key => $value) {
				$ret[$value->term_taxonomy_id] = $value;
			}
			return $ret;
		}
		
		public function attrclean_removeDuplicate( $first_term, $terms=array(), $debug = false ) {
			if ( empty($terms) || !is_array($terms) ) return false;

			$term_id = array();
			$term_taxonomy_id = array();
			foreach ($terms as $k => $v) {
				$term_id[] = $v->term_id;
				$term_taxonomy_id[] = $v->term_taxonomy_id;
				$taxonomy = $v->taxonomy;
			}
			// var_dump('<pre>',$first_term, $term_id, $term_taxonomy_id, $taxonomy,'</pre>');  

			$ret = array();
			$ret['term_relationships'] = $this->attrclean_remove_term_relationships( $first_term, $term_taxonomy_id, $debug );
			$ret['terms'] = $this->attrclean_remove_terms( $term_id, $debug );
			$ret['term_taxonomy'] = $this->attrclean_remove_term_taxonomy( $term_taxonomy_id, $taxonomy, $debug );
			// var_dump('<pre>',$ret,'</pre>');  
			return $ret;
		}
		
		private function attrclean_remove_term_relationships( $first_term, $term_taxonomy_id, $debug = false ) {
			global $wpdb;
			
			$idList = (is_array($term_taxonomy_id) && count($term_taxonomy_id)>0 ? implode(', ', array_map(array($this->the_plugin, 'prepareForInList'), $term_taxonomy_id)) : 0);

			if ( $debug ) {
			$q = "SELECT a.object_id, a.term_taxonomy_id FROM {$wpdb->term_relationships} AS a
 WHERE 1=1 AND a.term_taxonomy_id IN (%s) ORDER BY a.object_id ASC, a.term_taxonomy_id;";
 			$q = sprintf( $q, $idList );
			$res = $wpdb->get_results( $q );
			if ( !$res || !is_array($res) ) return false;
			
			$ret = array();
			$ret[] = 'object_id, term_taxonomy_id';
			foreach ($res as $key => $value) {
				$term_taxonomy_id = $value->term_taxonomy_id;
				$ret["$term_taxonomy_id"] = $value;
			}
			return $ret;
			}
			
			// execution/ update
			$q = "UPDATE {$wpdb->term_relationships} AS a SET a.term_taxonomy_id = '%s' 
 WHERE 1=1 AND a.term_taxonomy_id IN (%s);";
 			$q = sprintf( $q, $first_term, $idList );
			$res = $wpdb->query( $q );
			$ret = $res;
			return $ret;
		}
		
		private function attrclean_remove_terms( $term_id, $debug = false ) {
			global $wpdb;
			
			$idList = (is_array($term_id) && count($term_id)>0 ? implode(', ', array_map(array($this->the_plugin, 'prepareForInList'), $term_id)) : 0);

			if ( $debug ) {
			$q = "SELECT a.term_id, a.name FROM {$wpdb->terms} AS a
 WHERE 1=1 AND a.term_id IN (%s) ORDER BY a.name ASC;";
 			$q = sprintf( $q, $idList );
			$res = $wpdb->get_results( $q );
			if ( !$res || !is_array($res) ) return false;
			
			$ret = array();
			$ret[] = 'term_id, name';
			foreach ($res as $key => $value) {
				$term_id = $value->term_id;
				$ret["$term_id"] = $value;
			}
			return $ret;
			}
			
			// execution/ update
			$q = "DELETE FROM a USING {$wpdb->terms} as a WHERE 1=1 AND a.term_id IN (%s);";
 			$q = sprintf( $q, $idList );
			$res = $wpdb->query( $q );
			$ret = $res;
			return $ret;
		}
		
		private function attrclean_remove_term_taxonomy( $term_taxonomy_id, $taxonomy, $debug = false ) {
			global $wpdb;
			
			$idList = (is_array($term_taxonomy_id) && count($term_taxonomy_id)>0 ? implode(', ', array_map(array($this->the_plugin, 'prepareForInList'), $term_taxonomy_id)) : 0);

			if ( $debug ) {
			$q = "SELECT a.term_id, a.taxonomy, a.term_taxonomy_id FROM {$wpdb->term_taxonomy} AS a
 WHERE 1=1 AND a.term_taxonomy_id IN (%s) AND a.taxonomy = '%s' ORDER BY a.term_taxonomy_id ASC;";
 			$q = sprintf( $q, $idList, esc_sql($taxonomy) );
			$res = $wpdb->get_results( $q );
			if ( !$res || !is_array($res) ) return false;

			$ret = array();
			$ret[] = 'term_id, taxonomy, term_taxonomy_id';
			foreach ($res as $key => $value) {
				$term_taxonomy_id = $value->term_taxonomy_id;
				$ret["$term_taxonomy_id"] = $value;
			}
			return $ret;
			}

			// execution/ update
			$q = "DELETE FROM a USING {$wpdb->term_taxonomy} as a WHERE 1=1 AND a.term_taxonomy_id IN (%s) AND a.taxonomy = '%s';";
 			$q = sprintf( $q, $idList, $taxonomy );
			$res = $wpdb->query( $q );
			$ret = $res;
			return $ret;
		}

		public function attrclean_clean_all( $retType = 'die' ) {
			// :: get duplicates list
			$duplicates = $this->attrclean_getDuplicateList();
  
			if ( empty($duplicates) || !is_array($duplicates) ) {
				$ret['status'] = 'valid';
				$ret['msg_html'] = __('no duplicate terms found!', $this->the_plugin->localizationName);
				if ( $retType == 'die' ) die(json_encode($ret));
				else return $ret;
			}
			// html message
			$__duplicates = array();
			$__duplicates[] = '0 : name, slug, term_taxonomy_id, taxonomy, count';
			foreach ($duplicates as $key => $value) {
				$__duplicates[] = $value->name . ' : ' . implode(', ', (array) $value);
			}
			$ret['status'] = 'valid';
			$ret['msg_html'] = implode('<br />', $__duplicates);
			// if ( $retType == 'die' ) die(json_encode($ret));
			// else return $ret;

			// :: get terms per duplicate
			$__removeStat = array();
			$__terms = array();
			$__terms[] = '0 : term_id, name, slug, term_taxonomy_id, taxonomy, count';
			foreach ($duplicates as $key => $value) {
				$terms = $this->attrclean_getTermPerDuplicate( $value->name, $value->taxonomy );
				if ( empty($terms) || !is_array($terms) || count($terms) < 2 ) continue 1;

				$first_term = array_shift($terms);

				// html message
				foreach ($terms as $k => $v) {
					$__terms[] = $key . ' : ' . implode(', ', (array) $v);
				}

				// :: remove duplicate term
				$removeStat = $this->attrclean_removeDuplicate($first_term->term_id, $terms, false);
				
				// html message
				$__removeStat[] = '-------------------------------------- ' . $key;
				$__removeStat[] = '---- term kept';
				$__removeStat[] = 'term_id, term_taxonomy_id';
				$__removeStat[] = $first_term->term_id . ', ' . $first_term->term_taxonomy_id;
				foreach ($removeStat as $k => $v) {
					$__removeStat[] = '---- ' . $k;
					if ( !empty($v) && is_array($v) ) {
						foreach ($v as $k2 => $v2) {
							$__removeStat[] = implode(', ', (array) $v2);
						}
					} else if ( !is_array($v) ) {
						$__removeStat[] = (int) $v;
					} else {
						$__removeStat[] = 'empty!';
					}
				}
			}

			$ret['status'] = 'valid';
			$ret['msg_html'] = implode('<br />', $__removeStat);
			if ( $retType == 'die' ) die(json_encode($ret));
			else return $ret;
		}

		public function attrclean_splitTitle($title) {
			$extra = array(
				'ASIN' => 'ASIN',
				'CEROAgeRating' => 'CERO Age Rating',
				'EAN' => 'EAN',
				'EANList' => 'EAN List',
				'EANListElement' => 'EAN List Element',
				'EISBN' => 'EISBN',
				'ESRBAgeRating' => 'ESRB Age Rating',
				'HMAC' => 'HMAC',
				'IFrameURL' => 'IFrame URL',
				'ISBN' => 'ISBN',
				'MPN' => 'MPN',
				'ParentASIN' => 'Parent ASIN',
				'PurchaseURL' => 'Purchase URL',
				'SKU' => 'SKU',
				'UPC' => 'UPC',
				'UPCList' => 'UPC List',
				'UPCListElement' => 'UPC List Element',
				'URL' => 'URL',
				'URLEncodedHMAC' => 'URL Encoded HMAC',
				'WEEETaxValue' => 'WEEE Tax Value'
			);
			
			if ( in_array($title, array_keys($extra)) ) {
				return $extra["$title"];
			}
			
			preg_match_all('/((?:^|[A-Z])[a-z]+)/', $title, $matches, PREG_PATTERN_ORDER);
			return implode(' ', $matches[1]);
		}


		/**
		 * Product Price - Update november 2014
		 */
		public function productPriceSetMeta( $thisProd, $post_id='', $return=true ) {
			$ret = array();
			$o = array(
				'ItemAttributes'		=> isset($thisProd['ItemAttributes']['ListPrice']) ? array('ListPrice' => $thisProd['ItemAttributes']['ListPrice']) : array(),
				'Offers'				=> isset($thisProd['Offers']) ? $thisProd['Offers'] : array(),
				'OfferSummary'			=> isset($thisProd['OfferSummary']) ? $thisProd['OfferSummary'] : array(),
				'VariationSummary'		=> isset($thisProd['VariationSummary']) ? $thisProd['VariationSummary'] : array(),
			);
			/*
			if ( isset($o['Offers']['Offer']['Promotions']['Promotion']['Summary']) ) {
				//BenefitDescription, TermsAndConditions
				foreach (array('BenefitDescription', 'TermsAndConditions') as $key) {
					if ( isset($o['Offers']['Offer']['Promotions']['Promotion']['Summary']["$key"]) ) {
						$__tmp = $o['Offers']['Offer']['Promotions']['Promotion']['Summary']["$key"];
						$o['Offers']['Offer']['Promotions']['Promotion']['Summary']["$key"] = esc_html($__tmp);
					}
				}
			}
			*/
			update_post_meta($post_id, '_amzaff_amzRespPrice', $o);
			
			// Offers/Offer/OfferListing/IsEligibleForSuperSaverShipping
			if ( isset($o['Offers']['Offer']['OfferListing']['IsEligibleForSuperSaverShipping']) ) {
				$ret['isSuperSaverShipping'] = $o['Offers']['Offer']['OfferListing']['IsEligibleForSuperSaverShipping'] === true ? 1 : 0;
				update_post_meta($post_id, '_amzaff_isSuperSaverShipping', $ret['isSuperSaverShipping']);
			}
			
			// Offers/Offer/OfferListing/Availability
			if ( isset($o['Offers']['Offer']['OfferListing']['Availability']) ) {
				$ret['availability'] = (string) $o['Offers']['Offer']['OfferListing']['Availability'];
				update_post_meta($post_id, '_amzaff_availability', $ret['availability']);
			}
			
			return $ret;
		}

		public function productPriceSetRegularSaleMeta( $post_id, $type, $newMetas=array() ) {
			$_amzaff_price = $newMetas;
			$_amzaff_price_db = get_post_meta( $post_id, '_amzaff_'.$type.'_price', true );
			if ( !empty($_amzaff_price_db) && is_array($_amzaff_price_db) ) {
				$_amzaff_price = array_merge($_amzaff_price_db, $_amzaff_price);
			}
			update_post_meta($post_id, '_amzaff_'.$type.'_price', $_amzaff_price);
		}

		public function productPriceGetRegularSaleStatus( $post_id, $type='both' ) {
			$ret = array('regular' => 'auto', 'sale' => 'auto');
			
			foreach (array('regular', 'sale') as $priceType) {
				$meta = (array) get_post_meta( $post_id, '_amzaff_'.$priceType.'_price', true );
				if ( !empty($meta) && isset($meta["current"]) && !empty($meta["current"]) ) {
					$ret["$priceType"] = $meta["current"];
				}
			}
			if ( $type != 'both' && in_array($type, array('regular', 'sale')) ) {
				return $ret["$type"];
			}
			return $ret;
		}


		/**
		 * Seller
		 */
		public function product_has_amazon_seller( $thisProd ) {
			$multiply_factor =  ($this->amz_settings['country'] == 'co.jp') ? 1 : 0.01;
  
			//$price_setup = (isset($this->amz_settings["price_setup"]) && $this->amz_settings["price_setup"] == 'amazon_or_sellers' ? 'amazon_or_sellers' : 'only_amazon');
			$merchant_setup = (isset($this->amz_settings["merchant_setup"]) && $this->amz_settings["merchant_setup"] == 'only_amazon' ? 'only_amazon' : 'amazon_or_sellers');
			
			// request has had (MerchantId = Amazon) in order for the bellow code to work!
			if ( 'only_amazon' == $merchant_setup ) {
				if ( isset($thisProd['Offers'], $thisProd['Offers']['TotalOffers']) ) {
					$total_offers = (int) $thisProd['Offers']['TotalOffers'];

					if ( $total_offers ) {
						return true;
					} else {
						// false only when there is no offer when (MerchantId = Amazon)
						return false;
					}
				}
			}
			return true;
		}


		/**
		 * Octomber 2015 - new plugin functions
		 */
		// key: country || main_aff_id
		public function get_countries( $key='country' ) {
			$localizationName = $this->the_plugin->localizationName;
			if ( 'country' == $key ) {
				return  array(
					'com' => __('Worldwide', $localizationName),
                    'co.uk' => __('United Kingdom', $localizationName),
                    'de' => __('Germany', $localizationName),
                    'in' => __('India', $localizationName),
       
                    //'com.au' => __('Australia', $localizationName),
				);
			}
			else if ( 'main_aff_id' == $key ) {
				return  array(
					'com' => __('United States', $localizationName),
					'uk' => __('United Kingdom', $localizationName),
					'de' => __('Deutschland', $localizationName),
					'in' => __('India', $localizationName),
					//'au' => __('Australia', $localizationName),
				);
			}
			else {
				return  array(
					'com' => '<a href="https://affiliate-program.amazon.com/" target="_blank">United States</a>',
					'uk' => '<a href="https://affiliate-program.amazon.co.uk/" target="_blank">United Kingdom</a>',
					'de' => '<a href="https://partnernet.amazon.de/" target="_blank">Deutschland</a>',
					'in' => '<a href="https://affiliate-program.amazon.in/" target="_blank">India</a>',
					
					//'au' => '<a href="https://affiliate-program.amazon.com/" target="_blank">Australia</a>',
				);
			}
			return array();
		}
		
		// key: country || main_aff_id
		public function get_country_name( $country, $key='country' ) {
			$countries = $this->get_countries( $key );
			$country = isset($countries["$country"]) ? $countries["$country"] : '';
			return $country;
		}


		/**
		 * search products by pages
		 * input(pms): array(
		 * 		requestData					: array
		 * 		parameters					: array
		 * 		_optionalParameters		: array
		 * 		page								: int
		 * )
		 * return: array(
		 * 		response						: array
		 * 		status							: string ( valid | invalid )
         *         msg								: string
		 * 		code								: int
		 * 		req_link							: string
		 * )
		 */
		public function api_search_bypages( $pms=array() ) {

			$stat = $this->api_verify_request(array_merge($pms, array('what_func' => 'api_search_bypages')));
			if ( 'valid' == $stat['status'] ) {
            	return $stat;
			}

			extract($pms);
			
			// lock current amazon key
			$is_remote_keys = isset($pms['keys_id']) && !empty($pms['keys_id']) ? true : false;
			if ( $is_remote_keys ) {
				$this->keysObj->lock_current_access_key( $pms['keys_id'] );
			}
			
			try {
 
			$this->aaAmazonWS
            	->category( ( $parameters['category'] == 'AllCategories' ? 'All' : $parameters['category'] ) )
                ->page( $page )
                ->responseGroup('Large,ItemAttributes,OfferFull,Offers,Variations,Reviews,PromotionSummary,SalesRank');
     
			// set the page
            $_optionalParameters['ItemPage'] = $page;
                    
            if( isset($_optionalParameters) && count($_optionalParameters) > 0 ){
				// add optional parameter to query
                $this->aaAmazonWS
                	->optionalParameters( $_optionalParameters );
			}
            //var_dump('<pre>',$this->aaAmazonWS,'</pre>');
                
            // add the search keywords
            $response = $this->aaAmazonWS
            	->search( isset($parameters['keyword']) ? $parameters['keyword'] : '' );
			
			$req_link = $this->aaAmazonWS->get_xml_amazon_link('normal');
			//var_dump('<pre>',$req_link, $response,'</pre>'); die;
    
            //$__asinsDebug = array();
            //foreach ( $response['Items']['Item'] as $item_key => $item_val ) {
            //    $__asinsDebug[] = $item_val['ASIN'];
            //}
            //var_dump('<pre>',$__asinsDebug,'</pre>');
            
			} catch (Exception $e) {
                // Check 
                if (isset($e->faultcode)) { // error occured!
                    $msg = $e->faultcode .  ' : ' . (isset($e->faultstring) ? $e->faultstring : $e->getMessage());
                    //var_dump('<pre>',$msg,'</pre>'); die;

					// unlock current amazon key
					if ( $is_remote_keys ) {
						$this->keysObj->unlock_current_access_key( $pms['keys_id'] );
					}

					$response = array('status' => 'invalid', 'msg' => $msg, 'code' => 1, 'req_link' => $req_link);
					return array_merge(array('response' => $response), $response);
                }
            }

			$request_status = $this->is_amazon_valid_response( $response );
			
			// unlock current amazon key
			if ( $is_remote_keys ) {
				$this->keysObj->unlock_current_access_key( $pms['keys_id'] );
			}

			$this->the_plugin->save_amazon_last_requests(array_merge($pms, array(
				'request_status'		=> $request_status,
			)));

            return array(
            	'status'				=> $request_status['status'],
            	'msg'				=> $request_status['msg'],
            	'response' 		=> $response,
            	'code'				=> $request_status['code'],
            	'req_link' 			=> $req_link
			);
		}

		/**
		 * search products by asins list
		 * input(pms): array(
		 * 		asins								: array
		 * )
		 * return: array(
		 * 		response						: array
		 * 		status							: string ( valid | invalid )
         *         msg								: string
		 * 		code								: int
		 * 		req_link							: string
		 * )
		 */
		public function api_search_byasin( $pms=array() ) {
			
			$stat = $this->api_verify_request(array_merge($pms, array('what_func' => 'api_search_byasin')));
			if ( 'valid' == $stat['status'] ) {
            	return $stat;
			}

			extract($pms);
			
			// lock current amazon key
			$is_remote_keys = isset($pms['keys_id']) && !empty($pms['keys_id']) ? true : false;
			if ( $is_remote_keys ) {
				$this->keysObj->lock_current_access_key( $pms['keys_id'] );
			}
			
			try {

			$merchant_setup = (isset($this->amz_settings["merchant_setup"]) && $this->amz_settings["merchant_setup"] == 'only_amazon' ? 'only_amazon' : 'amazon_or_sellers');
			$merchant_setup_ = ('only_amazon' == $merchant_setup ? 'Amazon' : 'All');

			$this->aaAmazonWS
				->responseGroup('Large,ItemAttributes,OfferFull,Offers,Variations,Reviews,PromotionSummary,SalesRank')
				->optionalParameters(array('MerchantId' => $merchant_setup_));
			
			$response = $this->aaAmazonWS
				->lookup( implode(",", $asins) );
				
			$req_link = $this->aaAmazonWS->get_xml_amazon_link('normal');
			//var_dump('<pre>',$response,'</pre>'); die;
                    
            //$__asinsDebug = array();
            //foreach ( $response['Items']['Item'] as $item_key => $item_val ) {
            //    $__asinsDebug[] = $item_val['ASIN'];
            //}
            //var_dump('<pre>',$__asinsDebug,'</pre>');
            
			} catch (Exception $e) {
                // Check 
                if (isset($e->faultcode)) { // error occured!
                    $msg = $e->faultcode .  ' : ' . (isset($e->faultstring) ? $e->faultstring : $e->getMessage());
                    //var_dump('<pre>',$msg,'</pre>'); die;
                    
					// unlock current amazon key
					if ( $is_remote_keys ) {
						$this->keysObj->unlock_current_access_key( $pms['keys_id'] );
					}

					$response = array('status' => 'invalid', 'msg' => $msg, 'code' => 1, 'req_link' => $req_link);
					return array_merge(array('response' => $response), $response);
                }
            }

			$request_status = $this->is_amazon_valid_response( $response );

			// unlock current amazon key
			if ( $is_remote_keys ) {
				$this->keysObj->unlock_current_access_key( $pms['keys_id'] );
			}

			$this->the_plugin->save_amazon_last_requests(array_merge($pms, array(
				'request_status'		=> $request_status,
			)));

            return array(
            	'status'				=> $request_status['status'],
            	'msg'				=> $request_status['msg'],
            	'response' 		=> $response,
            	'code'				=> $request_status['code'],
            	'req_link' 			=> $req_link
			);
		}

		/**
		 * format api response results
		 * input(pms): array(
		 * 		requestData			: array,
		 * 		response			: array,
		 * )
		 * return: array(
		 * 		requestData			: array,
		 * 		response			: array,
		 * )
		 */
		public function api_format_results( $pms=array() ) {
			extract($pms);

			{
				$rsp = $this->api_search_set_stats(array(
					'requestData'				=> $requestData,
					'response'					=> $response,
				));
				$requestData = $rsp['requestData'];
			}

            // verify array of Items or array of Item elements
            if ( isset($response['Items']['Item']['ASIN']) ) {
				$response['Items']['Item'] = array( $response['Items']['Item'] );
            }

			$_response = array();
			foreach ( $response['Items']['Item'] as $key => $value){
				$_response["$key"] = $value;
			}
			//var_dump('<pre>', $_response, '</pre>'); die('debug...');

			return array(
				'requestData'	=> $requestData,
				'response'		=> $_response,
			);
		}
		
		/**
		 * search results validation
		 * input(pms): array(
		 * 		results				: array,
		 * )
		 * return: array(
		 * 		status				: boolean,
		 * 		nbpages				: int,
		 * )
		 */
		public function api_search_validation( $pms=array() ) {
			extract($pms);

			$status = true;
            if ( !isset($results['Items'], $results['Items']['TotalResults'], $results['Items']['NbPagesSelected'])
                || count($results) < 2 ) {
				$status = false;
			}
			$nbpages = isset($results['Items'], $results['Items']['NbPagesSelected']) ? (int) $results['Items']['NbPagesSelected'] : 0;
			
			return array(
				'status'		=> $status,
				'nbpages'		=> $nbpages,
			);
		}
		
		/**
		 * search products by pages: get search stats!
		 * input(pms): array(
		 * 		results				: array,
		 * )
		 * return: array(
		 * 		stats				: array,
		 * )
		 */
		public function api_search_get_stats( $pms=array() ) {
			extract($pms);

			return array(
				'stats'	=> array(
					'TotalResults'			=> $results['Items']['TotalResults'],
					'NbPagesSelected'		=> $results['Items']['NbPagesSelected'],
					'TotalPages'			=> $results['Items']['TotalPages'],
				)
			);
		}
		
		/**
		 * search products by pages: set search stats!
		 * input(pms): array(
		 * 		requestData			: array, 
		 * 		response			: array,
		 * )
		 * return: array(
		 * 		requestData			: array, 
		 * 		stats				: array,
		 * )
		 */
		public function api_search_set_stats( $pms=array() ) {
			extract($pms);

			{
				$totalItems = 0; $totalPages = 0;
				if ( isset($response['Items']['TotalResults']) ) {

					$totalItems = isset($response['Items']['TotalResults']) ? $response['Items']['TotalResults'] : 0;
					$totalPages = $totalItems > 0 ? ceil( $totalItems / 10 ) : 0;
				}
					
				if ( isset($totalPages, $requestData['nbpages'])
					&& $totalPages > 0
	            	&& (int) $totalPages < $requestData['nbpages'] ) {
   
	                $requestData['nbpages'] = (int) $totalPages;
	                // don't put this validated nbpages in $__cacheSearchPms, because the cache file could not be recognized then!
				}
			}

			return array(
				'requestData'	=> $requestData,
				'stats'			=> array(
					'TotalResults'			=> $totalItems,
					'TotalPages'			=> $totalPages,
				)
			);
		}

		/**
		 * search products by pages: get page asins list from cache file! 
		 * input(pms): array(
		 * 		page_content		: array,
		 * )
		 * return: array(
		 * 		asins				: int,
		 * )
		 */
		public function api_cache_get_page_asins( $pms=array() ) {
			extract($pms);
			
			$asins = $page_content['Items']['Item'];
			return array(
				'asins'		=> $asins,
			);
		}
		
		/**
		 * search products by pages: set page content as list of asins! 
		 * input(pms): array(
		 * 		requestData			: array, 
		 * 		content				: array,
		 * 		old_content			: array,
		 * 		cachename			: object,
		 * 		page				: int,
		 * )
		 * return: array(
		 * 		dataToSave			: array,
		 * )
		 */
		public function api_cache_set_page_content( $pms=array() ) {
			extract($pms);
			
			$response = $content;

			$dataToSave = array();
			if ( !empty($old_content) ) {
				$dataToSave = $old_content;
			} else {
				$rsp = $this->api_search_set_stats(array(
					'requestData'				=> $requestData,
					'response'					=> $response,
				));
				$stats = $rsp['stats'];

				$dataToSave['Items']['TotalResults'] = $stats['TotalResults'];
            	$dataToSave['Items']['TotalPages'] = $stats['TotalPages'];
                $dataToSave['Items']['NbPagesSelected'] = $cachename->params['nbpages'];
			}

            if ( is_array($content) && !isset($content['__notused__']) ) {

				$rsp = $this->api_format_results(array(
					'requestData'			=> $requestData,
					'response'				=> $response,
				));

				$dataToSave["$page"] = array();

				// 1 item found only
				if ( $dataToSave['Items']['TotalResults'] == 1 && !isset($rsp['response'][0]) ) {
					$rsp['response'] = array($rsp['response']);
				}

				foreach ($rsp['response'] as $key => $value) {
					$product = $this->build_product_data( $value );
					if ( !empty($product['ASIN']) ) {
						$dataToSave["$page"]['Items']['Item']["$key"] = $product['ASIN'];
					}
				}
			}			

			return array(
				'dataToSave'		=> $dataToSave,
			);
		}


		/**
		 * March 2016 - new methods
		 */
		public function api_verify_request( $pms=array() ) {

			// make remote request through aa-team server
			if ( $this->the_plugin->do_remote_amazon_request() ) {
				$stat = $this->the_plugin->get_remote_amazon_request( $pms );

				//if ( 'valid' == $stat['status'] ) {

					$method = isset($pms['method']) ? $pms['method'] : '';
					$this->the_plugin->save_amazon_last_requests(array_merge($pms, array(
						//'request_status'		=> $this->is_amazon_valid_response( $stat['response'], $method ),
						'request_status'		=> array(
							'status'							=> $stat['status'],
							'msg'							=> $stat['msg'],
						),
						'is_remote'				=> true,
					)));
					
					$this->the_plugin->save_amazon_request_remote_time();
				//}

				if (1) {
            		return array(
            			'status'				=> $stat['status'],
            			'msg'				=> $stat['msg'],
            			'response' 		=> $stat['response'],
						'code'				=> $stat['code'],
					);
				}
			}
			// re-init settings if demo keys are in use
			$_status = $this->the_plugin->verify_amazon_keys();
			if ( 'demo' == $_status['status'] ) {
				$this->init_settings( array(), false );				
			}
            return array(
            	'status'				=> 'default',
            	'msg'				=> 'default: not remote',
            	'response' 		=> array(),
				'code'				=> 0 
			);
		}

		/**
		 * general request to amazon
		 * input(pms): array(
		 * 		requestData					: array( // posible parameters bellow
		 * 			category					: string
		 * 			page							: int
		 * 			keyword					: string
		 * 			asin							: string | array
		 * 		)
		 * 		optionalParameters		: array
		 * 		responseGroup				: '' //ex.: Large,ItemAttributes,Offers,Reviews
		 * 		method							: '' //ex.: lookup | search
		 * )
		 * return: array(
		 * 		response						: array
		 * 		status							: string ( valid | invalid )
         *         msg								: string
		 * 		code								: int
		 * 		req_link							: string
		 * )
		 */
		public function api_make_request( $pms=array() ) {
   
			$stat = $this->api_verify_request(array_merge($pms, array('what_func' => 'api_make_request')));
			if ( 'valid' == $stat['status'] ) {
            	return $stat;
			}

			extract($pms);
			if ( isset($requestData) ) {
				extract($requestData);
			}
			
			// lock current amazon key
			$is_remote_keys = isset($pms['keys_id']) && !empty($pms['keys_id']) ? true : false;
			if ( $is_remote_keys ) {
				$this->keysObj->lock_current_access_key( $pms['keys_id'] );
			}
 
			$responseGroup 		= isset($responseGroup) ? $responseGroup : 'Large,ItemAttributes,Offers,Reviews';

			$merchant_setup = (isset($this->amz_settings["merchant_setup"]) && $this->amz_settings["merchant_setup"] == 'only_amazon' ? 'only_amazon' : 'amazon_or_sellers');
			$merchant_setup_ = ('only_amazon' == $merchant_setup ? 'Amazon' : 'All');

			$optionalParameters = isset($optionalParameters) && !empty($optionalParameters) ? $optionalParameters : array();
			$optionalParameters = array_merge($optionalParameters, array('MerchantId' => $merchant_setup_));

			if ( isset($asin) && is_array($asin) ) {
				$asin = implode(",", $asin);
			}
			
			$category			= isset($category) ? $category : 'DVD';
			$page				= isset($page) ? $page : 1;
			$keyword			= isset($keyword) ? $keyword : 'Matrix';
			$nodeid				= isset($nodeid) ? $nodeid : 0;
			$selectedItems	= isset($selectedItems) ? $selectedItems : array();

			$req_link = '';
			try {

			$method = isset($pms['method']) ? $pms['method'] : '';
			switch ( $method ) {

				case 'lookup':
					$response = $this->aaAmazonWS->responseGroup( $responseGroup )->optionalParameters( $optionalParameters )
						->lookup( $asin );
					break;
					
				case 'similarityLookup':
					$response = $this->aaAmazonWS->responseGroup( $responseGroup )->optionalParameters( $optionalParameters )
						->similarityLookup( $asin );
					break;

				case 'search':
					$response = $this->aaAmazonWS->category( $category )->page( $page )->responseGroup( $responseGroup )
						->search( $keyword );
					break;

				case 'browseNodeLookup':
					$response = $this->aaAmazonWS->responseGroup( $responseGroup )
						->browseNodeLookup( $nodeid );
					break;

				case 'cartThem':
					$response = $this->aaAmazonWS->responseGroup( $responseGroup )
						->cartThem( $selectedItems );
					break;
					
				case 'cartKill':
					$response = $this->aaAmazonWS->responseGroup( $responseGroup )
						->cartKill();
					break;
					
				default:
					$response = array('status' => 'invalid', 'msg' => 'you need to provide a valid method!', 'code' => 1, 'req_link' => $req_link);
					return array_merge(array('response' => $response), $response);
					//break;
			}

			$req_link = $this->aaAmazonWS->get_xml_amazon_link('normal');
			//var_dump('<pre>',$response,'</pre>'); die;

			} catch (Exception $e) {
                // Check 
                if (isset($e->faultcode)) { // error occured!
                    $msg = $e->faultcode .  ' : ' . (isset($e->faultstring) ? $e->faultstring : $e->getMessage());
                    //var_dump('<pre>',$msg,'</pre>'); die;
                    
					// unlock current amazon key
					if ( $is_remote_keys ) {
						$this->keysObj->unlock_current_access_key( $pms['keys_id'] );
					}
       
					$response = array('status' => 'invalid', 'msg' => $msg, 'code' => 1, 'req_link' => $req_link);
					return array_merge(array('response' => $response), $response);
                }
            }
   
			$request_status = $this->is_amazon_valid_response( 'cartThem' == $method ? $this->aaAmazonWS->get_lastCart() : $response, $method );

			// unlock current amazon key
			if ( $is_remote_keys ) {
				$this->keysObj->unlock_current_access_key( $pms['keys_id'] );
			}

			$this->the_plugin->save_amazon_last_requests(array_merge($pms, array(
				'request_status'		=> $request_status,
			)));

            return array(
            	'status'				=> $request_status['status'],
            	'msg'				=> $request_status['msg'],
            	'response' 		=> $response,
            	'code'				=> $request_status['code'],
            	'req_link' 			=> $req_link
			);
		}
	}
}