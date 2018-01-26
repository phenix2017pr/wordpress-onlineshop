<?php
/*
* Define class WooZoneLiteInsaneImport
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
!defined('ABSPATH') and exit;

if (class_exists('WooZoneLiteInsaneImport') != true) {
    class WooZoneLiteInsaneImport
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
        public $aaAmazonWS = null;

		private $module_folder = '';
        private $module_folder_path = '';
		private $module = '';

		static protected $_instance;
		
		private $settings;

        private static $CACHE = array(
            'search_lifetime'       => 720, // cache lifetime in minutes /half day
            'search_folder'         => '',
            'prods_lifetime'        => 1440, // cache lifetime in minutes /one day
            'prods_folder'          => '',
        );
        private static $CACHE_ENABLED = array(
            'search'                => true,
            'prods'                 => true,
        );

        //const LOAD_MAX_LIMIT =  10; // number of ASINs per amazon requests!
		private static $LOAD_MAX_LIMIT = array(
            'amazon'                => 10,
        );
		
		private static $REQUESTS_DELAY = array( // delay in micro seconds based on provider and on number of requests made!
		 	// nbreq = number of consecutive requests made; delay = sleep in microseconds
            'amazon'                => array('nbreq' => 0, 'delay' => 0), // amazon delay is made with Amazon requests rate option!
		);
		private static $REQUESTS_NB = array(
			'amazon' 				=> array('current' => 0, 'total' => 0),
		);

        const MSG_SEP = '—'; // messages html bullet // '&#8212;'; // messages html separator
        
        private static $optionalParameters = array(
        	'amazon'	=> array(
	            'BrowseNode'        => 'select',
	            'Brand'             => 'input',
	            'Condition'         => 'select',
	            'Manufacturer'      => 'input',
	            'MaximumPrice'      => 'input',
	            'MinimumPrice'      => 'input',
	            'MinPercentageOff'  => 'select',
	            'MerchantId'        => 'input',
	            'Sort'              => 'select',
            ),
        );
		
		private $objAI = null; // auto import object


        /*
        * Required __construct() function that initalizes the AA-Team Framework
        */
        public function __construct( $is_cron=false )
        {
        	global $WooZoneLite;

        	$this->the_plugin = $WooZoneLite;

            $this->amzHelper = $this->the_plugin->amzHelper;
			if ( is_object($this->the_plugin->amzHelper) ) {
            	$this->aaAmazonWS = $this->the_plugin->amzHelper->aaAmazonWS;
			}
            //$this->setupAmazonWS();

			$this->module_folder = $this->the_plugin->cfg['paths']['plugin_dir_url'] . 'modules/insane_import/';
            $this->module_folder_path = $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'modules/insane_import/';
			$this->module = $this->the_plugin->cfg['modules']['insane_import'];
			
			$this->settings = $this->the_plugin->getAllSettings('array', 'amazon');
            self::$CACHE = array_merge(self::$CACHE, array(
                'search_folder'         => $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'cache/search/',
                'prods_folder'          => $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'cache/products/',
            ));
			
			// load auto import module
			if ( !$is_cron ) {
				$this->load_auto_import();
			}
  
			if (is_admin() && !$is_cron) {
	            add_action('admin_menu', array( &$this, 'adminMenu' ));
			}
			
            // ajax requests
			add_action('wp_ajax_WooZoneLiteIM_KeywordAutocomplete', array( &$this, 'ajax_autocomplete' ));
			add_action('wp_ajax_WooZoneLiteIM_InsaneAjax', array( &$this, 'ajax_request' ), 10, 2);
            add_action('wp_ajax_WooZoneLiteIM_LoadProdsGrabParseURL', array( &$this, 'loadprods_grab_parse_url' ));
            add_action('wp_ajax_WooZoneLiteIM_LoadProdsByASIN', array( &$this, 'loadprods_queue_by_asin' ), 10, 2);
            add_action('wp_ajax_WooZoneLiteIM_LoadProdsBySearch', array( &$this, 'loadprods_queue_by_search' ), 10, 2);
            add_action('wp_ajax_WooZoneLiteIM_exportASIN', array( &$this, 'ajax_export_asin' ), 10, 1);
            add_action('wp_ajax_WooZoneLiteIM_getCategoryParams', array( &$this, 'get_category_params_html' ), 10, 2);
            add_action('wp_ajax_WooZoneLiteIM_getBrowseNodes', array( &$this, 'get_browse_nodes_html' ), 10, 2);
            add_action('wp_ajax_WooZoneLiteIM_ImportProduct', array( $this, 'import_product' ), 10, 2);
			add_action('wp_ajax_WooZoneLiteIM_GetScoreHint', array( $this, 'get_score_hint' ), 10, 2);
			
			$this->settings['page_types'] = array(
				'Best Sellers',
				//'Deals',
				'Top Rated',
				'Most Wished For',
				'Movers & Shakers',
				'Hot New Releases',
				//'Best Sellers Cattegory',
				//'Gift Ideas',
				//'New Arrivals',
			);
        }

		/**
	    * Singleton pattern
	    *
	    * @return WooZoneLiteInsaneImport Singleton instance
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
    			$this->the_plugin->alias . " " . __('Insane Import', $this->the_plugin->localizationName),
	            __('Insane Import', $this->the_plugin->localizationName),
	            'manage_options',
	            $this->the_plugin->alias . "_insane_import",
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
			
			//ob_start();
?>
    		<link rel='stylesheet' href='<?php echo $this->module_folder;?>rangeslider/rangeslider.css' type='text/css' media='all' />
			
			<div id="<?php echo WooZoneLite()->alias?>">
				
				<div class="<?php echo WooZoneLite()->alias?>-content"> 
				
					<?php if (is_object($this->objAI)) { // auto import
						$this->objAI->load_asset('css');
					} ?>
		
		    		<!-- <div id="WooZoneLite-wrapper" class="fluid wrapper-WooZoneLite WooZoneLite-asin-grabber"> -->
		
						<?php
						// show the top menu
						WooZoneLiteAdminMenu::getInstance()->make_active('import|insane_import')->show_menu();
						?>
			
						<!-- Content -->
						<section id="WooZoneLite-content" class="WooZoneLite-main">
						<!-- <div> -->
							
							<?php 
                    		echo WooZoneLite()->print_section_header(
		                        $this->module['insane_import']['menu']['title'],
		                        $this->module['insane_import']['description'],
		                        $this->module['insane_import']['help']['url']
		                    );
		                    ?>
		                    
<?php
	if ( !WooZoneLite()->can_import_products() ) {
		echo '<div class="panel panel-default WooZoneLite-panel WooZoneLite-setup">';
		echo '<div class="panel-body WooZoneLite-panel-body">';
		echo 	WooZoneLite()->demo_products_import_end_html();
		echo '</div>';
		echo '</div>';
	} else {
?>
							
							<!-- Main loading box -->
							<div id="WooZoneLite-main-loading">
								<div id="WooZoneLite-loading-overlay"></div>
								<div id="WooZoneLite-loading-box">
									<div class="WooZoneLite-loading-text"><?php _e('Loading', $this->the_plugin->localizationName);?></div>
									<div class="WooZoneLite-meter WooZoneLite-animate" style="width:86%; margin: 34px 0px 0px 7%;"><span style="width:100%"></span></div>
								</div>
							</div>
			
							<!-- Container -->
							<div class="WooZoneLite-container clearfix" id="WooZoneLite-insane-import" style="position: relative;">
							    
								<!-- Main Content Wrapper -->
								<div id="WooZoneLite-content-wrap" class="clearfix" style="padding-top: 5px;">
			                    <?php
			                    // find if user makes the setup
			                    $moduleValidateStat = $this->moduleValidation();
			                    if ( !$moduleValidateStat['status']
			                    	|| !is_object($this->the_plugin->amzHelper) || is_null($this->the_plugin->amzHelper) )
			                        echo $moduleValidateStat['html'];
			                    else {
			                    	WooZoneLite()->print_demo_request();
			                    ?>
			                    
			                        <?php
			                            // IMPORT PRODUCTS - PARAMETERS
			                            $amz_settings = $this->settings;
			                            $import_params = array(
			                                'spin_at_import'            => false,
			                                'import_attributes'         => false,
			                                'import_type'               => 'default',
			                                'number_of_images'          => '2',
			                                'number_of_variations'      => '2',
			                                'prods_import_type'			=> 'default',
			                            );
			                            
			                            // download images
			                            $import_type = 'default';
			                            if ( isset($amz_settings['import_type']) && $amz_settings['import_type']=='asynchronous' ) {
			                                $import_type = $amz_settings['import_type' ];
			                            }
			                            $import_params['import_type'] = $import_type;
			                                
			                            // number of images
			                            $number_of_images = (
			                                isset($amz_settings["number_of_images"]) && (int) $amz_settings["number_of_images"] > 0
			                                ? (int) $amz_settings["number_of_images"] : 'all'
			                            );
			                            if ( $number_of_images > 100 ) $number_of_images = 'all';
			                            $import_params['number_of_images'] = $number_of_images;
			                            
			                            // number of variations
			                            $variationNumber = isset( $amz_settings['product_variation'] ) ? $amz_settings['product_variation'] : 'no';
			                            // convert $variationNumber into number
			                            if( $variationNumber == 'yes_all' ){
			                                $variationNumber = 'all'; // 100 variation is enough
			                            }
			                            elseif( $variationNumber == 'no' ){
			                                $variationNumber = 0;
			                            }
			                            else{
			                                $variationNumber = explode(  "_", $variationNumber );
			                                $variationNumber = (int) end( $variationNumber );
			                                if ( $variationNumber > 100 ) $variationNumber = 'all';
			                            }
			                            $import_params['number_of_variations'] = $variationNumber;
			                            
			                            // spin at import
			                            $spin_at_import = isset($amz_settings['spin_at_import']) && $amz_settings['spin_at_import'] == 'yes' ? true : false;
			                            $import_params['spin_at_import'] = $spin_at_import;
			                            
			                            // import attributes
			                            $import_attributes = isset($amz_settings['item_attribute']) && $amz_settings['item_attribute'] == 'no' ? false : true;
			                            $import_params['import_attributes'] = $import_attributes;
			    
			                            //var_dump('<pre>', $import_params, '</pre>'); die('debug...'); 
			                        ?>
			    
			                        <?php
			                            // Lang Messages
			                            $lang = array(
			                                'loading'                   => __('Loading...', 'WooZoneLite'),
			                                'closing'                   => __('Closing...', 'WooZoneLite'),
			                                'load_op_search'            => __('load prods by search', 'WooZoneLite'),
			                                'load_op_grab'              => __('load prods by grab', 'WooZoneLite'),
			                                'load_op_bulk'              => __('load prods by bulk', 'WooZoneLite'),
			                                'load_op_export'            => __('export asins', 'WooZoneLite'),
			                                'load_op_import'            => __('import products', 'WooZoneLite'),
			                                'search_pages_single'       => __(' First page', 'WooZoneLite'),
			                                'search_pages_many'         => __(' First %s pages', 'WooZoneLite'),
			                                'bulk_add_asin'             => self::MSG_SEP . __(' Please first add some ASINs!', 'WooZoneLite'),
			                                'bulk_no_asin_found'        => self::MSG_SEP . __(' No ASINs found!', 'WooZoneLite'),
			                                'bulk_asin_found'           => self::MSG_SEP . __(' %s ASINs found: ', 'WooZoneLite'),
			                                'already_exists'            => self::MSG_SEP . __(' %s ASINs already parsed (loaded, invalid, imported): %s', 'WooZoneLite'),
			                                'export_no_asin'            => self::MSG_SEP . __(' No ASINs found to export!', 'WooZoneLite'),
			                                
			                                'loadprods_inprogress'      => __('Loading Products in Queue In Progress...', 'WooZoneLite'),
			                                'importprods_inprogress'    => __('Importing Products In Progress...', 'WooZoneLite'),
			                                
			                                'speed_value'               => __('%s PPM', 'WooZoneLite'), //products per minute
			                                'speed_level1'              => __('SPEED is VERY SLOW.', 'WooZoneLite'),
			                                'speed_level2'              => __('SPEED is SLOW.', 'WooZoneLite'),
			                                'speed_level3'              => __('SPEED is OK.', 'WooZoneLite'),
			                                'speed_level4'              => __('SPEED is FAST.', 'WooZoneLite'),
			                                'speed_level5'              => __('SPEED is VERY FAST.', 'WooZoneLite'),
			                                'speed_level6'              => __('SPEED is INSANE.', 'WooZoneLite'),
			                                
			                                'day'                       => __('day', 'WooZoneLite'),
			                                'hour'                      => __('hour', 'WooZoneLite'),
			                                'min'                       => __('minute', 'WooZoneLite'),
			                                'sec'                       => __('second', 'WooZoneLite'),
			                                
			                                // import product screen
			                                'btn_stop'                  => __('STOP', 'WooZoneLite'),
			                                'btn_close'                 => __('CLOSE BOX', 'WooZoneLite'),
			
			                                'import_empty'              => __('No products selected for import!', 'WooZoneLite'),
			                                'process_status_stop'       => __('the process is stopped', 'WooZoneLite'),
			                                'process_status_stop_'      => __('the process will stop after the current product', 'WooZoneLite'),
			                                'process_status_run'        => __('the process is running', 'WooZoneLite'),
			                                'process_status_finished'   => __('the process is finished', 'WooZoneLite'),
			                                'parsed_prods'              => __('%s of %s products', 'WooZoneLite'),
			                                'parsed_images'             => __('%s of %s images', 'WooZoneLite'),
			                                'parsed_variations'         => __('%s of %s variations', 'WooZoneLite'),
			                                
			                                'current_product_title'     => __('current product', 'WooZoneLite'),
			                                'next_product_title'        => __('next product', 'WooZoneLite'),
			                                
											'check_all'					=> __('check all', 'WooZoneLite'),
											'uncheck_all'				=> __('uncheck all', 'WooZoneLite'),
											
											'auto__process_status_stop_'      => sprintf( __('the process will stop after the current chunk of %s products', 'WooZoneLite'), 10 ),
			                            ); 
			                        ?>
			                        <!-- Lang Messages -->
			                        <div id="WooZoneLite-lang-translation" style="display: none;"><?php echo htmlentities(json_encode( $lang )); ?></div>
			                    
			                        <?php
			                            // Import Estimation Settings
			                            $importSettings = $this->the_plugin->get_last_imports(); 
			                        ?>
			                        <!-- Import Estimation Settings -->
			                        <div id="WooZoneLite-import-settings" style="display: none;"><?php echo htmlentities(json_encode( $importSettings )); ?></div>
			                        
			                        <?php
			                            // General Settings
			                            $generalSettings = $this->get_general_settings();
			                        ?>
			                        <!-- General Settings -->
			                        <div id="WooZoneLite-general-settings" style="display: none;"><?php echo htmlentities(json_encode( $generalSettings )); ?></div>
			
			                        <!-- Background Loading - OLD, not used -->
									<div class="WooZoneLite-insane-work-in-progress">
										<ul class="WooZoneLite-preloader"><li></li><li></li><li></li><li></li><li></li></ul>
										<span class="WooZoneLite-the-action"><?php _e('Execution action ...', $this->the_plugin->localizationName);?></span>
									</div>
									
									<!-- Import Product Screen -->
									<div id="WooZoneLite-import-screen" style="display: none;">
			
										<div class="WooZoneLite-iip-lightbox" id="WooZoneLite-iip-screen">
										    <div class="WooZoneLite-iip-in-progress-box">
										
										        <h1><?php _e('Import products in progress ...', $this->the_plugin->localizationName); ?></h1>
										        <p class="WooZoneLite-message WooZoneLite-info WooZoneLite-iip-notice">
										        <?php _e('Please be patient while the products are been imported. 
										        This can take a while if your server is slow (inexpensive hosting) or if you have many products. 
										        Do not navigate away from this page until this script is done. 
										        You will be notified via this box when the regenerating is completed.', $this->the_plugin->localizationName); ?>
										        </p>
										        <div class="WooZoneLite-iip-details">
										            <table>
										                <thead>
										                    <tr>
										                        <th><span><?php _e('Import Status', $this->the_plugin->localizationName); ?></span></th>
										                        <th><span><?php _e('Estimated Remained Time', $this->the_plugin->localizationName); ?></span></th>
										                        <th><span><?php _e('Speed', $this->the_plugin->localizationName); ?></span></th>
										                    </tr>
										                </thead>
										                <tbody>
										                    <tr>
										                        <td id="WooZoneLite-iip-estimate-status">
										                            <input type="button" value="<?php _e('STOP', $this->the_plugin->localizationName); ?>" class="WooZoneLite-form-button WooZoneLite-form-button-danger" id="WooZoneLite-import-stop-button">
										                            <span><?php echo $lang['process_status_run']; ?></span>
										                        </td>
										                        <td id="WooZoneLite-iip-estimate-time"><span></span></td>
										                        <td id="WooZoneLite-iip-estimate-speed"><span>0 <?php _e('PPM', $this->the_plugin->localizationName); ?></span></td>
										                    </tr>
										                </tbody>
										            </table>
										        </div>
										        <div class="WooZoneLite-iip-process-progress-bar im-products">
										            <div class="WooZoneLite-iip-process-progress-marker"></div>
										            <div class="WooZoneLite-iip-process-progress-text">
										                <span><?php _e('Progress', $this->the_plugin->localizationName); ?>: <span>0%</span></span>
										                <span><?php _e('Parsed', $this->the_plugin->localizationName); ?>: <span></span></span>
										                <span><?php _e('Elapsed time', $this->the_plugin->localizationName); ?>: <span></span></span>
										            </div>
										        </div>
										      
										        <div class="WooZoneLite-iip-process-progress-bar im-images">
										            <div class="WooZoneLite-iip-process-progress-marker"></div>
										            <div class="WooZoneLite-iip-process-progress-text">
										                <span><?php _e('Progress', $this->the_plugin->localizationName); ?>: <span>0%</span></span>
										                <span><?php _e('Parsed', $this->the_plugin->localizationName); ?>: <span></span></span>
										            </div>
										        </div>
										      
										        <div class="WooZoneLite-iip-process-progress-bar im-variations">
										            <div class="WooZoneLite-iip-process-progress-marker"></div>
										            <div class="WooZoneLite-iip-process-progress-text">
										                <span><?php _e('Progress', $this->the_plugin->localizationName); ?>: <span>0%</span></span>
										                <span><?php _e('Parsed', $this->the_plugin->localizationName); ?>: <span></span></span>
										            </div>
										        </div>
										
										        <div class="WooZoneLite-iip-tail">
										            <ul class="WZC-keyword-attached WooZoneLite-insane-bigscroll">
										            </ul>
										        </div>
										        
										        <div class="WooZoneLite-iip-log">
										            
										        </div>
										
										    </div>
										</div>
			
									</div>
									
									<!-- Auto-Import Product Screen -->
									<?php if (is_object($this->objAI)) { // auto import
										$this->objAI->print_auto_import_screen(array());
									} ?>
			
									<!-- Content Area -->
									<div class="WooZoneLite-insane-container WooZoneLite-insane-tabs">
									    <div class="WooZoneLite-insane-buton-logs" data-logcontainer="WooZoneLite-logs-load-products"><?php _e('View Messages Log', $this->the_plugin->localizationName); ?></div>
									    <?php if (is_object($this->objAI)) { // auto import
											$this->objAI->print_schedule_button(array(
												'title' => __('Add Search to schedule', $this->the_plugin->localizationName),
												'ver2'	=> true,
											));
										} ?>
										<div class="WooZoneLite-insane-panel-headline">
			                				<a href="#WooZoneLite-content-search" class="on"><?php _e('SEARCH FOR PRODUCTS', $this->the_plugin->localizationName);?></a>   
			                				<a href="#WooZoneLite-content-extension"><?php _e('Chrome Extension', $this->the_plugin->localizationName);?></a>
										</div>
										<div class="WooZoneLite-insane-tabs-content">
											<div id="WooZoneLite-content-scroll">
												
						            			<div id="WooZoneLite-content-search" class="WooZoneLite-insane-tab-content">
						            				<!-- Search buttons -->
						            				<div class="WooZoneLite-insane-tab-search-buttons-container">
						            					<form id="WooZoneLite-search-products">
							            					<ul class="WooZoneLite-insane-tab-search-buttons">
							            						<li>				            						
						            								<span class="tooltip" title="Choose Keyword"><i class="fa fa-search"></i></span>
						            							 	<input type="text" id="WooZoneLite-search-keyword" name="WooZoneLite-search[keyword]" placeholder="<?php _e('Keyword', $this->the_plugin->localizationName);?>">
						            							 	<ul class="WooZoneLite-search-completion"></ul>
							            						</li>
							            						<li id="WooZoneLite-select-on-category">
							            							<span class="tooltip" title="Choose Category or Custom BrowseNode"><i class="fa fa-sitemap"></i></span>
			                                                        <select id="WooZoneLite-search-category" name="WooZoneLite-search[category]">
			                                                            <option value="" disabled="disabled"><?php _e('Category', $this->the_plugin->localizationName);?></option>
			                                                            <option value="AllCategories" selected="selected" data-nodeid="all"><?php _e('All categories', $this->the_plugin->localizationName);?></option>
			                                                            <?php echo $this->get_categories_html(); ?>
			                                                        </select>
							            							<?php /*<input readonly type="text" class="WooZoneLite-select-category-placeholder" value="<?php _e('All categories', $this->the_plugin->localizationName);?>" id="WooZoneLite-search-search_on" name="WooZoneLite-search[search_on]" />
							            							<div class="WooZoneLite-category-selector">
							            								<label>
							            									<span><?php _e('Search on Category', $this->the_plugin->localizationName);?>:</span>
							            									<select id="WooZoneLite-search-category" name="WooZoneLite-search[category]">
									            								<option value="" disabled="disabled"><?php _e('Category', $this->the_plugin->localizationName);?></option>
			                                                                    <option value="AllCategories" selected="selected"><?php _e('All categories', $this->the_plugin->localizationName);?></option>
																				<?php echo $this->get_categories_html(); ?>
																			</select>
							            								</label>
							            								
			                                                            <label>
			                                                                <span><?php _e('Custom BrowseNode ID', $this->the_plugin->localizationName);?>:</span>
			                                                                <input type="text" id="WooZoneLite-node" name="WooZoneLite-search[node]" />
			                                                            </label>
							            							</div>*/ ?>
							            						</li>
							            						<!--li style="border:none; line-height:34px; width:auto;"><?php _e('or', $this->the_plugin->localizationName); ?></li>
							            						<li id="WooZoneLite-select-on-category">
							            							<span class="tooltip" title="<?php _e('Enter Custom BrowseNode', $this->the_plugin->localizationName);?>"><i class="fa fa-sitemap"></i></span>
			                                                        <input type="text" id="WooZoneLite-search-node" name="WooZoneLite-search[node]" placeholder="<?php _e('Custom BrowseNode', $this->the_plugin->localizationName);?>"/>
							            						</li-->
			                                                    <li>
			                                                        <span class="tooltip" title="Choose number of pages to search for results from amazon"><i class="fa fa-briefcase"></i></span>
			                                                        <select id="WooZoneLite-search-nbpages" name="WooZoneLite-search[nbpages]">
			                                                            <option value="" disabled="disabled"><?php _e('Grab', $this->the_plugin->localizationName);?></option>
			                                                        <?php
			                                                            for ($i = 1; $i <= 5; ++$i) {
			                                                                $text = $i == 1 ? $lang['search_pages_single'] : sprintf( $lang['search_pages_many'], $i );
			                                                                $selected = $i == 1 ? 'selected="selected"' : '';
			                                                                echo '<option value="'.$i.'" '.$selected.'>'.$text.'</option>';
			                                                            }
			                                                        ?>
			                                                        </select>
			                                                    </li>
							            						<li class="button-block">
							            							<input type="submit" value="<?php _e('Launch search', $this->the_plugin->localizationName);?>" class="WooZoneLite-form-button WooZoneLite-form-button-info" />
							            						</li>
							            						
							            						<?php /*if (is_object($this->objAI)) { // auto import
																	$this->objAI->print_schedule_button(array(
																		'title' => __('Add Search to schedule', $this->the_plugin->localizationName),
																	));
																}*/ ?>
							            					</ul>
						            					</form>
						            				</div>
						            			</div>
						            			   
						            	
						            			
						            			<div id="WooZoneLite-content-extension" class="WooZoneLite-insane-tab-content">
								            		<h3 style="color:#ad75a2;"> Using WooZoneLite ASIN(s) Grabber Chrome Extension you can Easily Create Product Lists while Browsing on any Amazon Website!</h3>
													<h3> How to install WooZoneLite Chrome Extension</h3>
													<h4> 1. Go to <a href="https://chrome.google.com/webstore/search/woozonelite"/>Chrome Web Store</a> and search for WooZoneLite Extension.</h4>
													<img src="<?php echo $this->module_folder;?>/images/woozonelitechromeext.jpg" alt="ext">
												
													<h4> 2. Locate the WooZoneLite Extension in the Search list and Click on <span style="color:#ad75a2;">ADD TO CHROME</span> </h4>
													<img src="<?php echo $this->module_folder;?>/images/addtochrome.jpg" alt="ext"> </br></br></br>
													<img src="<?php echo $this->module_folder;?>/images/addextbrowser.jpg" alt="ext">
													
													<h4> 3. Go to any <span style="color:#ad75a2;">Amazon Website</span> and while browsing, you can Add Products and Import them into  <span style="color:#ad75a2;">WooZoneLite </span> in no time!</h4>
													
													<h3> Tutorial - How to use the WooZoneLite Chrome Extension</h3>
													<iframe width="560" height="315" src="https://www.youtube.com/embed/47JpadUOZDU" frameborder="0" allowfullscreen></iframe>
												
						            			</div>
						            			
						            			<!-- latest search operation status --> 
						            			<div id="WooZoneLite-loadprods-status"></div>
			
						            		</div>
					            		</div>
									</div>
									
			                        <div class="WooZoneLite-insane-container WooZoneLite-insane-tabs WooZoneLite-insane-container-logs" id="WooZoneLite-logs-load-products">
			                            <div class="WooZoneLite-insane-panel-headline">
			                                <a href="#WooZoneLite-insane-loadstatus" class="on">
			                                    <span><img src="<?php echo $this->module_folder;?>/images/text_logs.png" alt="logs"></span>
			                                    <?php _e('Load in Queue Log', $this->the_plugin->localizationName);?>
			                                </a>
			                            </div>
			                            <div class="WooZoneLite-insane-tabs-content WooZoneLite-insane-status">
			                                <div id="WooZoneLite-insane-loadstatus" class="WooZoneLite-insane-tab-content">
			                                    <ul class="WooZoneLite-insane-logs">
			                                        <?php /*<li class="WooZoneLite-log-notice">
			                                            <i class="fa fa-info"></i>
			                                            <span class="WooZoneLite-insane-logs-frame">Yesterday 10:24 PM</span>
			                                            <p>You deleted the file intermediate-page_1000px_re…rid_02.jpg.</p>
			                                        </li>
			                                        <li class="WooZoneLite-log-error">
			                                            <i class="fa fa-minus-circle"></i>
			                                            <span class="WooZoneLite-insane-logs-frame">Yesterday 10:24 PM</span>
			                                            <p>You deleted the file intermediate-page_1000px_re…rid_02.jpg.</p>
			                                        </li>
			                                        <li class="WooZoneLite-log-success">
			                                            <i class="fa fa-check-circle"></i>
			                                            <span class="WooZoneLite-insane-logs-frame">Yesterday 10:24 PM</span>
			                                            <p>You deleted the file intermediate-page_1000px_re…rid_02.jpg.</p>
			                                        </li>*/ ?>
			                                    </ul>
			                                </div>
			                            </div>
			                        </div>
			
									<div class="WooZoneLite-insane-container WooZoneLite-insane-tabs">
			                    		<div class="WooZoneLite-insane-panel-headline WooZoneLite-check-all">
			                    			<span>
			                    				<input type="checkbox" value="added" name="check-all" id="squaredThree-all" checked>
			                    				<label for="squaredThree-all">uncheck all</label>
			                    			</span>
			                    			<a href="#WooZoneLite-queued-products" class="on">
			                    				<span><img src="<?php echo $this->module_folder;?>/images/products_icon.png" alt="products"></span>
			                    				<?php _e('queued products', $this->the_plugin->localizationName);?>
			                    			</a>
			                                <a href="#WooZoneLite-export-asins">
			                                    <span><img src="<?php echo $this->module_folder;?>/images/text_logs.png" alt="logs"></span>
			                                    <?php _e('Export ASINs', $this->the_plugin->localizationName);?>
			                                </a>
										</div>
										<div class="WooZoneLite-insane-tabs-content WooZoneLite-queue">
					            			<div id="WooZoneLite-queued-products" class="WooZoneLite-insane-tab-content">
			                                    <div id="WooZoneLite-queued-message">
			                                        <?php echo 'There are no products loaded and selected for import in the Queue. You should use one of the above methods first: Search for Products, Grab Products, Already have a list.'; ?>
			                                    </div>
					            				<div class="WZC-products-scroll-cointainer">
			                                        <ul class="WZC-keyword-attached WooZoneLite-insane-bigscroll">
					            					<?php
					            					/*$totals = 32; 
					            					for( $i = 0; $i < $totals; $i++ ){
					            					?>
													    <li>
													        <span class="WooZoneLite-checked-product squaredThree"><input type="checkbox" value="added" name="check" id="squaredThree-1" checked><label for="squaredThree-1"></label></span>
													        <a target="_blank" href="http://ecx.images-amazon.com/images/I/5141E97ulwL._SL75_.jpg" class="WZC-keyword-attached-image"><img src="http://ecx.images-amazon.com/images/I/5141E97ulwL._SL75_.jpg"></a>
													        <div class="WZC-keyword-attached-phrase"><span>galaxy note</span></div>
													        <div class="WZC-keyword-attached-title">Samsung Galaxy Note 4 SM-N910H Black Factory Unloc</div>
													        <div class="WZC-keyword-attached-brand">by: <span>Samsung</span></div>
													        <div class="WZC-keyword-attached-prices"><del>$1,029.99</del><span>$1,029.99</span></div>
													    </li>
												    <?php
													}*/
													?>
													</ul>
												</div>
					            			</div>
					            			<div id="WooZoneLite-queued-results-stats" class="WooZoneLite-insane-tab-product-search-results-stats">
												<label class="WooZoneLite-stats-block WooZoneLite-stats-found">
													<?php _e('Found', $this->the_plugin->localizationName);?>:
													<span><span>0</span> <?php _e('asins', $this->the_plugin->localizationName);?></span>
												</label>
												<label class="WooZoneLite-stats-block WooZoneLite-stats-loaded">
													<?php _e('Loaded and valid', $this->the_plugin->localizationName);?>:
													<span><span>0</span> <?php _e('products', $this->the_plugin->localizationName);?></span>
													<?php /*<p>(products are still being loaded in the background)</p>*/ ?>
												</label>
												<label class="WooZoneLite-stats-block WooZoneLite-stats-selected">
													<?php _e('Selected for Import', $this->the_plugin->localizationName);?>:
													<span><span>0</span> <?php _e('products', $this->the_plugin->localizationName);?></span>
												</label>
			                                    <label class="WooZoneLite-stats-block WooZoneLite-stats-imported">
			                                        <?php _e('Imported', $this->the_plugin->localizationName);?>:
			                                        <span><span>0</span> <?php _e('products', $this->the_plugin->localizationName);?></span>
			                                    </label>
			                                    <label class="WooZoneLite-stats-block WooZoneLite-stats-import_errors">
			                                        <?php _e('Errors on Import', $this->the_plugin->localizationName);?>:
			                                        <span><span>0</span> <?php _e('products', $this->the_plugin->localizationName);?></span>
			                                    </label>
												
												<a href="#" id="WooZoneLite-expand-all">
													<span><i class="fa fa-expand"></i> <?php _e('show products', $this->the_plugin->localizationName);?></span>
													<span style="display:none"><i class="fa fa-times"></i> <?php _e('collapse products list', $this->the_plugin->localizationName);?></span>
												</a>
											</div>
					            		
			                                <div id="WooZoneLite-export-asins" class="WooZoneLite-insane-tab-content">
			                                    <!-- ASINs Bulk export -->
			                                    <form id="WooZoneLite-export-form" class="WooZoneLite-import-products">
			                                        <div class="WooZoneLite-delimiters">
			                                            <span><?php _e('ASIN delimiter by', $this->the_plugin->localizationName);?>:</span>
			                                            <input type="radio" val="newline" name="WooZoneLite-export-delimiter" checked="" class="WooZoneLite-csv-radio" id="WooZoneLite-export-radio-newline"><label for="WooZoneLite-export-radio-newline"><?php _e('New line', $this->the_plugin->localizationName);?> <code>\n</code></label>
			                                            <input type="radio" val="comma" name="WooZoneLite-export-delimiter" id="WooZoneLite-export-radio-comma"><label for="WooZoneLite-export-radio-comma"><?php _e('Comma', $this->the_plugin->localizationName);?> <code>,</code></label>
			                                            <input type="radio" val="tab" name="WooZoneLite-export-delimiter" id="WooZoneLite-export-radio-tab"><label for="WooZoneLite-export-radio-tab"><?php _e('TAB', $this->the_plugin->localizationName);?> <code>TAB</code></label>
			                                        </div>
			                                        <div class="WooZoneLite-delimiters">
			                                            <span>Export ASINs type:</span>
			                                            <select id="WooZoneLite-export-asins-type" name="WooZoneLite-export-asins-type">
			                                                <option value="1"><?php _e('All Loaded and valid', $this->the_plugin->localizationName); ?></option>
			                                                <option value="2"><?php _e('All Selected for Import', $this->the_plugin->localizationName); ?></option>
			                                                <option value="3"><?php _e('All Imported Successfully', $this->the_plugin->localizationName); ?></option>
			                                                <option value="4"><?php _e('All Not Imported - Errors occured', $this->the_plugin->localizationName); ?></option>
			                                                <option value="5"><?php _e('Remained Loaded in Queue', $this->the_plugin->localizationName); ?></option>
			                                                <option value="6"><?php _e('Remained Selected in Queue', $this->the_plugin->localizationName); ?></option>
			                                                <option value="7"><?php _e('All Found invalid', $this->the_plugin->localizationName); ?></option>
			                                            </select>
			                                            <input id="WooZoneLite-export-button" type="button" value="<?php _e('Export ASINs', $this->the_plugin->localizationName);?>" />
			                                        </div>
			                                    </form> 
			                                </div>
			                                
			                            </div>
									</div>
			
									<div class="WooZoneLite-insane-container WooZoneLite-insane-tabs">
									    <div class="WooZoneLite-insane-buton-logs" data-logcontainer="WooZoneLite-logs-import-products"><?php _e('View Messages Log', $this->the_plugin->localizationName); ?></div>
			                    		<div class="WooZoneLite-insane-panel-headline">
			                    			<a href="#WooZoneLite-insane-import-parameters" class="on">
			                    				<span><img src="<?php echo $this->module_folder;?>/images/insane_icon.png" alt="insane settings"></span>
			                    				<?php _e('Insane Mode Import Fine Tuning', $this->the_plugin->localizationName);?>
			                    			</a>
										</div>
										<div class="WooZoneLite-insane-tabs-content">
											<div class="WooZoneLite-insane-import-parameters" id="WooZoneLite-insane-import-parameters">
			
												<ul>
			                                        <li>
			                                        	<?php if ( $this->the_plugin->is_remote_images ) { ?>
														<div class="WooZoneLite-images-overlay">
															<span class="WooZoneLite-images-copywright-text"><?php _e('Remote amazon images is active.', $this->the_plugin->localizationName); ?></span>
														</div>
														<?php } ?>

			                                            <h4><?php _e('Image Import Type', $this->the_plugin->localizationName);?></h4>
			                                            <p><?php _e('Amazon CDN - Please go to Amazon Config / Plugin Setup and setup <strong>Remote amazon images</strong> to YES', $this->the_plugin->localizationName);?></p>
			                                            <span class="WooZoneLite-checked-product squaredThree">
			                                                <input type="radio" value="default" name="import-parameters[import_type]" id="import-parameters-import_type-default" <?php echo $import_params['import_type'] == 'default' ? 'checked="checked"' : ''; ?>></span>
			                                            <label for="import-parameters-import_type-default"><?php _e('Download images at import', $this->the_plugin->localizationName);?></label>
			                                             
			                                        </li>
																							
			                                        <li>
			                                            <br />
			                                            <span class="WooZoneLite-checked-product squaredThree">
			                                                <input type="checkbox" value="added" name="import-parameters[attributes]" id="import-parameters-attributes" <?php echo $import_params['import_attributes'] ? 'checked="checked"' : ''; ?>></span>
			                                            <label for="import-parameters-attributes"><?php _e('Import attributes', $this->the_plugin->localizationName);?></label>
			                                        </li>
			                                        <li>
			                                            <h4><?php _e('Import in', $this->the_plugin->localizationName);?></h4>
			                                            <?php echo $this->get_importin_category(); ?>
			                                        </li>
							            			<?php if (is_object($this->objAI)) { // auto import
														$this->objAI->print_auto_import_options(array('import_params' => $import_params));
													} ?>
			                                        <li class="WooZoneLite-import-products-button-box">
			                                        	<a href="#" id="WooZoneLite-import-products-button">
															<i class="fa fa-exclamation"></i>
															<?php _e('IMPORT PRODUCTS', $this->the_plugin->localizationName);?>
														</a>
			                                        </li>
			                                        <!--li>
			                                            <h4><?php _e('Run', $this->the_plugin->localizationName);?></h4>
			                                            <input type="button" value="<?php _e('IMPORT PRODUCTS', $this->the_plugin->localizationName);?>" id="WooZoneLite-import-products-button" class="WooZoneLite-button orange">
			                                        </li-->
			                                       <!--  premium version banner  -->
			                                        <li style="padding-left:10px; border-left:3px solid red; background-color:#f2f2f2;">
			                                            <h4><?php _e('The Lite Version is restricted, you can only import 2 images and 2 variations per product, if you wish to take advantage of WZone full features, please purchase it from <a href="https://codecanyon.net/item/woocommerce-amazon-affiliates-wordpress-plugin/3057503?ref=AA-Team">Codecanyon!</a>', $this->the_plugin->localizationName);?></h4>
			                                            
			                                        </li>
												</ul>
												
												<div class="WooZoneLite-insane-import-estimate">
			    		            				<div class="WooZoneLite-insane-import-ETA">
			    		            					<p>
			    		            						<?php _e('ESTIMATED TIME', $this->the_plugin->localizationName);?><br />
			    		            						<span><?php //_e('5 MINUTES', $this->the_plugin->localizationName);?></span>
			    		            					</p>		            				
			    		            				</div>
			    		            				<div class="WooZoneLite-insane-import-ETA-triangle"></div>	
			    		            				<div id="WooZoneLite-speedometer">
			    		            					<div class="speedometer-center">
			    		            						<div class="speedometer-center-middle">
			    		            							<canvas id="speedometer-markers" width="230" height="230"></canvas>
			    		            							<div id="speedometer-needle">
			    		            								<div class="speedometer-needle-center"></div>
			    		            							</div>
			    		            						</div>
			    		            						<span class="speedometer-step"></span>
			    			            					<span class="speedometer-step"></span>
			    			            					<span class="speedometer-step"></span>
			    			            					<span class="speedometer-step"></span>
			    			            					<span class="speedometer-step"></span>
			    		            					</div>
			    		            					
			    		            					<label id="WooZoneLite-speedometer-name"><i>5</i> <?php _e('Products per minute', $this->the_plugin->localizationName);?></label>
			    		            				</div>
			    		            				<?php
			    		            				/*
			                                        <input type="range" min="5" max="105" value="5" id="test-speedometer" step="10">
			                                        */
			                                        ?>
			                                        <div class="WooZoneLite-insane-import-ETA-logo WooZoneLite-insane-logo-level1">
			                                            <p><?php echo $lang['speed_level1']; ?></p>
			                                        </div>
			                                        
			                                    </div>
			                                    
					            			</div>
					            		</div>
									</div>
									
									<div class="WooZoneLite-insane-container WooZoneLite-insane-tabs WooZoneLite-insane-container-logs" id="WooZoneLite-logs-import-products">
			                    		<div class="WooZoneLite-insane-panel-headline">
			                    			<a href="#WooZoneLite-insane-importstatus" class="on">
			                    				<span><img src="<?php echo $this->module_folder;?>/images/text_logs.png" alt="logs"></span>
			                    				<?php _e('Import Log', $this->the_plugin->localizationName);?>
			                    			</a>
										</div>
										<div class="WooZoneLite-insane-tabs-content WooZoneLite-insane-status">
					            			<div id="WooZoneLite-insane-importstatus" class="WooZoneLite-insane-tab-content">
					            				<ul class="WooZoneLite-insane-logs">
					            					<?php /*<li class="WooZoneLite-log-notice">
					            						<i class="fa fa-info"></i>
					            						<span class="WooZoneLite-insane-logs-frame">Yesterday 10:24 PM</span>
					            						<p>You deleted the file intermediate-page_1000px_re…rid_02.jpg.</p>
					            					</li>
					            					<li class="WooZoneLite-log-error">
					            						<i class="fa fa-minus-circle"></i>
					            						<span class="WooZoneLite-insane-logs-frame">Yesterday 10:24 PM</span>
					            						<p>You deleted the file intermediate-page_1000px_re…rid_02.jpg.</p>
					            					</li>
					            					<li class="WooZoneLite-log-success">
					            						<i class="fa fa-check-circle"></i>
					            						<span class="WooZoneLite-insane-logs-frame">Yesterday 10:24 PM</span>
					            						<p>You deleted the file intermediate-page_1000px_re…rid_02.jpg.</p>
					            					</li>*/ ?>
					            				</ul>
					            			</div>
					            		</div>
									</div>
			
			                    <?php
			                    } // end moduleValidation
			                    ?>
								</div><!-- end Main Content Wrapper -->
							</div>
						<!-- </div> -->
						
<?php } // end demo keys ?>

						</section>
					<!-- </div> -->
				</div>
			</div>

	        <script type="text/javascript" src="<?php echo $this->module_folder;?>rangeslider/rangeslider.min.js"></script>
			<script type="text/javascript" src="<?php echo $this->module_folder;?>app.insane_import.js" ></script>
			
			<?php if (is_object($this->objAI)) { // auto import
				$this->objAI->load_asset('js');
			} ?>

<?php 
		}

        public function moduleValidation() {
            $ret = array(
                'status'            => false,
                'html'              => ''
            );
            
            // AccessKeyID, SecretAccessKey, AffiliateId, main_aff_id
            
            // find if user makes the setup
            $module_settings = $this->the_plugin->getAllSettings('array', 'amazon');

            $module_mandatoryFields = array(
                'AccessKeyID'           => false,
                'SecretAccessKey'       => false,
                'main_aff_id'           => false
            );
            if ( isset($module_settings['AccessKeyID']) && !empty($module_settings['AccessKeyID']) ) {
                $module_mandatoryFields['AccessKeyID'] = true;
            }
            if ( isset($module_settings['SecretAccessKey']) && !empty($module_settings['SecretAccessKey']) ) {
                $module_mandatoryFields['SecretAccessKey'] = true;
            }
            if ( isset($module_settings['main_aff_id']) && !empty($module_settings['main_aff_id']) ) {
                $module_mandatoryFields['main_aff_id'] = true;
            }
            $mandatoryValid = true;
            foreach ($module_mandatoryFields as $k=>$v) {
                if ( !$v ) {
                    $mandatoryValid = false;
                    break;
                }
            }
            
            $module_name = 'Insane Import Mode';
            if ( !$mandatoryValid ) {
                $error_number = 1; // from config.php / errors key
                
                $ret['html'] = $this->the_plugin->print_module_error( $this->module, $error_number, 'Error: Unable to use '.$module_name.' module, yet!' );
                return $ret;
            }
            
            if( !$this->the_plugin->is_woocommerce_installed() ) {  
                $error_number = 2; // from config.php / errors key
                
                $ret['html'] = $this->the_plugin->print_module_error( $this->module, $error_number, 'Error: Unable to use '.$module_name.' module, yet!' );
                return $ret;
            }
            
			$db_protocol_setting = isset($this->settings['protocol']) ? $this->settings['protocol'] : 'auto';
            if( ( !extension_loaded('soap') && !class_exists("SOAPClient") && !class_exists("SOAP_Client") )
				&& in_array($db_protocol_setting, array('soap')) ) {
                $error_number = 3; // from config.php / errors key
                
                $ret['html'] = $this->the_plugin->print_module_error( $this->module, $error_number, 'Error: Unable to use '.$module_name.' module, yet!' );
                return $ret;    
            }

			if( !(extension_loaded("curl") && function_exists('curl_init')) ) {  
                $error_number = 4; // from config.php / errors key
                
                $ret['html'] = $this->the_plugin->print_module_error( $this->module, $error_number, 'Error: Unable to use '.$module_name.' module, yet!' );
                return $ret;
            }
            
            $ret['status'] = true;
            return $ret;
        }

      
		
		public function providerSettingsValidation( $provider='amazon' ) 
		{
			$ret = array('status' => 'invalid', 'html' => '');

			$provider_status = $this->the_plugin->verify_mandatory_settings( $provider );
			$ret = array_merge($ret, array(
				'status'			=> $provider_status['status'],
				'html'				=> 'valid' == $provider_status['status'] ? 'ok'
					: '<div class="aiowaff-provider-validation-message">Error: Unable to import products from this provider: Setup the provider config mandatory settings ( ' . implode(', ', $provider_status['fields_title']) . ' ).</div>',
			));
			return $ret;
		}


        /**
         * Ajax requests
         */
		public function ajax_autocomplete()
		{
			$ret = array();
			$keyword = isset($_REQUEST['keyword']) ? $_REQUEST['keyword'] : '';
			if( trim($keyword) == "" ){
				$ret['status'] = 'invalid';
			}
			else{
				$response = wp_remote_get( 'http://completion.amazon.com/search/complete?method=completion&q=' . ( $keyword ) . '&search-alias=aps&client=amzn-search-suggestions/--&mkt=1' );
				if( is_array($response) && $response['headers']['content-type'] == 'text/javascript;charset=UTF-8' ) {
					$body = $response['body'];
					
					$array = json_decode( $body, true );
					// if found any results
					if( isset($array[1]) && count($array[1]) > 0 ){
						$array[1] = array_filter( $array[1] );
						if( count($array[1]) > 0 ){
							$ret['status'] = 'valid';
							$ret['data'] = $array[1]; 
						}
					}  
				}
			}
			
			
			die( json_encode( $ret ) ); 
		}
		
		public function ajax_request( $retType='die', $pms=array() )
		{
            $requestData = array(
                'action'             => isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : '',
                'operation'          => isset($_REQUEST['operation']) ? $_REQUEST['operation'] : '',
                'operation_id'       => isset($_REQUEST['operation_id']) ? $_REQUEST['operation_id'] : '',
                'msg_sep'		     => isset($_REQUEST['msg_sep']) ? $_REQUEST['msg_sep'] : '<br />',
            );
            extract($requestData);
            
            $ret = array(
                'status'        => 'invalid',
                'msg'           => '',
            );
            
            if ($action == 'heartbeat' ) {
                
                $opStatusMsg = $this->the_plugin->opStatusMsgGet( $msg_sep, 'file' );
                
                $_opStatusMsg = array(
                    'operation'         => isset($opStatusMsg['operation']) ? $opStatusMsg['operation'] : '',
                    'operation_id'      => isset($opStatusMsg['operation_id']) ? $opStatusMsg['operation_id'] : '',
                    'msg'               => isset($opStatusMsg['msg']) ? $opStatusMsg['msg'] : '',
                );
  
                if ( $operation_id != $_opStatusMsg['operation_id'] ) {
                    $_opStatusMsg['msg'] = '';
                }

                $ret = array_merge($ret, array(
                    'status'    => 'valid',
                    'msg'       => $_opStatusMsg['msg'],
                ));
            }
            
            if ( $retType == 'return' ) { return $ret; }
            else { die( json_encode( $ret ) ); }
		}
    
    
        /**
         * Ajax - Load Products
         */
        // ajax/ grab asins from amazon page url
        public function loadprods_grab_parse_url() {
            //$durationQueue = array(); // Duration Queue
            $this->the_plugin->timer_start(); // Start Timer

            $base = array(
                'status'        => 'invalid',
                'msg'           => '',
                'asins'         => array(),
            );
            
            $asins = array();
            $params = array();
            parse_str( $_REQUEST['params'], $params );
            
            $remote_url = $params['WooZoneLite-grab']['url'];
            $page_type = $params['WooZoneLite-grab']['page-type'];
            $operation_id = isset($_REQUEST['operation_id']) ? $_REQUEST['operation_id'] : '';
            
            // status messages
            $this->the_plugin->opStatusMsgInit(array(
                'operation_id'  => $operation_id,
                'operation'     => 'load_by_grab',
                'msg_header'    => __('Founding products from remote amazon url...', $this->the_plugin->localizationName),
            ));
            
			// https://www.amazon.com/Best-Sellers/zgbs
            if ( trim($remote_url) == "" ) {
                // status messages
                $this->the_plugin->opStatusMsgSet(array(
                    'msg'       => self::MSG_SEP . __(' Please provide a valid Amazon Url.', $this->the_plugin->localizationName),
                    'duration'  => $this->the_plugin->timer_end(), // End Timer
                ));
            } else {
                require_once( $this->the_plugin->cfg['paths']['scripts_dir_path'] . '/php-query/phpQuery.php' );
 
                $input = wp_remote_get( 
                    $remote_url, 
                    array( 'timeout' => 30 ) 
                );
                
                $response = wp_remote_retrieve_body( $input );
                $doc = WooZoneLitephpQuery::newDocument( $response );

                // Best Sellers page type
                if( $page_type == 'best sellers' ){
                    $container = $doc->find( '#zg_left_col1' );
                    
                    if (strpos($remote_url, 'ref=') !== false) {
                        //$products = $container->find(".zg_itemImmersion .zg_itemWrapper .zg_image");
                        $products = $container->find(".zg_itemImmersion .zg_itemWrapper"); 
                    } else {
                        //$products = $container->find(".zg_item .zg_image");
						$products = $container->find(".zg_item");
                    }
                    
                    if( (int)$products->size() > 0 ){
                        foreach ( $products as $product ) {
                            //$product_url = trim(WooZoneLitepq( $product )->find("a")->attr('href'));
							$product_url = trim(WooZoneLitepq( $product )->find("a.a-link-normal:first")->attr('href'));
                            if( $product_url != "" ){
								$product_url = @urldecode( $product_url );
								$__ = explode("/", $product_url );
								$__ = preg_replace('~\?.*~', '', $__);
								$asins[] = end( $__ );
                            }                   
                        } 
                    }
                }
                
                // Deals page type
                elseif( $page_type == 'deals' ){
                    $container = $doc->find( '#mainResults' );
                     
                    if ($container->find( ".prod" ) != "") {
                        $products = $container->find( ".prod" );
                    } else {
                            $products = $container->find( ".product" );
                        }

                    if( (int)$products->size() > 0 ){
                        foreach ( $products as $product ) {
                            $asin_item = WooZoneLitepq( $product )->attr('name');     
                            $asins[] = $asin_item;                  
                        } 
                    }
                }

                // Top Rated, Most Wished For, Movers & Shakers, Hot New Releases, Best Sellers Cattegory, Gift Ideas page type
                elseif( $page_type == 'top rated' || 'most wished for' || 'movers & shakers' || 'hot new releases' || 'best sellers cattegory' || 'gift ideas' ){
                    $container = $doc->find( '#zg_left_col1' );
  
                    if (strpos($remote_url, 'ref=') !== false) {
                        //$products = $container->find(".zg_itemImmersion .zg_itemWrapper .zg_image");
						$products = $container->find(".zg_itemImmersion .zg_itemWrapper");
                    } else {
                        //$products = $container->find(".zg_item .zg_image");
                        $products = $container->find(".zg_item");
                    }
                    if( (int)$products->size() > 0 ){
                        foreach ( $products as $product ) {
                            //$product_url = trim(WooZoneLitepq( $product )->find("a")->attr('href'));
							$product_url = trim(WooZoneLitepq( $product )->find("a.a-link-normal:first")->attr('href'));
                            if( $product_url != "" ){
								$product_url = @urldecode( $product_url );
								$__ = explode("/", $product_url );
								$__ = preg_replace('~\?.*~', '', $__);
								$asins[] = end( $__ );
                            }                   
                        } 
                    }
                }



                // New Arrivals page type
                if( $page_type == 'new arrivals' ){
                    $container = $doc->find( '#resultsCol' );
                    
                    $products = $container->find(".prod .image");
                    if( (int)$products->size() > 0 ){
                        foreach ( $products as $product ) {
                            $product_url = trim(WooZoneLitepq( $product )->find("a")->attr('href'));
                            if( $product_url != "" ){
								$product_url = @urldecode( $product_url );
								$__ = explode("/", $product_url );
								$asins[] = end( $__ );
                            }                   
                        } 
                    }
                }
                
                // removes duplicate values
                $asins = array_unique($asins);

                if ( !empty($asins) ) {

                    $base = array_merge($base, array(
                        'status'    => 'valid',
                        'asins'     => $asins,
                    ));

                    // status messages
                    $this->the_plugin->opStatusMsgSet(array(
                        'status'    => 'valid',
                        'msg'       => self::MSG_SEP . sprintf( __(' The script was successfully. %s ASINs found: %s', $this->the_plugin->localizationName), count($base['asins']), implode(', ', $base['asins']) ),
                        'duration'  => $this->the_plugin->timer_end(), // End Timer
                    ));

                } else {
                    // status messages
                    $this->the_plugin->opStatusMsgSet(array(
                        'msg'       => self::MSG_SEP . __(' The script was unable to grab any ASIN codes. Please try again using another Page Type parameter.', $this->the_plugin->localizationName),
                        'duration'  => $this->the_plugin->timer_end(), // End Timer
                    ));
                }
            }

            $opStatusMsg = $this->the_plugin->opStatusMsgGet();
            $base['msg'] = $opStatusMsg['msg'];
            
            die( json_encode( $base ) );
        }

        // ajax/ load products in queue based on ASINs list
        public function loadprods_queue_by_asin( $retType='die', $pms=array() ) {

            $durationQueue = array(); // Duration Queue
            $this->the_plugin->timer_start(); // Start Timer
            
            //$amz_setup = $this->the_plugin->getAllSettings('array', 'amazon');
            $amz_setup = $this->settings;
            $do_parent_setting = !isset($amz_setup['variation_force_parent'])
                || ( isset($amz_setup['variation_force_parent']) && $amz_setup['variation_force_parent'] != 'no' )
                ? true : false;

            $requestData = array(
                'operation'             => isset($_REQUEST['operation']) ? $_REQUEST['operation'] : '',
                'asins'                 => isset($_REQUEST['asins']) ? (array) $_REQUEST['asins'] : array(),
                'asins_inqueue'         => isset($_REQUEST['asins_inqueue']) ? (array) $_REQUEST['asins_inqueue'] : array(),
                'page'                  => isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 0,
                'operation_id'          => isset($_REQUEST['operation_id']) ? $_REQUEST['operation_id'] : '',
                'provider'				=> 'amazon',
            );
            foreach ($requestData as $rk => $rv) {
                if ( isset($pms["$rk"]) ) {
                    $new_val = $pms["$rk"];
                    $new_val = in_array($rk, array('asins', 'asins_inqueue')) ? (array) $new_val : $new_val;
                    $requestData["$rk"] = $new_val;
                }
            }
  
            $requestData['asins'] = array_unique( $requestData['asins'] );
            $requestData['asins_inqueue'] = array_unique( $requestData['asins_inqueue'] );
            extract($requestData);
			//$provider = $requestData['provider'];
            
            $prods = array();
            $ret = array(
                'status'        => 'invalid',
                'nb_amz_req'    => 0, // number of amazon requests
                'asins'         => array(
                    'found'             => array(), // found no matter if valid or not
                    'remained'          => $asins, // asins remained to be parsed in future requests 
                    'inqueue'           => array(), // already in queue
                    'loaded'            => array(), // valid & will be loaded in queue
                    'invalid'           => array(), // invalid & will NOT be loaded
                    'imported'          => array(), // already imported
                    'variations'        => array(), // variations: child -> parent

                    'from_cache'        => array(), // get from cache files
                    'from_amz'          => array(), // get straight from amazon request
                ),
                'msg'           => '',
                'duration'      => 0,
            );
            $ret['asins']['inqueue'] = $asins_inqueue;
            
            if ( $operation != 'search' ) {
                // status messages
                $this->the_plugin->opStatusMsgInit(array(
                    'operation_id'  => $requestData['operation_id'],
                    'operation'     => 'load_by_asin',
                    'msg_header'    => __('Loading products by ASIN...', $this->the_plugin->localizationName),
                ));
            }
            
            if ( $operation != 'search' ) {
                // status messages
                $this->the_plugin->opStatusMsgSet(array(
                    'status'    => 'valid',
                    'msg'       => self::MSG_SEP . ' <u><strong>' . strtoupper($operation) . '</strong> operation.</u>',
                ));
            }
            // status messages
            $this->the_plugin->opStatusMsgSet(array(
                'status'    => 'valid',
                'msg'       => self::MSG_SEP . ' <strong>Page '.$page.'</strong>: try to retrieve Products Data.',
            ));

            if ( empty($asins) || !is_array($asins) ) {
                $tmp_msg = __('No ASINs provided!', $this->the_plugin->localizationName);

                $duration = $this->the_plugin->timer_end(); // End Timer
                // status messages
                $this->the_plugin->opStatusMsgSet(array(
                    'msg'       => $tmp_msg,
                    'duration'  => $duration,
                ));
                
                $ret['msg'] = $tmp_msg;
                $ret['duration'] = $duration;

                if ( $retType == 'return' ) { return $ret; }
                else { die( json_encode( $ret ) ); }
            }
            
            
            // already in queue
            $all_inqueue = $asins_inqueue;
            $here_inqueue = array_values( array_intersect($asins, $asins_inqueue) );
            $ret['asins']['inqueue'] = $here_inqueue;
            
            $asins = array_values( array_diff($asins, $asins_inqueue) );
            $ret['asins']['remained'] = $asins;
            

            // already imported
            $all_already_imported = $this->get_products_already_imported();
            $already_imported = array_values( array_intersect($asins, $all_already_imported) );
            $ret['asins']['imported'] = $already_imported;
            
            $asins = array_values( array_diff($asins, $all_already_imported) );
            $ret['asins']['remained'] = $asins;


            // from cache
            //foreach ($asins as $key => $asin) {
            $len = count($asins); $cc = 0;
            while ( $cc < $len ) {
                $key = $cc; $asin = $asins["$key"];

                $__cachePms = array(
                	'provider'			=> $provider,
                    'cache_type'        => 'prods',
                    'asin'              => $asin,
                );
      
                $__cache = $this->getTheCache( $__cachePms );
                $__cachePage = ( $__cache !== false ? $__cache : array() );

                // cache is found!
                if ( self::$CACHE_ENABLED['prods'] && !empty($__cachePage)
					&& $this->get_ws_object( $provider )->is_valid_product_data($__cachePage) ) {
                    $product = $__cachePage;
                    $product_asin = $asin;
                    $parent_asin = $__cachePage['ParentASIN'];
  
                    // remove from the list for amazon request!
                    unset($asins["$key"]);
  
                    $ret['asins']['from_cache'][] = $product_asin;
                    
                    // product or parent already parsed
                    $already_parsed = array_merge_recursive($ret['asins'], array(
                        'all_inqueue'               => $all_inqueue,
                        'all_already_imported'      => $all_already_imported,
                    ));
 
                    $inqueue_product = $this->already_parsed_asin($already_parsed, $product_asin);
                    $inqueue_parent = $this->already_parsed_asin($already_parsed, $parent_asin);

                    // product is a variation child => try to find parent variation
                    if ( $do_parent_setting && !empty($parent_asin) && ( $product_asin != $parent_asin ) ) {

                        if ( !$inqueue_parent ) {
                            if ( !in_array($parent_asin, $asins) ) {
                                $asins[] = $parent_asin;
                                $len++;
                            }
                        }
                        else {
                            $ret['asins']['inqueue'][] = $parent_asin;
                        }
                        $ret['asins']['invalid'][] = $product_asin;
                        $ret['asins']['variations']["$product_asin"] = $parent_asin;
                    } else {
                            
                        if ( !$inqueue_product ) {
                            $ret['asins']['loaded'][] = $product_asin;
                            $prods["$product_asin"] = $product;
                        }
                        else {
                            $ret['asins']['inqueue'][] = $product_asin;
                            if ( ($key = array_search($product_asin, $asins)) !== false ) {
                                unset($asins["$key"]);
                                $asins = array_values($asins);
                            }
                        }
                    }
                }
                ++$cc;
            }
   
            $asins = array_values($asins);
            $ret['asins']['remained'] = $asins;
 
            // from amazon request!
            if ( !empty($asins) ) {
                $ret['asins']['remained'] = array_values( array_slice($asins, self::$LOAD_MAX_LIMIT["$provider"]) );
                $asins = array_values( array_slice($asins, 0, self::$LOAD_MAX_LIMIT["$provider"]) );

                $hasErr = (object) array('amazon' => false, 'amazon_loop' => false);

                try {
                    ++$ret['nb_amz_req'];
                    $hasErr->amazon = false;

					/*
                    $this->get_ws_object( $provider, 'ws' )
                    ->responseGroup('Large,ItemAttributes,OfferFull,Offers,Variations,Reviews,PromotionSummary,SalesRank')
                    ->optionalParameters(array('MerchantId' => 'All'));
                    $response = $this->get_ws_object( $provider, 'ws' )
                    ->lookup( implode(",", $asins) );
                    //var_dump('<pre>',$response,'</pre>'); die;
					*/
					$rsp = $this->get_ws_object( $provider )->api_search_byasin(array(
						'amz_settings'			=> $this->the_plugin->amz_settings,
						'from_file'				=> str_replace($this->the_plugin->cfg['paths']['plugin_dir_path'], '', __FILE__),
						'from_func'				=> __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
						'asins'					=> $asins,
					));
					
                    // status messages
                    if ( isset($rsp['req_link']) ) {
	                    $this->the_plugin->opStatusMsgSet(array(
	                        'status'    => 'valid',
	                        'msg'       => self::MSG_SEP . ' <a href="' . $rsp['req_link'] . '" target="_blank">amazon request link</a>',
	                    ));
                    }

					$response = $rsp['response'];
 
					$this->inc_nbreq($provider, 'search_byasin'); // increase number of requests made!

					$respStatus = $this->get_ws_object( $provider )->is_amazon_valid_response( $response );
                    if ( $respStatus['status'] != 'valid' ) { // error occured!

                        $duration = $this->the_plugin->timer_end(); // End Timer
                        $durationQueue[] = $duration; // End Timer
                        $this->the_plugin->timer_start(); // Start Timer
                            
                        // status messages
                        $this->the_plugin->opStatusMsgSet(array(
                            'status'    => 'invalid',
                            'msg'       => 'Invalid ' . $provider . ' response ( ' . $respStatus['code'] . ' - ' . $respStatus['msg'] . ' )',
                            'duration'  => $duration,
                        ));
                        
                        $hasErr->amazon = true;
                        $hasErr->amazon_loop = true;
                    } else { // success!

                        $duration = $this->the_plugin->timer_end(); // End Timer
                        $durationQueue[] = $duration; // End Timer
                        $this->the_plugin->timer_start(); // Start Timer
                            
                        // status messages
                        $this->the_plugin->opStatusMsgSet(array(
                            'status'    => 'valid',
                            'msg'       => 'Valid ' . $provider . ' response',
                            'duration'  => $duration,
                        ));

                        /*
                        if ( isset($response['Items']['Item']['ASIN']) ) {
                            $response['Items']['Item'] = array( $response['Items']['Item'] );
                        }
						*/
						$rsp = $this->get_ws_object( $provider )->api_format_results(array(
							'requestData'			=> $requestData,
							'response'				=> $response,
						));
						$requestData = $rsp['requestData'];

						foreach ( $rsp['response'] as $key => $value){

							$product = $this->build_product_data( $value, array(), $provider );
                            $product_asin = $product['ASIN'];
                            $parent_asin = $product['ParentASIN'];

							// join the first cache from search with the cache from details ( if provider needs it this way! )
							if ( isset($product['__isfrom']) && ('details' == $product['__isfrom']) ) {
	                            $__cachePms = array(
	                            	'provider'			=> $provider,
	                                'cache_type'        => 'prods',
	                                'asin'              => $product_asin,
	                            );
	      
	                			$__cache = $this->getTheCache( $__cachePms );
	                			$__cachePage = ( $__cache !== false ? $__cache : array() );
 
	                			// cache is found!
	                			if ( self::$CACHE_ENABLED['prods'] && !empty($__cachePage)
	                    			&& $this->get_ws_object( $provider )->is_valid_product_data($__cachePage, 'search') ) {

                					//$product = array_replace_recursive($__cachePage, $product);
									$product = $this->build_product_data( $value, $__cachePage, $provider );
								}
							}

                            $ret['asins']['from_amz'][] = $product_asin;

                            // product or parent already parsed
                            $already_parsed = array_merge_recursive($ret['asins'], array(
                                'all_inqueue'               => $all_inqueue,
                                'all_already_imported'      => $all_already_imported,
                            ));
                            $inqueue_product = $this->already_parsed_asin($already_parsed, $product_asin);
                            $inqueue_parent = $this->already_parsed_asin($already_parsed, $parent_asin);

                            // product is a variation child => try to find parent variation
                            if ( $do_parent_setting && !empty($parent_asin) && ( $product_asin != $parent_asin ) ) {

                                if ( !$inqueue_parent ) {
                                    if ( !in_array($parent_asin, $ret['asins']['remained']) ) {
                                        $ret['asins']['remained'][] = $parent_asin;
                                    }
                                }
                                else {
                                    $ret['asins']['inqueue'][] = $parent_asin;
                                }
                                $ret['asins']['invalid'][] = $product_asin;
                                $ret['asins']['variations']["$product_asin"] = $parent_asin;
                            } else {
                                    
                                if ( !$inqueue_product ) {
                                    $ret['asins']['loaded'][] = $product_asin;
                                    $prods["$product_asin"] = $product;
                                }
                                else {
                                    $ret['asins']['inqueue'][] = $product_asin;
                                    if ( ($key = array_search($product_asin, $asins)) !== false ) {
                                        unset($asins["$key"]);
                                        $asins = array_values($asins);
                                    }
                                }
                            }
                            
                            // set cache
                            $__cachePms = array(
                            	'provider'			=> $provider,
                                'cache_type'        => 'prods',
                                'asin'              => $product_asin,
                            );
                            $this->setTheCache( $__cachePms, $product );
                        }
                    }
                    // go to [success] label
                    //...

                } catch (Exception $e) {
                    // Check 
                    if (isset($e->faultcode)) { // error occured!

                        //ob_start();
                        //var_dump('<pre>', 'Invalid ' . $provider . ' response (exception)', $e,'</pre>');
                        
                        $duration = $this->the_plugin->timer_end(); // End Timer
                        $durationQueue[] = $duration; // End Timer
                        $this->the_plugin->timer_start(); // Start Timer

                        //$__msg = ob_get_clean();
                        $__msg = $e->faultcode .  ' : ' . (isset($e->faultstring) ? $e->faultstring : $e->getMessage());

                        // status messages
                        $this->the_plugin->opStatusMsgSet(array(
                            'status'    => 'invalid',
                            'msg'       => $__msg,
                            'duration'  => $duration,
                        ));
                        
                        $asins = array_values($asins);
                        $hasErr->amazon = true;
                        $hasErr->amazon_loop = true;
                    }
                }
            }

            if ( $operation != 'search' ) {
                // status messages
                $this->the_plugin->opStatusMsgSet(array(
                    'status'    => 'valid',
                    'msg'       => sprintf( 'Number of ' . $provider . ' Requests: %s', $ret['nb_amz_req'] ),
                ));
            }

            $invalid_prods = array_values( array_diff($asins, $ret['asins']['loaded']) );
            $ret['asins']['invalid'] = array_merge($ret['asins']['invalid'], $invalid_prods);
            
            $from_amz = array_values( array_diff($asins, $ret['asins']['from_cache']) );
            $ret['asins']['from_amz'] = array_merge($ret['asins']['from_amz'], $from_amz);
            
            // make unique
            foreach ($ret['asins'] as $atype => $avalue) {
                if ( !in_array($atype, array('variations')) ) {
                    $ret['asins']["$atype"] = array_unique( $avalue );
                }
            }

            // amazon request was made
            if ( isset($hasErr->amazon) ) {
                // error occured on amazon request
                if ( $hasErr->amazon ) {}
                // [success] label
                else {}
            }
            else {
            }

            $duration = $this->the_plugin->timer_end(); // End Timer
            $durationQueue[] = $duration; // End Timer
            $duration = round( array_sum($durationQueue), 4 ); // End Timer
            
            // status messages
            $this->the_plugin->opStatusMsgSet(array(
                'status'    => 'valid',
                'msg'       => $this->loadprods_set_msg( $ret ),
                'duration'  => $duration,
                'end'       => true,
            ));
            
            $opStatusMsg = $this->the_plugin->opStatusMsgGet();

            if ( empty($ret['asins']['invalid']) && empty($ret['asins']['imported']) && empty($ret['asins']['inqueue']) ) {
                $ret['status'] = 'valid';
            }
            
            // build html
			$ret['html'] = $this->loadprods_build_html( $prods, $provider );
            $ret['duration'] = $duration;
            
            $ret = array_merge($ret, array('msg' => $opStatusMsg['msg']));
            if ( $retType == 'return' ) { return $ret; }
            else { die( json_encode( $ret ) ); }
        }

        // ajax/ load products in queue based on Search
        public function loadprods_queue_by_search( $retType='die', $pms=array() ) {

            $durationQueue = array(); // Duration Queue
            $this->the_plugin->timer_start(); // Start Timer
            
            //params['WooZoneLite-search']: category, keyword, nbpages, node, search_on
            $requestData = array(
                //'use_categ_field'       => isset($_REQUEST['use_categ_field']) ? $_REQUEST['use_categ_field'] : 'category',
                'operation'             => isset($_REQUEST['operation']) ? $_REQUEST['operation'] : '',
                'asins_inqueue'         => isset($_REQUEST['asins_inqueue']) ? trim($_REQUEST['asins_inqueue']) : '',
                'params'                => isset($_REQUEST['params']) ? $_REQUEST['params'] : '',
                'page'                  => isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 0,
                'operation_id'          => isset($_REQUEST['operation_id']) ? $_REQUEST['operation_id'] : '',
                'provider'				=> 'amazon',
            );
            if ( !empty($requestData['asins_inqueue']) && substr_count($requestData['asins_inqueue'], ',') ) {
                $requestData['asins_inqueue'] = explode(',', $requestData['asins_inqueue']);
            } else {
                $requestData['asins_inqueue'] = array();
            }
            $requestData['asins_inqueue'] = array_unique($requestData['asins_inqueue']);
            
            $params = array();
            parse_str( ( $requestData['params'] ), $params);
        
            if( isset($params['WooZoneLite-search'])) {
                $requestData = array_merge($requestData, $params['WooZoneLite-search']);
            }
            //foreach ($requestData as $rk => $rv) {
            //    if ( isset($pms["$rk"]) ) {
            //        $new_val = $pms["$rk"];
            //        $new_val = in_array($rk, array()) ? (array) $new_val : $new_val;
            //        $requestData["$rk"] = $new_val;
            //    }
            //}
			foreach ($pms as $rk => $rv) {
				$new_val = $rv;
				$new_val = in_array($rk, array()) ? (array) $new_val : $new_val;
				$requestData["$rk"] = $new_val;
			}
            //foreach ($requestData as $key => $val) {
            //    if ( strpos($key, '-') !== false ) {
            //        $_key = str_replace('-', '_', $key); 
            //        $requestData["$_key"] = $val;
            //        unset($requestData["$key"]);
            //    }
            //}
            $provider = $requestData['provider'];
            
			if ( 'amazon' == $provider && (!isset($requestData['category']) || empty($requestData['category'])) ) {
                $requestData['category'] = 'AllCategories';
            }
			$max_nbpages = isset($requestData['category']) && ($requestData['category'] == 'AllCategories') ? 5 : 10;
            if ( !isset($requestData['nbpages']) || $requestData['nbpages'] < 1 || $requestData['nbpages'] > $max_nbpages ) {
                $requestData['nbpages'] = 1;
            }
			if ( !isset($requestData['page']) || empty($requestData['page']) ) {
				$requestData['page'] = 0;
			}
            //var_dump('<pre>', $requestData, '</pre>'); die('debug...');
            
            // status messages
            $this->the_plugin->opStatusMsgInit(array(
                'operation_id'  => $requestData['operation_id'],
                'operation'     => 'load_by_search',
                'msg_header'    => __('Loading products by Searching...', $this->the_plugin->localizationName),
            ));
            
            $parameters = array();
			if ( isset($requestData['keyword']) && !empty($requestData['keyword']) ) {
				$parameters['keyword'] = $requestData['keyword'];
			}
			if ( isset($requestData['category']) && !empty($requestData['category']) && ( !isset($requestData['node']) || $requestData['node'] == '' ) ) {
				$parameters['category'] = $requestData['category'];
			}
			if( isset($requestData['node']) || !empty($requestData['node']) ) {
				$parameters['node'] = $requestData['node'];
			}
			//if ( isset($requestData['site']) && !empty($requestData['site']) ) {
			//	$parameters['site'] = $requestData['site'];
			//}
			//if ( isset($requestData['term']) && !empty($requestData['term']) ) {
			//	$parameters['term'] = $requestData['term'];
			//}
			if ( isset($requestData['search_type']) && !empty($requestData['search_type']) ) {
				$parameters['search_type'] = $requestData['search_type'];
			}
			if ( isset($requestData['nbpages']) && !empty($requestData['nbpages']) ) {
				$parameters['nbpages'] = (int) $requestData['nbpages'];
			}
            if ( isset($requestData['page']) && !empty($requestData['page']) ) {
                $parameters = array_merge($parameters, array(
                    'page'          => $requestData['page'],
                    'nbpages'		=> 1, // when you choose a specific page, number of pages is alwasy 1
                ));
            }

            // option parameters
            $_optionalParameters = array();
			$optionalParameters = array_keys( self::$optionalParameters["$provider"] );
            if( count($optionalParameters) > 0 ){
                foreach ($optionalParameters as $oparam){
                    if ( isset($requestData["$oparam"]) ) {
                        $_optionalParameters["$oparam"] = $requestData["$oparam"];
						$_optionalParameters["$oparam"] = trim( $_optionalParameters["$oparam"] );
                    }
                }
            }
            // if node is send, chain to request
            //if( isset($requestData['node']) && trim($requestData['node']) != "" ){
            //    $_optionalParameters['BrowseNode'] = $requestData['node'];
            //}
            if ( 'amazon' == $provider && !in_array('MerchantId', array_keys($_optionalParameters)) ) {
				$merchant_setup = (isset($this->settings["merchant_setup"]) && $this->settings["merchant_setup"] == 'only_amazon' ? 'only_amazon' : 'amazon_or_sellers');
				$_optionalParameters['MerchantId'] = ('only_amazon' == $merchant_setup ? 'Amazon' : 'All');
            }
            // clear the empty array
            $_optionalParameters = array_filter($_optionalParameters);
            //var_dump('<pre>', $_optionalParameters, '</pre>'); die('debug...'); 

            // cache
            $__cacheSearchPms = array(
            	'provider'			=> $provider,
                'cache_type'        => 'search',
                'params1'           => $parameters,
                'params2'           => $_optionalParameters,
                'requestData'		=> $requestData,
            );
      
            $__cacheSearch = $this->getTheCache( $__cacheSearchPms );
            $__cacheSearchPage = ( $__cacheSearch !== false ? $__cacheSearch : array() );

            $searchResults = array();
            $ret = array(
                'status'        => 'invalid',
                'nb_amz_req'    => 0, // number of amazon requests
                'msg'           => '',
            );

            //$__searchPmsMsg = implode(', ', array_map(array($this->the_plugin, 'prepareForPairView'), $parameters, array_keys($parameters)));
            $__searchPmsMsg = http_build_query( $this->__search_nice_params( array_merge($parameters, $_optionalParameters), $provider ), '', ', ' );
            
            // status messages
            $this->the_plugin->opStatusMsgSet(array(
                'status'    => 'valid',
                'msg'       => self::MSG_SEP . ' <u><strong>Search Products</strong> operation: try to retrieve results.</u>',
            ));
            // status messages
            $this->the_plugin->opStatusMsgSet(array(
                'status'    => 'valid',
                'msg'       => 'Search Parameters: ' . $__searchPmsMsg,
            ));

            // cache is found!
            if ( self::$CACHE_ENABLED['search'] && !empty($__cacheSearchPage) ) {
                
                $__writeCache['dataToSave'] = $__cacheSearchPage;
                
                $duration = $this->the_plugin->timer_end(); // End Timer
                $durationQueue[] = $duration; // End Timer
                $this->the_plugin->timer_start(); // Start Timer
                
                // status messages
                $this->the_plugin->opStatusMsgSet(array(
                    'status'    => 'valid',
                    'msg'       => self::MSG_SEP . ' Search results returned from Cache',
                    'duration'  => $duration,
                ));

            }
			// cache NOT found!
			else {
 
                $duration = $this->the_plugin->timer_end(); // End Timer
                $durationQueue[] = $duration; // End Timer
                $this->the_plugin->timer_start(); // Start Timer

                // status messages
                $this->the_plugin->opStatusMsgSet(array(
                    'status'    => 'valid',
                    'msg'       => self::MSG_SEP . ' Search results - try to retrieve from ' . $provider,
                ));

                // already imported
                $all_already_imported = $this->get_products_already_imported();
            
                $hasErr = (object) array('cache' => false, 'amazon' => false, 'amazon_loop' => false, 'stop_loop' => false );
                $__writeCache = array('dataToSave' => array());

                $cc = 1; $max = 10;
                // Begin Loop
                do {

                    $page = $cc;
                    if ( isset($requestData['page']) && !empty($requestData['page']) ) {
                        $page = $requestData['page'];
                    }

                    // status messages
                    $this->the_plugin->opStatusMsgSet(array(
                        'status'    => 'valid',
                        'msg'       => self::MSG_SEP . ' <strong>Page '.$page.'</strong>.',
                    ));
    
                    try {
                        ++$ret['nb_amz_req'];
                        $hasErr->amazon = false;

                        /*
						$this->get_ws_object( $provider, 'ws' )
                        ->category( ( $parameters['category'] == 'AllCategories' ? 'All' : $parameters['category'] ) )
                        ->page( $page )
                        ->responseGroup('Large,ItemAttributes,OfferFull,Offers,Variations,Reviews,PromotionSummary,SalesRank');
     
                        // set the page
                        $_optionalParameters['ItemPage'] = $page;
                    
                        if( count($_optionalParameters) > 0 ){
                            // add optional parameter to query
                            $this->get_ws_object( $provider, 'ws' )
                            ->optionalParameters( $_optionalParameters );
                        }
                        //var_dump('<pre>',$this->get_ws_object( $provider, 'ws' ),'</pre>');  
                
                        // add the search keywords
                        $response = $this->get_ws_object( $provider, 'ws' )
                        ->search( isset($parameters['keyword']) ? $parameters['keyword'] : '' );
                        //var_dump('<pre>',$response,'</pre>'); die;
    
                        //$__asinsDebug = array();
                        //foreach ( $response['Items']['Item'] as $item_key => $item_val ) {
                        //    $__asinsDebug[] = $item_val['ASIN'];
                        //}
                        //var_dump('<pre>',$__asinsDebug,'</pre>');
						*/
						$rsp = $this->get_ws_object( $provider )->api_search_bypages(array(
							'amz_settings'			=> $this->the_plugin->amz_settings,
							'from_file'				=> str_replace($this->the_plugin->cfg['paths']['plugin_dir_path'], '', __FILE__),
							'from_func'				=> __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
							'requestData'			=> $requestData,
							'parameters'			=> $parameters,
							'_optionalParameters'	=> $_optionalParameters,
							'page'					=> $page,
						));
						
	                    // status messages
	                    if ( isset($rsp['req_link']) ) {
		                    $this->the_plugin->opStatusMsgSet(array(
		                        'status'    => 'valid',
		                        'msg'       => self::MSG_SEP . ' <a href="' . $rsp['req_link'] . '" target="_blank">amazon request link</a>',
		                    ));
						}
						
						$response = $rsp['response'];
						
						$this->inc_nbreq($provider, 'search_bypages'); // increase number of requests made!
                        
                        $respStatus = $this->get_ws_object( $provider )->is_amazon_valid_response(
                        	$response,
                        	isset($requestData['search_type']) && !empty($requestData['search_type'])
								? $requestData['search_type'] : 'search'
						);
                        if ( $respStatus['status'] != 'valid' ) { // error occured!
    
                            $duration = $this->the_plugin->timer_end(); // End Timer
                            $durationQueue[] = $duration; // End Timer
                            $this->the_plugin->timer_start(); // Start Timer
                            
                            // status messages
                            $this->the_plugin->opStatusMsgSet(array(
                                'status'    => 'invalid',
                                'msg'       => 'Invalid ' . $provider . ' response ( ' . $respStatus['code'] . ' - ' . $respStatus['msg'] . ' )',
                                'duration'  => $duration,
                            ));
    
                            $hasErr->amazon = true;
                            $hasErr->amazon_loop = true;
                            if ( 3 == $respStatus['code'] || $page == 1
                            	|| ( isset($requestData['page']) && !empty($requestData['page']) ) ) { // no search results
                                $hasErr->stop_loop = true;
                            }
                        } else { // success!
    
                            $duration = $this->the_plugin->timer_end(); // End Timer
                            $durationQueue[] = $duration; // End Timer
                            $this->the_plugin->timer_start(); // Start Timer
                            
                            // status messages
                            $this->the_plugin->opStatusMsgSet(array(
                                'status'    => 'valid',
                                'msg'       => 'Valid ' . $provider . ' response',
                                'duration'  => $duration,
                            ));

							/*
                            if ( isset($response['Items']['TotalPages'])
                                && (int) $response['Items']['TotalPages'] < $requestData['nbpages'] ) {
                                $requestData['nbpages'] = (int) $response['Items']['TotalPages'];
                                // don't put this validated nbpages in $__cacheSearchPms, because the cache file could not be recognized then!
                            }
        
                            // verify array of Items or array of Item elements
                            if ( isset($response['Items']['Item']['ASIN']) ) {
                                $response['Items']['Item'] = array( $response['Items']['Item'] );
                            }
							*/
							$rsp = $this->get_ws_object( $provider )->api_format_results(array(
								'requestData'			=> $requestData,
								'response'				=> $response,
							));
							$requestData = $rsp['requestData'];
       
                            foreach ( $rsp['response'] as $key => $value){
        
                                $product = $this->build_product_data( $value, array(), $provider );
                                $product_asin = $product['ASIN'];

                                if ( !in_array(
                                	$product_asin,
                                	$all_already_imported
								) ) {
    
                                    $__cachePms = array(
                                    	'provider'			=> $provider,
                                        'cache_type'        => 'prods',
                                        'asin'              => $product_asin,
                                    );
            
                                    $__cache = $this->getTheCache( $__cachePms );
                                    $__cachePage = ( $__cache !== false ? $__cache : array() );
            
                                    // cache is found!
                                    if ( self::$CACHE_ENABLED['prods'] && !empty($__cachePage)
										&& $this->get_ws_object( $provider )->is_valid_product_data($__cachePage) ) ;
                                    else {
                                        $this->setTheCache( $__cachePms, $product );
                                    }
                                }
                            }
                        }
                        // go to [success] label
                        //...
    
                    } catch (Exception $e) {
                        // Check 
                        if (isset($e->faultcode)) { // error occured!
    
                            //ob_start();
                            //var_dump('<pre>', 'Invalid ' . $provider . ' response (exception)', $e,'</pre>');
                            
                            $duration = $this->the_plugin->timer_end(); // End Timer
                            $durationQueue[] = $duration; // End Timer
                            $this->the_plugin->timer_start(); // Start Timer
                            
                            //$__msg = ob_get_clean();
                            $__msg = $e->faultcode .  ' : ' . (isset($e->faultstring) ? $e->faultstring : $e->getMessage());
                            
                            // status messages
                            $this->the_plugin->opStatusMsgSet(array(
                                'status'    => 'invalid',
                                'msg'       => $__msg,
                                'duration'  => $duration,
                            ));
    
                            $hasErr->amazon = true;
                            $hasErr->amazon_loop = true;
                        }
                    }
    
                    ++$cc;
					
                    // [success] label
                    // here we build the results array using the setTheCache method!
                    $__cacheSearchPms = array_merge($__cacheSearchPms, array(
                        'page'              => $page,
                    ));
                    if ( !$hasErr->amazon ) {
                        $__writeCache = $this->setTheCache( $__cacheSearchPms, $response, $__writeCache['dataToSave'], false );
    
                        // we'll write the cache only if errors didn't occured on any page step                  
                        if ( !$hasErr->cache
                            && ( $__writeCache === false || !isset($__writeCache['dataToSave']) || empty($__writeCache['dataToSave']) )
                        ) {
                            $hasErr->cache = true;
                        }
                        
                        // status messages
                        $this->the_plugin->opStatusMsgSet(array(
                            'status'    => 'valid',
                            'msg'       => 'Page results retrieved successfully from ' . $provider,
                        ));
                    }
                
                } while ($cc <= $requestData['nbpages'] && $cc <= $max && !$hasErr->stop_loop );
                // End Loop
                
                // error occured during caching or on one amazon request => delete current wrote cache if found
                if ( $hasErr->cache || $hasErr->amazon_loop ) {
                    $this->deleteTheCache( $__cacheSearchPms );
                    $tmp_msg = self::MSG_SEP . ' Search results could not be wrote in cache file!';
                }
                // wrote cache
                else {
                    $this->setTheCache( $__cacheSearchPms, array('__notused__' => true), $__writeCache['dataToSave'], true );
                    $tmp_msg = self::MSG_SEP . ' Search results successfully wrote in cache file.';
                }
                
                $duration = $this->the_plugin->timer_end(); // End Timer
                $durationQueue[] = $duration; // End Timer
                $this->the_plugin->timer_start(); // Start Timer

                // status messages
                $this->the_plugin->opStatusMsgSet(array(
                    'status'    => 'valid',
                    'msg'       => $tmp_msg,
                    'duration'  => $duration,
                ));
            } // end cache NOT found!

            //var_dump('<pre>', $__writeCache['dataToSave'], '</pre>'); die('debug...'); 
            $results = $__writeCache['dataToSave'];

            // amazon should returned a valid reponse & at least one page
			$rsp = $this->get_ws_object( $provider )->api_search_validation(array(
				'results'				=> $results,
			));
			$nbpages = $rsp['nbpages'];
            //if ( !isset($results['Items'], $results['Items']['TotalResults'], $results['Items']['NbPagesSelected'])
            //    || count($results) < 2 ) {
			if ( !$rsp['status'] ) {
                $duration = $this->the_plugin->timer_end(); // End Timer
                $durationQueue[] = $duration; // End Timer
                $duration = round( array_sum($durationQueue), 4 ); // End Timer
                
                // status messages
                $this->the_plugin->opStatusMsgSet(array(
                    'status'    => 'invalid',
                    'msg'       => 'Unsuccessfull operation!',
                    'duration'  => $duration,
                ));
                
                $opStatusMsg = $this->the_plugin->opStatusMsgGet();

                $ret = array_merge($ret, array('msg' => $opStatusMsg['msg']));
                if ( $retType == 'return' ) { return $ret; }
                else { die( json_encode( $ret ) ); }
            }
            //$nbpages = (int) $results['Items']['NbPagesSelected'];
            
            // search stats
			$rsp = $this->get_ws_object( $provider )->api_search_get_stats(array(
				'results'				=> $results,
			));
			$search_stats = $rsp['stats'];
            
            // status messages
            $opStatusMsg = array();
            $__opStatusMsg = $this->the_plugin->opStatusMsgGet();
            $opStatusMsg[] = $__opStatusMsg['msg'];
            $this->the_plugin->opStatusMsgInit(array(
                'operation_id'  => $requestData['operation_id'],
                'operation'     => 'load_by_search',
                'status'        => 'valid',
            ));
            
            // PARSE SEARCH RESULTS...
            $ret = array_merge($ret, array(
            	'status'			=> 'valid',
            	'asins'				=> array(),
            	'html'				=> '',
			));
            foreach ($results as $page => $page_content) {
                if ( !is_numeric($page) ) continue 1;
                //var_dump('<pre>',$page, $page_content,'</pre>');
                
                $duration = $this->the_plugin->timer_end(); // End Timer
                $durationQueue[] = $duration; // End Timer

                //$asins = $page_content['Items']['Item'];
				$rsp = $this->get_ws_object( $provider )->api_cache_get_page_asins(array(
					'page_content'				=> $page_content,
				));
				$asins = $rsp['asins'];
                $asins_inqueue = $this->build_asins_inqueue( (array) $requestData['asins_inqueue'], $ret['asins'] );
                $queueAsinsStats = $this->loadprods_queue_by_asin( 'return', array(
                    'operation'         => 'search',
                    'page'              => $page,
                    'asins'             => $asins,
                    'asins_inqueue'     => $asins_inqueue,
                    'provider'			=> $provider
                ));
                $queueAsinsStats['asins']['found'] = $asins;

                if ( isset($queueAsinsStats['duration']) ) {
                	$durationQueue[] = $queueAsinsStats['duration']; // End Timer
                	unset($queueAsinsStats['duration']);
				}
                
                $this->the_plugin->timer_start(); // Start Timer

                if ( isset($queueAsinsStats['msg']) ) {
                	unset($queueAsinsStats['msg']);
				}

                if ( isset($queueAsinsStats['html']) ) {
                	$ret['html'] .= $queueAsinsStats['html'];
                	unset($queueAsinsStats['html']);
				}
                
                if ( isset($queueAsinsStats['nb_amz_req']) ) {
                	$ret['nb_amz_req'] += $queueAsinsStats['nb_amz_req'];
                	unset($queueAsinsStats['nb_amz_req']);
				}

               	$ret['status'] = ( $ret['status'] == 'valid' ) && isset($queueAsinsStats['status'])
					&& ( $queueAsinsStats['status'] == 'valid' ) ? 'valid' : 'invalid';
                if ( $queueAsinsStats['status'] ) {
                	unset($queueAsinsStats['status']);
				}

                $ret = array_merge_recursive($ret, $queueAsinsStats); //array_replace_recursive
            }

            $duration = $this->the_plugin->timer_end(); // End Timer
            $durationQueue[] = $duration; // End Timer
            $duration = round( array_sum($durationQueue), 4 ); // End Timer
            
            // status messages
            if ( isset($search_stats['TotalResults'], $search_stats['TotalPages']) ) {
	            $search_stats_msg = sprintf( 
					__('%s items in %s pages', 'aiowaff'),
					$search_stats['TotalResults'],
					$search_stats['TotalPages']
				);
	            $this->the_plugin->opStatusMsgSet(array(
	                'status'    => 'valid',
	                'msg'       => $search_stats_msg,
	            ));
				$ret['search_stats'] = $search_stats_msg;
			}

            // status messages
            $this->the_plugin->opStatusMsgSet(array(
                'status'    => 'valid',
                'msg'       => sprintf( 'Number of ' . $provider . ' Requests: %s', $ret['nb_amz_req'] ),
                'duration'  => $duration,
                'end'       => true,
            ));
            
            $__opStatusMsg = $this->the_plugin->opStatusMsgGet();
            $opStatusMsg[] = $__opStatusMsg['msg'];
 
            $ret = array_merge($ret, array('msg' => implode('<br />', $opStatusMsg)));
            //var_dump('<pre>',$ret,'</pre>');
            if ( $retType == 'return' ) { return $ret; }
            else { die( json_encode( $ret ) ); }
        }

        // load products - set msg/message for ajax response
        private function loadprods_set_msg( $ret ) {
            
            $loaded = $ret['asins']['loaded'];
            $imported = $ret['asins']['imported'];
            $invalid = $ret['asins']['invalid'];
            $inqueue = $ret['asins']['inqueue'];
            $variations = $ret['asins']['variations'];
            
            //$amz_setup = $this->the_plugin->getAllSettings('array', 'amazon');
            $amz_setup = $this->settings;
            $do_parent_setting = !isset($amz_setup['variation_force_parent'])
                || ( isset($amz_setup['variation_force_parent']) && $amz_setup['variation_force_parent'] != 'no' )
                ? true : false;
            $show_variation = count($variations) > 0 ? true : false;

            $_invalid_childs = array();
            if ( $do_parent_setting && $show_variation ) {
                $invalid_real = array_diff( $invalid, array_keys($variations) );
                $invalid_childs = array_intersect( $invalid, array_keys($variations) );
                foreach ( $invalid_childs as $asin) {
                    $_invalid_childs["$asin"] = $variations["$asin"]; // child=parent
                }
                $__invalid_childs = !empty($_invalid_childs) ? http_build_query( $_invalid_childs, '', ', ' ) : '--';
            }

            // message
            $_msg = array();
            // Loaded
            if ( count($loaded) > 0 ) {
                $_msg[] = sprintf( __('%s ASINs loaded in queue: %s', $this->the_plugin->localizationName), count($loaded), implode(', ', $loaded) );
            }
            // Already Imported
            if ( count($imported) > 0 ) {
                $_msg[] = sprintf( __('%s ASINs already imported: %s', $this->the_plugin->localizationName), count($imported), implode(', ', $imported) );
            }
            // Already Parsed: loaded, invalid, already imported
            if ( count($inqueue) > 0 ) {
                $_msg[] = sprintf( __('%s ASINs already parsed (loaded, invalid, imported): %s', $this->the_plugin->localizationName), count($inqueue), implode(', ', $inqueue) );
            }
            // Invalid
            if ( count($invalid) > 0 ) {
                if ( $do_parent_setting && $show_variation ) {
                    if ( count($invalid_real) > 0 ) {
                        $_msg[] = sprintf( __('%s ASINs invalid: %s', $this->the_plugin->localizationName), count($invalid_real), implode(', ', $invalid_real) );
                    }
                }
                else {
                    $_msg[] = sprintf( __('%s ASINs invalid: %s', $this->the_plugin->localizationName), count($invalid), implode(', ', $invalid) );
                }
            }
            // Variations childs
            if ( $do_parent_setting && $show_variation ) {
                if ( count($_invalid_childs) > 0 ) {
                    $_msg[] = sprintf( __('%s ASINs variation childs (child=parent): %s', $this->the_plugin->localizationName), count($_invalid_childs), $__invalid_childs );
                }
            }

            return implode(' | ', $_msg);           
        }

        private function already_parsed_asins($parsed, $asins) {
            $ret = array('yes' => array(), 'no' => array());

            $tmp_yes = array();
            foreach (array('loaded', 'invalid', 'all_inqueue', 'all_already_imported') as $key) {
                $current = $parsed["$key"];

                // exists
                $tmp_yes = array_merge( $tmp_yes, array_values( array_intersect($asins, $current) ) );
            }
            $tmp_yes = array_unique($tmp_yes);
            $ret['yes'] = array_values( $tmp_yes );

            // do NOT exists
            $ret['no'] = array_values( array_diff($asins, $ret['yes']) );

            return (object) $ret;
        }

        private function already_parsed_asin($asins_parsed, $asin) {
            $stat = $this->already_parsed_asins($asins_parsed, array($asin));
            return in_array($asin, $stat->yes) ? true : false;
        }
        
        private function build_asins_inqueue($current=array(), $asins=array()) {
            $ret = (array) $current;
            if ( isset($asins['inqueue']) ) {
                $ret = array_merge($ret, $asins['inqueue']);
            }
            if ( isset($asins['loaded']) ) {
                $ret = array_merge($ret, $asins['loaded']);
            }
            if ( isset($asins['invalid']) ) {
                $ret = array_merge($ret, $asins['invalid']);
            }
            if ( isset($asins['imported']) ) {
                $ret = array_merge($ret, $asins['imported']);
            }
            $ret = array_unique($ret);
            return $ret;
        }

        private function __search_nice_params( $pms=array(), $provider='amazon' ) {
            $ret = array();
            foreach ($pms as $key => $value) {
            	if ( in_array($key, array('MerchantId')) && 'amazon' != $provider ) {
            		continue 1;
				}
                if ( $key == 'nbpages' ) $key = 'NbPages';
                $key = str_replace('_', ' ', $key);
                $key = ucwords($key);
                $ret["$key"] = $value;
            }
            return $ret;
        }


        /**
         * Import Product
         */
        public function import_product( $retType='die', $pms=array() ) {
            $requestData = array(
                'asin'                  => isset($_REQUEST['asin']) ? $_REQUEST['asin'] : '',
                'params'                => isset($_REQUEST['params']) ? $_REQUEST['params'] : '',
                'operation_id'          => isset($_REQUEST['operation_id']) ? $_REQUEST['operation_id'] : '', // operation id
                'provider'				=> 'amazon',
            );

            // params: import_type, nbimages, nbvariations, spin, attributes, to-category
            $params = array();
            parse_str( ( $requestData['params'] ), $params);

            $params['nbimages'] = 2;
            $params['nbvariations'] = 2;
   
            if( !empty($params) ) {
                $requestData = array_merge($requestData, $params);
            }
			foreach ($pms as $rk => $rv) {
				//if ( isset($pms["$rk"]) ) {
					$new_val = $pms["$rk"];
					$new_val = in_array($rk, array()) ? (array) $new_val : $new_val;
					$requestData["$rk"] = $new_val;
				//}
			}
            foreach ($requestData as $key => $val) {
                if ( strpos($key, '-') !== false ) {
                    $_key = str_replace('-', '_', $key); 
                    $requestData["$_key"] = $val;
                    unset($requestData["$key"]);
                }
            }
            extract($requestData);
  
            $ret = array(
                'status'        => 'invalid',
                'msg'           => '',
            );
            
            // from cache
            $product_from_cache = array();
            $__cachePms = array(
            	'provider'			=> $provider,
                'cache_type'		=> 'prods',
                'asin'					=> $asin
            );
      
            $__cache = $this->getTheCache( $__cachePms );
            $__cachePage = ( $__cache !== false ? $__cache : array() );

            // cache is found!
            if ( self::$CACHE_ENABLED['prods'] && !empty($__cachePage)
               	&& $this->get_ws_object( $provider )->is_valid_product_data($__cachePage) ) {
                $product_from_cache = $__cachePage;
            }
            
            // try to insert in database
            $args_add = array(
                'asin'                  => $asin,
                'from_cache'            => $product_from_cache,

                'from_module'           => 'insane',
                'import_type'           => $import_type,

                // bellow parameters are used in framework addNewProduct method
                'operation_id'          => $requestData['operation_id'],

                'import_to_category'    => $to_category,

                'import_images'         => (int) $nbimages > 0 ? (int) $nbimages : 'all',

                'import_variations'     => (string) $nbvariations === '0' ? 'no' : 'yes_' . $nbvariations,

                'spin_at_import'        => isset($requestData['spin']) ? true : false,

                'import_attributes'     => isset($requestData['attributes']) ? true : false,
            );
			$getProduct = $this->get_ws_object( $provider )->getProductDataFromAmazon( 'return', $args_add );
               
            $ret = array_merge($ret, $getProduct);
            $ret['import_settings'] = $this->the_plugin->get_last_imports();
			$ret['general_settings'] = $this->get_general_settings();
            //var_dump('<pre>',$ret,'</pre>');

            if ( $retType == 'return' ) { return $ret; }
            else { die( json_encode( $ret ) ); }
        }


        /**
         * Load Products - HTML
         */
        // load products in queue - build html
        public function loadprods_build_html( $prods=array(), $provider='amazon' ) {
            //$amz_setup = $this->the_plugin->getAllSettings('array', 'amazon');
            $amz_setup = $this->settings;
            $do_parent_setting = !isset($amz_setup['variation_force_parent'])
                || ( isset($amz_setup['variation_force_parent']) && $amz_setup['variation_force_parent'] != 'no' )
                ? true : false;

            $html = array();
            foreach ($prods as $asin => $prod) {
                
                // number of variations
                $nb_variations = 0;
				if ( in_array($provider, array('amazon', 'ebay')) ) {
                	$nb_variations = isset($prod['Variations'], $prod['Variations']['TotalVariations'])
                    	? (int) $prod['Variations']['TotalVariations'] : 0;
				}
                    
                // number of images
                $nb_images = isset($prod['images'], $prod['images']['large'])
                    ? (int) count($prod['images']['large']) : 0;
                    
                $data_settings = array(
                    'nb_variations'             => $nb_variations,
                    'nb_images'                 => $nb_images,
                );
                $data_settings = htmlentities(json_encode( $data_settings ));
                
                // price
				$price = $this->get_ws_object( $provider )->get_productPrice($prod);
                //var_dump('<pre>', $price, '</pre>');
                
                $_currency = !empty($price['_currency']) ? $price['_currency'] : '$';
				$_currency_ = strlen($_currency) > 1 ? $_currency . ' ' : $_currency;
                $_regular = $price['_regular_price'];
                $_sale = $price['_sale_price'];
                $price_html = array(); //<del>$1,029.99</del><span>$1,029.99</span>
                if ( !empty($_regular) ) {
                    if ( !empty($_sale) ) {
                        $price_html[] = "<del>{$_currency_}$_regular</del>";
                        $price_html[] = "<span>{$_currency_}$_sale</span>";
                    } else {
                        $price_html[] = "<span>{$_currency_}$_regular</span>";
                    }
                } else if ( !empty($_sale) ) {
                    $price_html[] = "<span>{$_currency_}$_sale</span>";
                }
                $price_html = implode('', $price_html);

                $html[] = '<li class="selected" data-asin="'.$asin.'" data-settings="'.$data_settings.'">'
                    . ($nb_variations > 0 ? '<i class="fa fa-external-link" title="' . sprintf( __('%s variations', $this->the_plugin->localizationName), $nb_variations ) . '"></i>' : '')
                    . '<span class="WooZoneLite-checked-product squaredThree">
                       <input type="checkbox" value="added" name="check" id="squaredThree-'.$asin.'" checked><label for="squaredThree-'.$asin.'"></label>
                    </span>
                    <a target="_blank" href="'.$prod['DetailPageURL'].'" class="WZC-keyword-attached-image"><img src="'.$prod['SmallImage'].'"></a>
                    <div class="WZC-keyword-attached-phrase"><a target="_blank" href="'.$prod['DetailPageURL'].'" class="WZC-keyword-attached-url"><span>'.$asin.'</span></a></div>
                    <div class="WZC-keyword-attached-title"><a target="_blank" href="'.$prod['DetailPageURL'].'" class="WZC-keyword-attached-url">'.$prod['Title'].'</a></div>
                    <div class="WZC-keyword-attached-brand">'.__('by:', $this->the_plugin->localizationName).' <span>'.$prod['Brand'].'</span></div>
                    <div class="WZC-keyword-attached-prices">'.$price_html.'</div>
                </li>';
            }
            return implode('', $html);
        }

        private function build_select( $param, $values, $default='', $extra=array() ) {
            $extra = array_replace_recursive(array(
                'prefix'        => 'WooZoneLite-search',
                'desc'          => array(),
                'nodeid'        => array(),
            ), $extra);
            extract($extra);

            $html = array();
            if (empty($values) || !is_array($values)) return '';
            foreach ($values as $k => $v) {
                
                $__selected = ($k == $default ? ' selected="selected"' : '');
                $__desc = (!empty($desc) && isset($desc["$k"]) ? ' data-desc="'.$desc["$k"].'"' : '');
                $__nodeid = (!empty($nodeid) && isset($nodeid["$k"]) ? ' data-nodeid="'.$nodeid["$k"].'"' : '');
                $html[] = '<option value="' . $k . '"' . $__selected . $__desc . $__nodeid . '>' . $v . '</option>';
            }
            return implode('', $html);
        }

        private function build_input_text( $param, $placeholder, $default='', $extra=array() ) {
            $extra = array_replace_recursive(array(
                'prefix'        => 'WooZoneLite-search',
                'desc'          => array(),
                'nodeid'        => array(),
            ), $extra);
            extract($extra);

            $name = $prefix.'['.$param.']';
            $id = "$prefix-$param";

            return '<input placeholder="' . $placeholder . '" name="' . $name . '" id="' . $id . '" type="text" value="' . (isset($default) && !empty($default) ? $default : '') . '"' . '>';
        }

        public function get_categories_html( $provider='amazon' ) {
            $categories = $this->get_categories('name', 'nice_name', $provider);
            $nodes = $this->get_categories('name', 'nodeid', $provider);
            return $this->build_select('category', $categories, '', array('nodeid' => $nodes));
        }
        
        public function build_searchform_element( $elm_type, $param, $value, $default, $extra=array() ) {
            $extra = array_replace_recursive(array(
                'global_desc'           => '',
                'desc'                  => array(),
            ), $extra);
            extract($extra);

            $css = array();
            $fa = 'fa-bars';
            if ( $param == 'Sort' ) {
                $fa = 'fa-sort';
            } else if ( $param == 'BrowseNode' ) {
                $fa = 'fa-sitemap';
                $css[] = 'WooZoneLite-param-node';
            }
            $css = !empty($css) ? ' ' .implode(' ', $css) : '';
            
            $html = array();
            $html[] = '<li class="WooZoneLite-param-optional'.$css.'">';
            $html[] =       '<span class="tooltip" title="'.$global_desc.'" data-title="'.$global_desc.'"><i class="fa '.$fa.'"></i></span>';
            $nice_name = $this->the_plugin->__category_nice_name( $param );
            if ( $elm_type == 'input' ) {
                $value = $nice_name;
                $html[] =   $this->build_input_text( $param, $value, $default, $extra );
            } else if ( $elm_type == 'select' ) {
            	$css = ' class=""';
            	if ( !empty($desc) && is_array($desc) ) {
            		$css = ' class="aiowaff-search-opt-desc"';
            	}
                $html[] =   '<select id="WooZoneLite-search-'.$param.'" name="WooZoneLite-search['.$param.']"'.$css.'>';
                $html[] =       '<option value="" disabled="disabled">'.$nice_name.'</option>';
                $html[] =   $this->build_select( $param, $value, $default, $extra );
                $html[] =   '</select>';
            }
            $html[] = '</li>';
            return implode('', $html);
        }
        
        public function get_category_params_html( $retType='die', $pms=array() ) {
            $ret = array(
                'status'        => 'invalid',
                'html'          => '',
            );

            $requestData = array(
                'what_params'           => isset($_REQUEST['what_params']) ? $_REQUEST['what_params'] : 'all',
                'category'              => isset($_REQUEST['category']) ? $_REQUEST['category'] : '',
                'nodeid'                => isset($_REQUEST['nodeid']) ? $_REQUEST['nodeid'] : '',
                'provider'				=> 'amazon',
            );
            foreach ($requestData as $rk => $rv) {
                if ( isset($pms["$rk"]) ) {
                    $new_val = $pms["$rk"];
                    $requestData["$rk"] = $new_val;
                }
            }
            extract($requestData);

            require('lists.inc.php');
            
            $optionalParameters = self::$optionalParameters["$provider"];
            if ( is_array($what_params) && !empty($what_params) ) {
                $optionalParameters = array_intersect_key($optionalParameters, array_flip($what_params));
            }

            // search parameters
            $ItemSearchParameters = array();
            if (!empty($optionalParameters) && !empty($nodeid)
				&& in_array($provider, array('amazon', 'alibaba'))) {
                $ItemSearchParameters = $this->get_ws_object( $provider )->getAmazonItemSearchParameters();
            }
    
            // sort parameters
            $ItemSortValues = array();
            if (!empty($optionalParameters)  && !empty($nodeid)
				&& in_array($provider, array('amazon', 'alibaba'))) {
                $ItemSortValues = $this->get_ws_object( $provider )->getAmazonSortValues();
            }

            $html = array();
            foreach ($optionalParameters as $oparam => $type) {
                
                if ( (!isset($ItemSearchParameters[$category]) || !in_array($oparam, $ItemSearchParameters[$category]))
                    && $oparam != 'Sort'
					&& in_array($provider, array('amazon', 'alibaba')) ) {
                    continue 1;
                }
                if ( $oparam == 'Sort' && (empty($category) || $category == 'AllCategories') ) {
                    continue 1;
                }
                
                $desc           = array();
                $global_desc    = isset($WooZoneLite_search_params_desc["$oparam"])
                    ? $WooZoneLite_search_params_desc["$oparam"] : '';
                $value          = isset($WooZoneLite_search_params["$oparam"])
                    ? $WooZoneLite_search_params["$oparam"] : '';
                    
                if ( $oparam == 'BrowseNode' ) {
                    
                    $value = $this->get_browse_nodes( $nodeid, $provider );

                } else if ( $oparam == 'Sort' ) {

					$value = (array) $value;
                    $curr_sort = array();
                    if ( isset($ItemSortValues[$category]) ) {
                        $curr_sort = $ItemSortValues[$category];
                    }
                    
                    foreach ( $value as $skey => $stext ){
                        if ( empty($curr_sort) || !in_array( $skey, $curr_sort) ){
                            unset($value["$skey"]);
                        }
                        $desc["$skey"] = $WooZoneLite_search_params_sort["$skey"];
                    }
                }
                
                $extra = array(
                    'global_desc'       => $global_desc,
                    'desc'              => $desc,
                );

                if ( ($type == 'select' && !empty($value)) || ($type == 'input') ) {
                	$default = '';
                    $html[] = $this->build_searchform_element( $type, $oparam, $value, $default, $extra );
                }
            }

            $ret = array_merge($ret, array(
                'status'        => !empty($html) ? 'valid' : 'invalid',
                'html'          => implode('', $html),
            ));
            
            if ( $retType == 'return' ) { return $ret; }
            else { die( json_encode( $ret ) ); }
        }

        public function get_browse_nodes_html( $retType='die', $pms=array() ) {
            $requestData = array(
                'what_params'           => array('BrowseNode'),
                'category'              => isset($_REQUEST['category']) ? $_REQUEST['category'] : '',
                'nodeid'                => isset($_REQUEST['nodeid']) ? $_REQUEST['nodeid'] : '',
                'provider'				=> 'amazon',
            );
            foreach ($requestData as $rk => $rv) {
                if ( isset($pms["$rk"]) ) {
                    $new_val = $pms["$rk"];
                    $requestData["$rk"] = $new_val;
                }
            }
            extract($requestData);

            $ret = $this->get_category_params_html($retType, $requestData);

            if ( $retType == 'return' ) { return $ret; }
            else { die( json_encode( $ret ) ); }
        }


        /**
         * Export ASINs
         */
        public function ajax_export_asin() {
            $req = array(
                'asins'                 => isset($_REQUEST['asins']) ? (array) $_REQUEST['asins'] : array(),
                'export_asins_type'     => isset($_REQUEST['export_asins_type']) ? $_REQUEST['export_asins_type'] : '1',
                'delimiter'             => isset($_REQUEST['delimiter']) ? $_REQUEST['delimiter'] : 'newline',
                'do_export'             => isset($_REQUEST['do_export']) ? true : false,
            );
            $req = array_merge($req, array(
                'export_type'           => 'csv',
            ));
            extract($req);
            if ( $delimiter == 'newline' ) {
                $delimiter = "\n";
            } else if ( $delimiter == 'comma' ) {
                $delimiter = ",";
            } else if ( $delimiter == 'tab' ) {
                $delimiter = "\t";
            }
            $req["delimiter"] = $delimiter;
 
            $ret = array(
                'status'    => 'invalid',
                'msg'      => '',
            );
            
            if ( empty($export_asins_type) ) {
                $ret = array_merge($ret, array(
                    'msg'      => 'Please choose an export asins type!'
                ));
                die(json_encode( $ret ));
            }
            
            $file_rows = array_merge(array(0 => 'ASINs List'), $asins);
            if ( empty($file_rows) ) {
                $ret = array_merge($ret, array(
                    'msg'      => 'No ASINs found to export!'
                ));
                die(json_encode( $ret ));
            }
            
            if ( $do_export ) {
                $this->do_export( $file_rows, $req );
                die;
            }
            
            $ret = array_merge($ret, array(
                'status'        => 'valid',
                'msg'          => 'export was successfull.',
            ));
            die(json_encode( $ret ));
        }

        private function do_export( $result, $req ) {
            if (!$result) return false;
            
            extract($req);
            
            $filename = $this->__export_filename($req);
            switch ($export_type) {
                case 'csv' :
                    $file_ext = 'csv';
                    $content_type = 'text/csv';
                    break;
                    
                case 'sml':
                    $file_ext = 'xls';
                    $content_type = 'application/vnd.ms-excel';
                    //xls: application/vnd.ms-excel
                    //xlsx: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
                    
                    require_once( $this->the_plugin->cfg['paths']['scripts_dir_path'] . '/php-export-data/php-export-data.class.php' );
                    $exporter = null; 
                    if( class_exists('ExportDataExcel') ){
                        $exporter = new ExportDataExcel('string', 'test.xls');
                    }
                    break;
            }

            ob_end_clean();

            // export headers
            ///*
            header("Content-Description: File Transfer");           
            header("Content-Type: $content_type; charset=utf-8"); //application/force-download
            header("Content-Disposition: attachment; filename=$filename.$file_ext");
            // Disable caching
            header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
            header("Cache-Control: private", false);
            header("Pragma: no-cache"); // HTTP 1.0
            header("Expires: 0"); // Proxies
            //*/
            
            //echo "record1,record2,record3\n"; die;
 
            $isExport = false;
            if ( $export_type == 'csv'
                || ( $export_type == 'sml' && !is_null($exporter) ) ) {
                $isExport = true;
            }

            // begin export file
            if ( $isExport ) {
                $fp = fopen('php://output', 'w');
                $headrow = $result[0];
                $headrow = array($headrow);
                //$headrow = array_keys($headrow);
                $headrow = array_map(array($this, '__nice_title'), $headrow);
                unset($result[0]);
            }
  
            // export file content
            if ( $export_type == 'csv' ) {
                $this->__fputcsv_eol($fp, $headrow, ',', '"', $delimiter);
                foreach ($result as $data) {
                    $this->__fputcsv_eol($fp, array($data), ',', '"', $delimiter);
                }
                
            } else if ( $export_type == 'sml' && !is_null($exporter) ) {
                $exporter->initialize(); // starts streaming data to web browser
                
                // pass addRow() an array and it converts it to Excel XML format and sends 
                // it to the browser
                $exporter->addRow($headrow); 
                //$exporter->addRow(array("This", "is", "a", "test")); 
                //$exporter->addRow(array(1, 2, 3, "123-456-7890"));
                
                foreach ($result as $data) {
                    $exporter->addRow($data);
                }
                
                $exporter->finalize(); // writes the footer, flushes remaining data to browser.
                
                $content = $exporter->getString();
                fwrite($fp, $content);
            }
            
            // end export file
            if ( $isExport ) {
                fclose($fp);
            }

            $contLength = ob_get_length();
            //header( 'Content-Length: '.$contLength);

            die;
        }

        private function __export_filename( $req ) {
            extract($req);

            $f = array();
            $f[] = 'woozonelite_IM_export_asins';
            $f[] = time();
            
            return implode('__', $f);         
        }
        
        private function __nice_title($item) {
            $title = str_replace('_', ' ', $item);
            $title = ucwords($title);
            return $title;
        }
        
        private function __old_fputcsv_eol($handle, $array, $delimiter = ',', $enclosure = '"', $eol = "\n") {
            $return = fputcsv($handle, $array, $delimiter, $enclosure);
            if($return !== FALSE && "\n" != $eol && 0 === fseek($handle, -1, SEEK_CUR)) {
                fwrite($handle, $eol);
            }
            return $return;
        }
        
        private function __fputcsv_eol($fh, array $fields, $delimiter = ',', $enclosure = '"', $eol = "\n", $mysql_null = false) { 
            $delimiter_esc = preg_quote($delimiter, '/'); 
            $enclosure_esc = preg_quote($enclosure, '/'); 

            $output = array(); 
            foreach ($fields as $field) { 
                if ($field === null && $mysql_null) { 
                    $output[] = 'NULL'; 
                    continue; 
                } 

                $output[] = preg_match("/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field) ? ( 
                    $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure 
                ) : $field; 
            }

            fwrite($fh, join($delimiter, $output) . $eol); 
        }


        /**
         * Cache related
         */
        // build cache name
        private function buildCacheName($pms) {
            extract($pms);
            $arr = array();
            $ret = array();

            if ( $cache_type == 'search' ) {
                $ret['folder'] = self::$CACHE['search_folder'];
                $ret['cache_lifetime'] = self::$CACHE['search_lifetime'];

				//$arr['provider'] = $provider;
				$arr = array_merge($arr, $params1);
                
                $arr = array_merge($arr, $params2);
                if ( isset($arr['ItemPage']) ) unset($arr['ItemPage']);

                $cachename = md5( json_encode( $arr ) );
                
            } else if ( $cache_type == 'prods' ) {

                $ret['folder'] = self::$CACHE['prods_folder'];
                $ret['cache_lifetime'] = self::$CACHE['prods_lifetime'];
                
				//$arr['provider'] = $provider;
                $arr['asin'] = $asin;
                
                $cachename = strtolower($arr['asin']);
            }

            //$cachename = md5( json_encode( $arr ) );
            return (object) array_merge($ret, array(
            	'provider'			=> $provider,
                'cache_type'        => $cache_type,
                'filename'          => $cachename,
                'params'            => $arr
            ));
        }
        
        // get cache data
        private function getTheCache($pms) {
            extract($pms);
            $u = $this->the_plugin->u;

            $cachename = $this->buildCacheName($pms);
            $filename = $cachename->folder . ( $cachename->filename ) . '.json';

            // read from cache!
            if ( $u->needNewCache($filename, $cachename->cache_lifetime) !== true ) { // no need for new cache!
   
                $body = $u->getCacheFile($filename);
  
                if (is_null($body) || !$body || trim($body)=='') { // empty cache file
                } else {
                    $ret = $body;
                    //$ret = json_decode( $ret );
                    $ret = unserialize( $ret );
                    return $ret;
                }
            }
            return false;
        }
        
        // set cache data
        private function setTheCache($pms, $content, $old_content=array(), $do_write=true) {
            if ( empty($content) ) return false;
            extract($pms);
            $u = $this->the_plugin->u;

            $cachename = $this->buildCacheName($pms);
            $filename = $cachename->folder . ( $cachename->filename ) . '.json';

            $dataToSave = array();            
            if ( $cache_type == 'prods' ) {
                
                if ( !empty($old_content) ) {
                    $dataToSave = $old_content;
                }
				$dataToSave = array_replace_recursive($dataToSave, $content);

            } else if ( $cache_type == 'search' ) {

				/*
                if ( !empty($old_content) ) {
                    $dataToSave = $old_content;
                } else {
                    $dataToSave['Items']['TotalResults'] = $content['Items']['TotalResults'];
                    $dataToSave['Items']['NbPagesSelected'] = $cachename->params['nbpages'];
                }

                if ( is_array($content) && !isset($content['__notused__']) ) {

                    $dataToSave["$page"] = array();
                    $response = $content;

                    // 1 item found only
                    if ( $dataToSave['Items']['TotalResults'] == 1 && !isset($response['Items']['Item'][0]) ) {
                        $response['Items']['Item'] = array($response['Items']['Item']);
                    }

                    foreach ($response['Items']['Item'] as $key => $value) {
                        $product = $this->build_product_data( $value, array(), $provider );
                        if ( !empty($product['ASIN']) ) {
                            $dataToSave["$page"]['Items']['Item']["$key"] = $product['ASIN'];
                        }
                    }

                    // 1 item found only
                    if ( $dataToSave['Items']['TotalResults'] == 1 && !isset($response['Items']['Item'][0]) ) {
                        $dataToSave["$page"]['Items']['Item'] = $dataToSave["$page"]['Items']['Item'][0];
                    }
                }
				*/
				$rsp = $this->get_ws_object( $provider )->api_cache_set_page_content(array(
					'requestData'			=> $requestData,
					'content'				=> $content,
					'old_content'			=> $old_content,
					'cachename'				=> $cachename,
					'page'					=> $page,
				));
				$dataToSave = $rsp['dataToSave'];
            }

            // return instead of write content to file
            if ( !$do_write ) {
                return array(
                    'dataToSave'        => $dataToSave,
                    'filename'          => $filename,
                );
            }

            $dataToSave = serialize( $dataToSave );
            //$dataToSave = json_encode( $dataToSave );
            return $u->writeCacheFile( $filename, $dataToSave ); // write new local cached file! - append new data
        }

        // delete cache data
        private function deleteTheCache($pms) {
            $u = $this->the_plugin->u;
            
            $cachename = $this->buildCacheName($pms);
            $filename = $cachename->folder . ( $cachename->filename ) . '.json';
            return $u->deleteCache($filename);
        }

        // cache status (enabled | disabled)
        public function setCacheStatus($cache_type, $new_status='') {
            if ( !empty($new_status) && is_bool($new_status) ) {
                self::$CACHE_ENABLED["$cache_type"] = $new_status;
            }
            return self::$CACHE_ENABLED["$cache_type"];
        }

		public function getCacheSettings() {
			return array_merge(array(), self::$CACHE_ENABLED, self::$CACHE);
		}


        /**
         * Utils
         */
        // get categories; retkey = nodeid | name
        private function get_categories( $retkey='name', $retval='nice_name', $provider='amazon' ) {
            $ret = array();
			$categs = $this->get_ws_object( $provider )->getAmazonCategs();
            //$categs = array_flip($categs); // fixed so duplicated node ids will not be removed!
            foreach ($categs as $categ_name => $nodeid) {
                if ( $retval == 'nice_name' ) {
                    $__categ_name = $this->the_plugin->__category_nice_name($categ_name);
                } else if ( $retval == 'nodeid' ) {
                    $__categ_name = $nodeid;
                }
                $__key = $retkey == 'name' ? $categ_name : $nodeid;
                $ret["$__key"] = $__categ_name;
            }
            return $ret;
        }
        
        private function get_importin_category() {
            $args = array(
                'orderby'   => 'menu_order',
                'order'     => 'ASC',
                'hide_empty' => 0,
                'post_per_page' => '-1'
            );
            $categories = get_terms('product_cat', $args);
              
            $args = array(
                'show_option_all'    => '',
                'show_option_none'   => 'Use category from Amazon',
                'orderby'            => 'ID', 
                'order'              => 'ASC',
                'show_count'         => 0,
                'hide_empty'         => 0, 
                'child_of'           => 0,
                'exclude'            => '',
                'echo'               => 0,
                'selected'           => 0,
                'hierarchical'       => 1, 
                'name'               => 'WooZoneLite-to-category',
                'id'                 => 'WooZoneLite-to-category',
                'class'              => 'postform',
                'depth'              => 0,
                'tab_index'          => 0,
                'taxonomy'           => 'product_cat',
                'hide_if_empty'      => false,
            );
            return wp_dropdown_categories( $args );
        }

        private function get_browse_nodes( $nodeid, $provider, $option_none=true ) {
            $ret = array();
            $first = false;
            $nodes = $this->the_plugin->getBrowseNodes( $nodeid, $provider );
			if ( empty($nodes) ) return $ret;

            foreach ($nodes as $key => $value){
            	if ( 'amazon' == $provider ) {
            		$browse_node = isset($value['BrowseNodeId']) && trim($value['BrowseNodeId']) != ""
            			? $value['BrowseNodeId'] : array();
					$name = !empty($browse_node) ? $value['Name'] : '';
				}
				else if ( 'ebay' == $provider ) {
            		$browse_node = isset($value['CategoryID']) && trim($value['CategoryID']) != ""
            			? $value['CategoryID'] : array();
					$name = !empty($browse_node) ? $value['CategoryName'] : '';
				}
				
                if( !empty($browse_node) ) {
                    if ( !$first && $option_none ) {
                        $ret[''] = 'All Browse Nodes';
                        $first = true;
                    }
                    //$browse_node = $value['BrowseNodeId'];
                    //$name = $value['Name'];
                    $ret["$browse_node"] = $name;                    
                }
            }
            return $ret;
        }

        // get products already imported in database
        private function get_products_already_imported() {
            $your_products = (array) $this->the_plugin->getAllProductsMeta('array', '_amzASIN');
            if( empty($your_products) || !is_array($your_products) ){
                $your_products = array();
            }
            return $your_products;
        }

        // setup amazon object for making request
        public function setupAmazonWS( $params=array() ) {
            $settings = $this->settings;

            // load the amazon webservices client class
            require_once( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '/lib/scripts/amazon/aaAmazonWS.class.php' );

            // create new amazon instance
            $aaAmazonWS = new aaAmazonWS(
                isset($params['AccessKeyID']) ? $params['AccessKeyID'] : $settings['AccessKeyID'],
                isset($params['SecretAccessKey']) ? $params['SecretAccessKey'] : $settings['SecretAccessKey'],
                isset($params['country']) ? $params['country'] : $settings['country'],
                isset($params['main_aff_id']) ? $params['main_aff_id'] : $this->the_plugin->main_aff_id()
            );

			$this->amzHelper->aaAmazonWS = $aaAmazonWS;
            $this->amzHelper->aaAmazonWS->set_the_plugin( $this->the_plugin, $settings );

			$this->aaAmazonWS = $this->amzHelper->aaAmazonWS;
        }
        
        // build single product data based on amazon request array
        private function build_product_data( $item=array(), $old_item=array(), $provider='amazon' ) {
			return $this->get_ws_object( $provider )->build_product_data( $item, $old_item );
        }
    
		/**
		 * Octomber 2015 - new plugin functions
		 */
		public function get_ws_object( $provider='amazon', $what='helper' ) {
			//return $this->the_plugin->get_ws_object( $provider, $what );
			$arr = array(
				//'generic'		=> array(
				//	'helper'		=> $this->genericHelper,
				//	'ws'			=> null,
				//),
				'amazon'		=> array(
					'helper'		=> $this->amzHelper,
					'ws'			=> is_object($this->amzHelper) ? $this->amzHelper->aaAmazonWS : null,
				),
			);
			return $arr["$provider"]["$what"];
		}
    
		private function do_sleep( $provider='amazon' ) {
			$rd = isset(self::$REQUESTS_DELAY["$provider"]) ? self::$REQUESTS_DELAY["$provider"] : array();
			if ( empty($rd) || !isset($rd['nbreq']) || !isset($rd['delay']) ) return;
			if ( empty($rd['nbreq']) || empty($rd['delay']) ) return;
			
			$nbreq = $rd['nbreq'];
			$delay = $rd['delay'];
			$current_nbreq = self::$REQUESTS_NB["$provider"]['current'];

			if ( $nbreq <= $current_nbreq ) {
				self::$REQUESTS_NB["$provider"]['current'] = 0;
				usleep( $delay );
			}
			return;
		}
		
		private function inc_nbreq( $provider='amazon', $from='' ) {
			if ( !in_array($from, array('search_byasin', 'search_bypages')) ) return;
			if ( !isset(self::$REQUESTS_NB["$provider"]) ) return;

			// increase number of requests made based on provider & from parameters
			$inc = 1;
			if ( 'search_byasin' == $from ) {
				//if ( 'envato' == $provider ) {
				//	 $inc = 2; // 2 requests are made in provider aaEnvatoWS class file
				//}
			}
			else if ( 'search_bypages' == $from ) ;
			
			self::$REQUESTS_NB["$provider"]['current'] += $inc;
			self::$REQUESTS_NB["$provider"]['total'] += $inc;
			
			// make delay if necessary
			$this->do_sleep( $provider );
		}
	
		private function provider_is_enabled( $provider='amazon' ) {
			//$providers = $this->the_plugin->get_ws_prefixes();
			$providers_status = $this->the_plugin->get_ws_status();
			if ( isset($providers_status["$provider"]) && $providers_status["$provider"] ) return true;
			return false;
		}
	

		/**
		 * Auto Import related
		 */
		public function load_auto_import() {
			//return false; // DEACTIVATED
			if ( !$this->the_plugin->is_module_active('auto_import') ) return;

   			// already loaded?
			if ( !is_null($this->objAI) && is_object($this->objAI) ) return;

			// Initialize the WooZoneLiteAutoImport class
			require_once( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '/modules/auto_import/init.php' );
			//$WooZoneLiteAutoImport = new WooZoneLiteAutoImport();
			$WooZoneLiteAutoImport = WooZoneLiteAutoImport::getInstance();

			$this->objAI = $WooZoneLiteAutoImport;
		}
	
	
		/**
		 * from june 2016
		 */
		public function get_general_settings() {
			$s = array(
				'remote_amazon_images'		=> $this->the_plugin->is_remote_images,
			); 
			return $s;
		}
	}
}

// Initialize the WooZoneLiteInsaneImport class
$WooZoneLiteInsaneImport = WooZoneLiteInsaneImport::getInstance();