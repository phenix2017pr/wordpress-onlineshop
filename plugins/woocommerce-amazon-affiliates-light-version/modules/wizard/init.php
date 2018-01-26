<?php
/*
* Define class WooZoneLiteWizard
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
!defined('ABSPATH') and exit;

if (class_exists('WooZoneLiteWizard') != true) {
    class WooZoneLiteWizard
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
        //public $amz_settings;

		private $module_folder = '';
        private $module_folder_path = '';
		private $module = '';

		static protected $_instance;
		
		private $settings;

		public $localizationName;

		public $wp_dashboard_url = '';
		public $plugin_dashboard_url = '';
		public $wizard_index_url = '';
		public $wizard_requirements_url = '';
		public $wizard_questions_url = '';

		private $alias_wz = '';

		private $steps = array(); // wizard steps
		private $step = null; // wizard current showed step
		private $nextprev = array(); // wizard next & previous step based on current

		private $save_status = array();


        /*
         * Required __construct() function that initalizes the AA-Team Framework
         */
        public function __construct( $load=true )
        {
        	global $WooZoneLite;

	       	$this->the_plugin = $WooZoneLite;

            $this->amzHelper = $this->the_plugin->amzHelper;
			if ( is_object($this->the_plugin->amzHelper) ) {
            	$this->aaAmazonWS = $this->the_plugin->amzHelper->aaAmazonWS;
			}
			//$this->amz_settings = $this->the_plugin->amz_settings;

			$this->localizationName = $this->the_plugin->localizationName;

			$this->module_folder = $this->the_plugin->cfg['paths']['plugin_dir_url'] . 'modules/wizard/';
            $this->module_folder_path = $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'modules/wizard/';
			$this->module = $this->the_plugin->cfg['modules']['wizard'];

			$this->wp_dashboard_url = esc_url( admin_url() );
			$this->plugin_dashboard_url = esc_url( admin_url( '/admin.php?page=WooZoneLite#!/dashboard' ) );
			$this->wizard_index_url = $this->wizard_build_link();
			$this->wizard_requirements_url = $this->wizard_build_link( 'requirements' );
			$this->wizard_questions_url = $this->wizard_build_link( 'questions' );

			$this->alias_wz = $this->the_plugin->alias . "_wizard";

			if ( $load ) {
				// we must have wizard as current wp page 
				if ( filter_input( INPUT_GET, 'page' ) !== $this->alias_wz ) {
					return;
				} 
				//$this->settings = $this->the_plugin->getAllSettings('array', 'wizard');

				if ( is_admin() ) {
					add_action('admin_menu', array( $this, 'adminMenu' ));
					add_action('admin_enqueue_scripts', array( $this, 'wizard_enqueue_scripts' ));
					add_action('admin_init', array( $this, 'wizard_render_page' ), 20);

					// init
					$this->wizard_init();
				}
			} // end load
        }

		/**
	     * Singleton pattern
	     *
	     * @return WooZoneLiteInsaneImport Singleton instance
	     */
	    static public function getInstance( $load=true )
	    {
	        if (!self::$_instance) {
	            self::$_instance = new self( $load );
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
    			$this->the_plugin->alias . " " . __('Wizard', $this->the_plugin->localizationName),
	            __('Wizard', $this->the_plugin->localizationName),
	            'manage_options',
	            $this->alias_wz,
	            array($this, 'wizard_render_page')
	        );

	        add_dashboard_page( '', '', 'manage_options', $this->alias_wz, '' );

			return $this;
		}

		public function wizard_enqueue_scripts() {
			$protocol = is_ssl() ? 'https' : 'http';

			//wp_enqueue_media();
			//wp_enqueue_style( 'forms' );

			//:: styles
			wp_enqueue_style( $this->alias_wz . '-google-lora',  $protocol . '://fonts.googleapis.com/css?family=Lora:400,700' );
			wp_enqueue_style( $this->alias_wz . '-google-lato',  $protocol . '://fonts.googleapis.com/css?family=Lato:400,700' );

			wp_enqueue_style( $this->alias_wz . '-bootstrap', $this->module_folder . 'css/bootstrap.min.css', array() );
			wp_enqueue_style( $this->alias_wz . '-demo', $this->module_folder . 'css/demo.css', array() );
			wp_enqueue_style( $this->alias_wz . '-wizard', $this->module_folder . 'css/wizard.css', array() );
			wp_enqueue_style( $this->alias_wz . '-responsive', $this->module_folder . 'css/responsive.css', array() );

			//:: scripts /wp_register_script
			wp_enqueue_script( $this->alias_wz . '-use-fontawesome',  $protocol . '://use.fontawesome.com/5a26c12a15.js', array() );
			//https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( $this->alias_wz . '-hashchange', $this->module_folder . 'js/hashchange.js', array( 'jquery' ) );
			wp_enqueue_script( $this->alias_wz . '-main', $this->module_folder . 'js/main.js', array( 'jquery', $this->alias_wz . '-hashchange' ) );

			$vars = array(
				'ajax_url'			=> admin_url('admin-ajax.php'),
				'lang' 				=> array(
					'loading'						=> __('Loading...', $this->localizationName),
					'closing'          				=> __('Closing...', $this->localizationName),
					'saving'           				=> __('Saving...', $this->localizationName),
					'amzcart_cancel'				=> __('canceled', $this->localizationName),
					'check_keys_check'				=> __('Checking your keys ...', $this->localizationName),
					'check_keys_success'			=> __('WooCommerce Amazon Affiliates was able to connect to Amazon with the specified AWS Key Pair and Associate ID', $this->localizationName),
					'check_keys_error'				=> __('WooCommerce Amazon Affiliates was not able to connect to Amazon with the specified AWS Key Pair and Associate ID. Please triple-check your AWS Keys and Associate ID.', $this->localizationName),
					'questions_answer_invalid'		=> __('We\'re sorry, but in order for us to recommand some settings, you must answer the above question.', $this->localizationName),
					'check_keys_affid_invalid'		=> __('You must enter you\'re AffiliateID.', $this->localizationName),
				),
			);
			wp_localize_script( $this->alias_wz . '-main', 'woozonelite_vars', $vars );
		}

		public function wizard_get_requirements() {
			$check = $this->sr_check(array());
			$check_all = isset($check['status']) ? $check['status'] : '';
			$check = isset($check['check']) ? $check['check'] : array();

			$GLOBALS['check'] = $check;
			$GLOBALS['check_all'] = $check_all;
		}
		
		public function wizard_render_page() {
			// we must have wizard as current wp page
			if ( filter_input( INPUT_GET, 'page' ) !== $this->alias_wz ) {
				return;
			}

			$this->printBaseInterface();
			exit;
		}

		/*
		 * printBaseInterface, method
		 * --------------------------
		 *
		 * this will add the base DOM code for you options interface
		 */
		private function printBaseInterface()
		{
			$this->wizard_enqueue_scripts();

			ob_start();
			//var_dump('<pre>', $this->step, $this->nextprev, '</pre>');
			$this->setup_wizard_header();
			$this->setup_wizard_content();
			$this->setup_wizard_footer();
			//exit;

			// show the top menu
			//WooZoneLiteAdminMenu::getInstance()->make_active('import|insane_import')->show_menu();
		}


		/**
		 * Wizard
		 *
		 */
		public function wizard_init() { 

			if ( ! isset($_SESSION['WooZoneLite_wizard']) ) {
				$_SESSION['WooZoneLite_wizard'] = array();
			}
			//$_SESSION['WooZoneLite_wizard'] = array(); //DEBUG
			//var_dump('<pre>', $_SESSION , '</pre>');

			$this->wizard_init_steps();
			$this->wizard_current_step();
			$this->wizard_nextprev_step();

			// try to save step data
			$this->wizard_save_step_data();

			// default settings used to set default values for form elements
			$this->wizard_default_settings();

			// some parameters
			$current_step = $this->step;
			$nextprev = $this->nextprev;

			$stepPms = array(
				'current'		=> $current_step,
				'next'			=> $nextprev['next']['step'],
				'prev'			=> $nextprev['prev']['step']
			);
			$GLOBALS['stepPms'] = $stepPms;

			// get system requirements
			$this->wizard_get_requirements();
		}

		public function wizard_save_step_data() {
			// :: request parameters
			$req = array(
				'_wz_nonce'			=> isset($_POST['_wz_nonce']) ? $_POST['_wz_nonce'] : '',
				'savechanges'		=> isset($_POST['savechanges']) ? $_POST['savechanges'] : '',
				'step_prev'			=> isset($_POST['step_prev']) ? $_POST['step_prev'] : '',
				'WooZoneLite_wizard'	=> isset($_POST['WooZoneLite-wizard']) ? (array) $_POST['WooZoneLite-wizard'] : array(),
				'tosave'			=> isset($_POST['tosave']) ? $_POST['tosave'] : '',
			);

			$tosave = array();
			parse_str( $req['tosave'], $tosave );
			if( ! empty($tosave) ) {
				if ( isset($tosave['WooZoneLite-wizard']) ) {
					$tosave['WooZoneLite_wizard'] = $tosave['WooZoneLite-wizard'];
					unset($tosave['WooZoneLite-wizard']);
				}
				$req = array_merge($req, $tosave);
			}
			//var_dump('<pre>', $req , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			extract($req);


			$ret = array(
				'status'		=> 'invalid',
				'html'			=> '',
			);

			$questions_list = array('site_info', 'site_install', 'site_purpose');
			$current_step = $this->step;
			if ( empty($step_prev) ) {
				$step_prev = $this->nextprev['prev']['step'];
			}
			
			//$_SESSION['WooZoneLite_wizard'] = array(); //DEBUG
			ob_start();
			echo __FILE__ . ":" . __LINE__ . PHP_EOL;
			var_dump('<pre>_SESSION', $_SESSION['WooZoneLite_wizard'] , '</pre>');
			$this->save_status['dbg'] = ob_get_clean();

			//:: do we need to save form data?
			$is_save = false;
			if ( isset($savechanges) && $savechanges=='yes' ) {
				//check_admin_referer('woozonelite-wizard-save', '_wz_nonce');
				$check_nonce = check_ajax_referer('woozonelite-wizard-save', '_wz_nonce', false);
				//var_dump('<pre>',$check_nonce ,'</pre>');
				if ( $check_nonce ) {
					$is_save = true;
				}
				else {
					$ret = array_replace_recursive($ret, array(
						'html'		=> 'invalid form nonce!',
					));
				}
			}
			else {
				$ret = array_replace_recursive($ret, array(
					'html'		=> 'no need to save form!',
					'status' 	=> 'valid',
				));
				// we don't set is_save to true, because we must not save form data in this case
			}

			//:: do we have a Valid form submit?
			if ( ! $is_save ) {
				$this->save_status = array_replace_recursive($this->save_status, $ret);
				return $ret;
			}

			//:: yes, form submit is Valid...
			//...

			//:: reset the step default settings based on step - questions answer
			$question_answer = isset($_SESSION['WooZoneLite_wizard']['site_purpose'])
				? (string) $_SESSION['WooZoneLite_wizard']['site_purpose'] : '';
			$question_answer_ = isset($WooZoneLite_wizard, $WooZoneLite_wizard['site_purpose'])
				? (string) $WooZoneLite_wizard['site_purpose'] : '';
			//var_dump('<pre>', $question_answer, $question_answer_ , '</pre>');
			//var_dump('<pre>',('questions' == $step_prev) && ($question_answer != $question_answer_) ,'</pre>');

			if ( ('questions3' == $step_prev) && ($question_answer != $question_answer_) ) {
			//if (1) {
				foreach ($_SESSION['WooZoneLite_wizard'] as $key => $val) {
					if ( ! in_array($key, $questions_list) ) {
						unset( $_SESSION['WooZoneLite_wizard']["$key"] );
					}
				}
			}

			$options = array();
			$options = $this->wizard_build_default_settings($options, array('what' => 'empty_selection', 'step' => $step_prev));

			//:: overwrite settings with wizard chosen settings
			if ( isset($WooZoneLite_wizard) && ! empty($WooZoneLite_wizard) && is_array($WooZoneLite_wizard) ) {
				$options = array_replace_recursive($options, $WooZoneLite_wizard);
			}

			//:: save them in session
			///*
			$_SESSION['WooZoneLite_wizard'] = array_replace_recursive(
				$_SESSION['WooZoneLite_wizard'],
				$options
			);
			//*/

			//$_SESSION['WooZoneLite_wizard'] = array(); //DEBUG
			ob_start();
			echo __FILE__ . ":" . __LINE__ . PHP_EOL;
			var_dump('<pre>_SESSION', $_SESSION['WooZoneLite_wizard'] , '</pre>');
			var_dump('<pre>options', $options , '</pre>');
			$this->save_status['dbg'] = ob_get_clean();

			//:: end of wizard => try to save to db
			if ( 'finished' == $current_step ) {

				$save_opt = array();
				$save_opt = $this->wizard_build_default_settings($save_opt, array('what' => 'default_settings'));
				$save_opt = $this->wizard_build_default_settings($save_opt, array('what' => 'db_settings')); 

				//:: don't save these
				foreach ($_SESSION['WooZoneLite_wizard'] as $key => $val) {
					if ( in_array($key, $questions_list) ) {
						unset( $_SESSION['WooZoneLite_wizard']["$key"] );
					}
					else if ( preg_match('/--unused/imu', $key) ) {
						unset( $_SESSION['WooZoneLite_wizard']["$key"] );
					}
				}

				//:: save it now
				$save_opt = $this->wizard_build_default_settings($save_opt, array('what' => 'from_session'));
				update_option( 'WooZoneLite_amazon', $save_opt );

				//:: reset the wizard current session
				$_SESSION['WooZoneLite_wizard'] = array();
			}

			$ret = array_replace_recursive($ret, array(
				'status'		=> 'valid',
				'html'			=> 'ok',
			));
			$this->save_status = array_replace_recursive($this->save_status, $ret);
			return $ret;
		}

		public function wizard_default_settings() {
			//$_SESSION['WooZoneLite_wizard'] = array(); //DEBUG
			$s = array();
			$s = $this->wizard_build_default_settings($s, array('what' => 'empty_selection', 'step' => $this->step));
			$s = $this->wizard_build_default_settings($s, array('what' => 'default_json'));
			$s = $this->wizard_build_default_settings($s, array('what' => 'default_settings'));
			$s = $this->wizard_build_default_settings($s, array('what' => 'db_settings'));
			$s = $this->wizard_build_default_settings($s, array('what' => 'per_question'));
			$s = $this->wizard_build_default_settings($s, array('what' => 'from_session'));
			//var_dump('<pre>', $_SESSION['WooZoneLite_wizard'], $s , '</pre>');
			$this->settings = $s;
		}

		public function wizard_build_default_settings( $settings=array(), $pms=array() ) {
			$pms = array_replace_recursive(array(
				'what'		=> '',
				'step'		=> $this->step,
			), $pms);
			extract($pms);

			//:: get default settings
			if ( in_array($what, array('empty_selection', 'default_settings', 'per_question')) ) {
				//from /modules/setup_backup/default-settings.php
				$file_default_opt = $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'modules/setup_backup/default-settings.php';
				require( $file_default_opt );
			}

			//:: get default json settings (used when you activate the plugin)
			if ( 'default_json' == $what ) {
				//from /modules/setup_backup/default-setup.json
				$settings_from_json = array();
				$file_json = $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'modules/setup_backup/default-setup.json';
				//$response = wp_remote_get( $url_json, array());
				//if ( ! is_wp_error($response) ) {
				//	$body = wp_remote_retrieve_body( $response );
				//	$response = json_decode( $body, true );
				//}
				$response = $this->the_plugin->wp_filesystem->get_contents( $file_json );
				if ( $response ) {
					$response_ = json_decode( $response, true );
					if ( isset($response_['WooZoneLite_amazon']) && ! empty($response_['WooZoneLite_amazon']) ) {
						$settings_from_json = (array) $response_['WooZoneLite_amazon'];
					}
				}
			}

			//:: empty selection default settings
			if ( 'empty_selection' == $what ) {
				//from /modules/setup_backup/default-settings.php
				if ( isset($woozonelite_settings_empty_selection) && ! empty($woozonelite_settings_empty_selection) ) {
					if ( isset($woozonelite_settings_empty_selection["$step"])
						&& ! empty($woozonelite_settings_empty_selection["$step"])
					) {
						$settings = array_replace_recursive(
							$settings, 
							(array) $woozonelite_settings_empty_selection["$step"]
						);
					}
				}
			}

			//:: settings used when you activate the plugin
			if ( 'default_json' == $what ) {
				//from /modules/setup_backup/default-setup.json
				if ( ! empty($settings_from_json) ) {
					$settings = array_replace_recursive(
						$settings, 
						$settings_from_json
					);				
				}
			}

			//:: after you fresh install the plugin & hit first time the "save settings" button from amazon config module
			if ( 'default_settings' == $what ) {
				//from /modules/setup_backup/default-settings.php
				if ( isset($woozonelite_default_settings) && ! empty($woozonelite_default_settings) ) {
					$settings = array_replace_recursive(
						$settings, 
						(array) $woozonelite_default_settings
					);
				}
			}

			//:: current saved in db, plugin settings
			if ( 'db_settings' == $what ) {
				$current_opt = get_option( 'WooZoneLite_amazon', array() );
				if ( isset($current_opt) && ! empty($current_opt) && is_array($current_opt) ) {
					$settings = array_replace_recursive(
						$settings, 
						(array) $current_opt
					);
				}
			}

			//:: per question answer default settings
			if ( 'per_question' == $what ) {
				//from /modules/setup_backup/default-settings.php
				$answer_key = $this->wizard_question_answer_key();
				if ( isset($woozonelite_settings_per_questions) && ! empty($woozonelite_settings_per_questions) ) {
					if ( isset($woozonelite_settings_per_questions["$answer_key"])
						&& ! empty($woozonelite_settings_per_questions["$answer_key"])
					) {
						$settings = array_replace_recursive(
							$settings, 
							(array) $woozonelite_settings_per_questions["$answer_key"]
						);
					}
				}
			}

			//:: from session when following wizard steps
			if ( 'from_session' == $what ) {
				if ( isset($_SESSION['WooZoneLite_wizard']) ) {
					$settings = array_replace_recursive(
						$settings, 
						(array) $_SESSION['WooZoneLite_wizard']
					);
				}
			}

			//var_dump('<pre>', $settings , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			return $settings;
		}

		public function wizard_question_answer_key() {
			$question_answer_key = -1;

			$question_answer = isset($_SESSION['WooZoneLite_wizard']['site_purpose'])
				? (string) $_SESSION['WooZoneLite_wizard']['site_purpose'] : '';

			//do we have an answer saved in session?
			if ( ! in_array($question_answer, array('have_website', 'new_store', 'have_store', 'custom_checkout')) ) {
				return $question_answer_key;
			}

			switch ($question_answer) {
				case 'have_website':
					$question_answer_key = 0;
					break;

				case 'new_store':
				case 'have_store':
					$question_answer_key = 1;
					break;

				case 'custom_checkout':
					$question_answer_key = 2;
					break;
			}
			return $question_answer_key;
		}

		public function wizard_init_steps() {
			$steps = array(
				// index
				'index'			=> array(
					'title'			=> __('Index', $this->localizationName),
					'file'			=> 'index.php',
					'icon'			=> '',
				),
				// answer questions & get started
				'questions'		=> array(
					'title'			=> __('Questions', $this->localizationName),
					'file'			=> 'questions.php',
					'icon'			=> '',
				),
				'questions2'		=> array(
					'title'			=> __('Questions', $this->localizationName),
					'file'			=> 'questions2.php',
					'icon'			=> '',
				),
				'questions3'		=> array(
					'title'			=> __('Questions', $this->localizationName),
					'file'			=> 'questions3.php',
					'icon'			=> '',
				),

				// wizard settings
				'settings'		=> array(
					'title'			=> __('Settings', $this->localizationName),
					'file'			=> 'settings.php',
					'icon'			=> 'icon-cogs',
				),
				'prices_setup'	=> array(
					'title'			=> __('Prices Setup', $this->localizationName),
					'file'			=> 'price_setup.php',
					'icon'			=> 'icon-cash',
				),
		
				'customization'	=> array(
					'title'			=> __('Customization', $this->localizationName),
					'file'			=> 'customization.php',
					'icon'			=> 'icon-spray',
				),
				'amazon_config'	=> array(
					'title'			=> __('Amazon Keys', $this->localizationName),
					'file'			=> 'amazon_config.php',
					'icon'			=> 'icon-amazon',
				),

				// finished screen
				'finished'	=> array(
					'title'			=> __('Finished', $this->localizationName),
					'file'			=> 'finished.php',
					'icon'			=> '',
				),
				// system requirements
				'requirements'	=> array(
					'title'			=> __('System Requirements', $this->localizationName),
					'file'			=> 'requirements.php',
					'icon'			=> '',
				),
			);
			$this->steps = $steps;
			return $steps;
		}

		public function wizard_current_step() {
			$this->step = isset($_REQUEST['step'] ) ? sanitize_key($_REQUEST['step']) : $this->wizard_default_step();
			return $this->step;
		}

		public function wizard_default_step() {
			$def = array_keys($this->steps);
			$def = current($def);
			return $def;
		}

		public function wizard_nextprev_step() {
			$steps = $this->wizard_timeline_steps();
			$current_step = $this->step;

			$ret = array(
				'next'		=> array('step' => '', 'url' => '', 'text' => __('Next', $this->localizationName)),
				'prev'		=> array('step' => '', 'url' => '', 'text' => __('Previous', $this->localizationName)),
			);

			//$next = '';
			//$prev = '';
			$next = $this->array_key_relative($steps, $current_step, 1);
			$prev = $this->array_key_relative($steps, $current_step, -1);

			foreach ( array('next', 'prev') as $what ) {
				if ( ! empty($$what) ) {
					$what_ = $$what;

					$goto = isset($steps["$what_"]["file"]) ? $what_ : 'index';
					$goto = $this->wizard_build_link( $goto );

					$ret["$what"] = array_replace_recursive(
						$ret["$what"],
						array('step' => $$what, 'url' => $goto)
					);
				}
			} // end foreach

			if ( 'finished' == $ret['next']['step'] ) {
				$ret['next']['text'] = __('Finish', $this->localizationName);
			}
			if ( empty($ret['next']['url']) ) {
				$ret['next']['url'] = $this->plugin_dashboard_url;
				$ret['next']['text'] = __('Finish', $this->localizationName);
			}
			if ( in_array($current_step, array('index', 'requirements', 'finished')) ) {
				$ret['next']['url'] = '';
				$ret['prev']['url'] = '';
			}
			//var_dump('<pre>', $current_step, $ret ,'</pre>');

			$this->nextprev = $ret;
			return $ret;
		}

		public function wizard_timeline_steps() {
			$steps = $this->steps;
			if ( isset($steps['requirements']) ) {
				unset( $steps['requirements'] );
			}
			return $steps;
		}

		public function wizard_build_link( $step='' ) {
			$url = admin_url( '/admin.php?page=WooZoneLite_wizard' );
			if ( empty($step) ) {
				$url = esc_url( $url );
				return $url;
			}

			//$url = $url . '&step=' . $step;
			$url = $url . '#!/' . $step;
			$url = esc_url( $url );
			return $url;
		}

		public function setup_wizard_header() {
			$filename = $this->module_folder_path . 'steps/_header.php';
			require_once( $filename );
		}

		public function setup_wizard_footer() {
			$filename = $this->module_folder_path . 'steps/_footer.php';
			require_once( $filename );
		}

		public function setup_wizard_content() {
			$file = isset($this->steps["{$this->step}"]) ? $this->steps["{$this->step}"] : $this->wizard_default_step();
			$file = isset($file["file"]) ? $file["file"] : 'index.php';
			$filename = $this->module_folder_path . 'steps/' . $file;
			//var_dump('<pre>',$filename ,'</pre>');

			if ( $this->the_plugin->u->verifyFileExists($filename) ) {
				require_once( $filename );
			}
			else {
				die('error: step not found!');
			}
		}


		/**
		 * UTILS
		 *
		 */
		public function array_key_relative( $array, $current_key, $offset=1, $strict=true ) {
			// create key map
			$keys = array_keys($array);

			// find current key
			$current_key_index = array_search($current_key, $keys, $strict);
		  
			// return wanted offset, if in array, or false if not exists
			if ( isset($keys[$current_key_index + $offset]) ) {
				return $keys[$current_key_index + $offset];
			}
			return false;
		}

		public function array_set_pointer_to_key( &$array, $key ) {
			// reset the array pointer to first key
			reset($array);

			// current key is not the one we want
			while ( key($array) !== $key ) {
				// advanced to next element and also verify if it's end of array and couldn't find the key
				if ( next($array) === false ) {
					//throw new Exception('Invalid key');
					return false;
				}
			}
			return true;
		}

		public function __WooZoneLiteAffIDsHTML() {
			//global $WooZoneLite;
			$WooZoneLite = $this->the_plugin;
			
			//$html         = array();
			//$img_base_url = $WooZoneLite->cfg['paths']["plugin_dir_url"] . 'modules/amazon/images/flags/';
			
			$config = $WooZoneLite->settings();
			
			$config = $WooZoneLite->build_amz_settings(array(
				'AccessKeyID'			=> 'zzz',
				'SecretAccessKey'		=> 'zzz',
				'country'				=> 'com',
			));
		 
			require_once( $WooZoneLite->cfg['paths']['plugin_dir_path'] . 'aa-framework/amz.helper.class.php' );
			if ( class_exists('WooZoneLiteAmazonHelper') ) {
				//$theHelper = WooZoneLiteAmazonHelper::getInstance( $aiowaff );
				$theHelper = new WooZoneLiteAmazonHelper( $WooZoneLite );
			}
			$what = 'main_aff_id';
			$list = is_object($theHelper) ? $theHelper->get_countries( $what ) : array();
			return $list;
		}

		public function __WooZoneLite_amazon_countries( $what='array' ) {
			//global $WooZoneLite;
			$WooZoneLite = $this->the_plugin;
			
			//$html         = array();
			//$img_base_url = $WooZoneLite->cfg['paths']["plugin_dir_url"] . 'modules/amazon/images/flags/';
			
			$config = $WooZoneLite->settings();
			
			$config = $WooZoneLite->build_amz_settings(array(
				'AccessKeyID'			=> 'zzz',
				'SecretAccessKey'		=> 'zzz',
				'country'				=> 'com',
			));
			require_once( $WooZoneLite->cfg['paths']['plugin_dir_path'] . 'aa-framework/amz.helper.class.php' );
			if ( class_exists('WooZoneLiteAmazonHelper') ) {
				//$theHelper = WooZoneLiteAmazonHelper::getInstance( $aiowaff );
				$theHelper = new WooZoneLiteAmazonHelper( $WooZoneLite );
			}
			$list = is_object($theHelper) ? $theHelper->get_countries( $what ) : array();
			
			if ( in_array($what, array('country', 'main_aff_id')) ) {
				return $list;
			}
			return implode(', ', array_values($list));
		}

		public function __WooZoneLite_asof_font_size($min=0.1, $max=2.0, $step=0.1) {
			$newarr = array();
			for ($i=$min; $i <= $max; $i += $step, $i = (float) number_format($i, 1)) {
				$newarr[ "$i" ] = $i . ' em';
			}
			return $newarr;
		}


		/**
		 * HTML build form elements
		 *
		 */
        public function build_form_select( $param, $extra=array() ) {
            $extra = array_replace_recursive(array(
                'prefix'        => 'WooZoneLite-wizard',
                'css_class'		=> 'wz-dropdown',
                'css_style'		=> '',
                'default'		=> '',
                'values'		=> array(),
                'has_disabled'	=> false,
                'title'			=> '',
            ), $extra);
            extract($extra);

            $html = array();
            if ( empty($values) || ! is_array($values) ) {
            	return '';
            }

            $name = $this->__build_form_elem_name( $param, $prefix );
            $id = $this->__build_form_elem_id( $name );

            if ( '' == $default ) {
            	$default = isset($this->settings["$param"]) ? $this->settings["$param"] : '';
            }
            $default_ = (isset($default) && !empty($default) ? $default : '');

            $html[] = '<select name="' . $name . '" id="' . $id . '" class="' . $css_class . '" style="' . $css_style . '">';
            if ( $has_disabled ) {
            	$title_ = ! empty($title) ? $title : 'SELECT';
            	$html[] = '<option value="" disabled="disabled">'.$title.'</option>';
    		}
            foreach ($values as $k => $v) {
                $__selected = ($k == $default ? ' selected="selected"' : '');
                $html[] = '<option value="' . $k . '"' . $__selected . '>' . $v . '</option>';
            }
            $html[] = '</select>';

            return implode('', $html);
        }

        public function build_form_input( $param, $extra=array() ) {
            $extra = array_replace_recursive(array(
                'prefix'        => 'WooZoneLite-wizard',
                'css_class'		=> '',
                'css_style'		=> '',
                'default'		=> '',
            	'type'			=> 'text',
                'placeholder'	=> '',
                'readonly'		=> false,
            ), $extra);
            extract($extra);

            $html = array();

            // name & id
            $name = $this->__build_form_elem_name( $param, $prefix );
            $id = $this->__build_form_elem_id( $name );

            // default value
            if ( '' == $default ) {
            	$default = isset($this->settings["$param"]) ? $this->settings["$param"] : '';
            }
            $default_ = isset($default) && !empty($default) ? $default : '';
            if ( 'range' == $type ) {

            	$name_ = $name;
            	$id_ = $id;
            	if ( isset($hidden) && ! empty($hidden) ) {
            		$name = str_replace( $param, $param.'--unused', $name );
            		$id = str_replace( $param, $param.'--unused', $id );
            	}

            	$default_2 = $default_;
            	$default_2a = $default_2;
            	$datas = array();

            	$foundit = false;
            	if ( isset($min_to) && ! empty($min_to) && is_array($min_to) ) {
					$datas['min_to'] = $min_to;
					if ( ! $foundit && $min_to[0] === $default_ ) {
						$default_ = $min;
						$default_2a = isset($min_to[1]) ? $min_to[1] : $default_;
						$foundit = true;
					}
            	}
            	if ( isset($max_to) && ! empty($max_to) && is_array($max_to) ) {
					$datas['max_to'] = $max_to;
					if ( ! $foundit && $max_to[0] === $default_ ) {
						$default_ = $max;
						$default_2a = isset($max_to[1]) ? $max_to[1] : $default_;
						$foundit = true;
					}
            	}
            	if ( isset($val_to) && ! empty($val_to) && is_array($val_to) ) {
					$datas['val_to'] = $val_to;
					if ( ! $foundit ) {
						$default_ = str_replace( sprintf( $val_to[0], '' ), '', $default_ );
						$default_2a = isset($val_to[1]) ? $val_to[1] : $default_;
						$default_2a = sprintf( $default_2a, $default_ );
						$foundit = true;
					}
            	}

            	$datas = json_encode( $datas );
            	$datas = ' data-pms=\'' . $datas . '\'';
            }

            if ( 'textarea' == $type ) {
            	$html[] = '<textarea name="' . $name . '" id="' . $id . '" placeholder="' . $placeholder . '" class="' . $css_class . '" style="' . $css_style . '">' . $default_ . '</textarea>';
            }
            else {
            	$is_readonly = $readonly ? ' readonly="readonly"' : '';

            	$input = '<input' . $is_readonly . ' type="' . $type . '" name="' . $name . '" id="' . $id . '" value="' . $default_ . '" placeholder="' . $placeholder . '" class="' . $css_class . '" style="' . $css_style . '"%s>';

            	if ( 'range' == $type ) {
            		$inp_replace = ' min="' . $min . '" max="' . $max . '" step="' . $step . '"';
            		$inp_replace .= $datas;
            		$input = sprintf( $input, $inp_replace );

            		// output element associated
            		if ( isset($output) && ! empty($output) ) {
            			if ( true === $output ) {
							$output = '<output class="range-slider__value">{default}</output>';
            			}
           				$output = str_replace( '{default}', $default_2a, $output );
            			$input .= $output;
            		}

            		// hidden input associated
            		if ( isset($hidden) && ! empty($hidden) ) {
            			if ( true === $hidden ) {
							$hidden = '<input type="hidden" name="' . $name_ . '" id="' . $id_ . '" value="{default}" style="display: none;">';
            			}
           				$hidden = str_replace( '{default}', $default_2, $hidden );
            			$input .= $hidden;
            		}

            		// custom wrapper
					if ( isset($custom) && ! empty($custom) ) {
            			if ( true === $custom ) {
							$custom = '<div class="range-slider">{input}</div>';
            			}
           				$input = str_replace( '{input}', $input, $custom );
					}
            	}
            	else if ( 'checkbox' == $type || 'radio' == $type ) {
            		$is_checked = $value == $default_ ? true : false;
            		$is_checked_ = $is_checked ? ' checked="checked"' : '';
            		$inp_replace = "$is_checked_";

            		// data html5 attribute
            		$datas = array();
            		if ( isset($value_not) ) {
            			$datas['value_not'] = $value_not;
            		}
            		$datas = json_encode( $datas );
            		$datas = ' data-pms=\'' . $datas . '\'';
            		$inp_replace .= $datas;

            		$input = str_replace( 'value="' . $default_ . '"', 'value="' . $value . '"', $input );
            		$input = sprintf( $input, $inp_replace );

            		// label element associated
            		if ( isset($label) && ! empty($label) ) {

            			if ( 'checkbox' == $type ) {
	            			$is_checked__ = $is_checked ? 'wz-checked' : '';
	            			if ( true === $label ) {
								$label = '<label class="{is_checked}" for="' . $id . '"></label>';
	            			}
	           				$label = str_replace( '{is_checked}', $is_checked__, $label );

	           				$input .= $label;
           				}
           				else if ( 'radio' == $type ) {
           					$label = '<label class="control control--radio">{text}{input}<div class="control__indicator"></div></label>';

           					$label = str_replace( '{text}', $text, $label );
           					$label = str_replace( '{input}', $input, $label );

           					$input = $label;
           				}
            		}

            		// custom wrapper
					if ( isset($custom) && ! empty($custom) && 'checkbox' == $type ) {
            			if ( true === $custom ) {
							$custom = '<div class="checkbox">{input}</div>';
            			}
           				$input = str_replace( '{input}', $input, $custom );
					}
            	}
            	else {
            		$input = sprintf( $input, '' );
            	}

            	$html[] = $input;
            }

            return implode('', $html);
        }

        public function build_form_textarea( $param, $extra=array() ) {
        	$extra = array_replace_recursive(array(
        		'type'		=> 'textarea',
        		'css_class'	=> 'wz-textarea',
        	), $extra);
        	return $this->build_form_input( $param, $extra );
        }

        public function build_form_input_text( $param, $extra=array() ) {
        	$extra = array_replace_recursive(array(
        		'type'		=> 'text',
        		'css_class'	=> 'wz-textinput large',
        	), $extra);
        	return $this->build_form_input( $param, $extra );
        }

        public function build_form_input_range( $param, $extra=array() ) {
        	$extra = array_replace_recursive(array(
        		'type'		=> 'range',
        		'css_class'	=> 'range-slider__range',
        		'min'		=> 0,
        		'max'		=> 100,
        		'step'		=> 1,
        		'output'	=> true, // the associated ouput element, which shows the current value
        		'custom'	=> true, // custom wrapper around this field
        		'hidden'	=> true, // add an associated hidden input for non-integer values
        		'min_to'	=> array(), // convert integer min to ... and add it to hidden input
        		'max_to'	=> array(), // convert integer max to ... and add it to hidden input
        		'val_to'	=> array(), // convert integer val to ... and add it to hidden input
        	), $extra);
        	return $this->build_form_input( $param, $extra );
        }

        public function build_form_input_checkbox( $param, $extra=array() ) {
        	$extra = array_replace_recursive(array(
        		'type'		=> 'checkbox',
				'css_class'	=> '',
        		'value'		=> 'yes', // the checkbox value attribute - not default
        		'value_not' => 'no', // the checkbox value attribute when not checked
        		'label'		=> true, // the associated label element, when you do a custom design checkbox
        		'custom'	=> true, // custom wrapper around this field
        	), $extra);
        	return $this->build_form_input( $param, $extra );
        }

        public function build_form_input_radio( $param, $extra=array() ) {
        	$extra = array_replace_recursive(array(
        		'type'		=> 'radio',
				'css_class'	=> '',
        		'value'		=> 'yes', // the radio value attribute - not default
        		'text'		=> '', // associated text
        		'label'		=> true, // the associated label element, when you do a custom design checkbox
        		'custom'	=> true, // custom wrapper around this field
        	), $extra);
        	return $this->build_form_input( $param, $extra );
        }

        public function build_form_input_radio_group( $param, $extra=array() ) {
        	$extra = array_replace_recursive(array(
        		'type'		=> 'radio',
				'css_class'	=> '',
        		'value'		=> 'yes', // the radio value attribute - not default
        		'text'		=> '', // associated text
        		'label'		=> true, // the associated label element, when you do a custom design checkbox
        		'custom'	=> true, // custom wrapper around this field
        		'values'	=> array(),
        	), $extra);
        	extract($extra);

            $html = array();
            if ( empty($values) || ! is_array($values) ) {
            	return '';
            }

            foreach ($values as $k => $v) {
                $html[] = $this->build_form_input_radio($param, array(
					'value'		=> $k,
					'text' 		=> $v,
				));
            }

        	return implode('', $html);
        }

        public function __build_form_elem_name( $name, $prefix='' ) {
        	if ( empty($prefix) ) {
        		return $name;
        	}

        	if ( ($found = strpos($name, '[')) !== false ) {
        		return $prefix.'[' . substr($name, 0, $found) . ']' . substr($name, $found);
        	}
        	else {
        		return $prefix.'[' . $name . ']';
        	}
        }

        public function __build_form_elem_id( $name ) {
        	$id = str_replace( ']', '', str_replace('[', '-', $name) );
        	return $id;
        }


		/**
		 * System Requirements
		 *
		 */
		public function __let_to_num($size) {
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

		public function __format_msg( $type=1, $pms=array() ) {
			$pms = array_replace_recursive(array(
				'msg_stat'		=> '',
				'msg_text'		=> '',
			), $pms);
			extract( $pms );

			switch ($type) {
				case 1:
					$msg_stat = 'valid' == $msg_stat ? 'wz-success' : 'wz-error';
					$str = '<p class="{msg_stat}">{msg_text}</p>';
					break;

				case 2:
					$msg_stat = 'valid' == $msg_stat ? 'WooZoneLite-success' : 'WooZoneLite-error';
					$str = '<div class="WooZoneLite-message {msg_stat}">{msg_text}</div>';
					break;
			}

			$str = str_replace( '{msg_stat}', $msg_stat, $str );
			$str = str_replace( '{msg_text}', $msg_text, $str );
			return $str;
		}

        // test wp-cron status on your webiste by performing a test spawn (cached for 1 hour where success).
        public function test_cron_spawn( $cache=true ) {

            if ( defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON )
                return true;
    
            $cached_status = get_transient( 'WooZoneLite-cronjobs-test-ok' );
    
            if ( $cache && $cached_status )
                return true;
    
            $doing_wp_cron = sprintf( '%.22F', microtime( true ) );
    
            $cron_request = apply_filters( 'cron_request', array(
                'url'  => site_url( 'wp-cron.php?doing_wp_cron=' . $doing_wp_cron ),
                'key'  => $doing_wp_cron,
                'args' => array(
                    'timeout'   => 3,
                    'blocking'  => true,
                    'sslverify' => apply_filters( 'https_local_ssl_verify', true )
                )
            ) );
    
            $cron_request['args']['blocking'] = true;
    
            $result = wp_remote_post( $cron_request['url'], $cron_request['args'] );
    
            if ( is_wp_error( $result ) ) {
                return $result;
            } else {
                set_transient( 'WooZoneLite-cronjobs-test-ok', 1, 3600 );
                return true;
            }
        }

        public function woo_check_pages( $pms=array() ) {
			$def = array(
				'what_pages'		=> array( 'woo_cart', 'woo_checkout' ),
			);
			if ( ! isset($pms['what_pages']) ) {
				$pms['what_pages'] = $def['what_pages'];
			}
			$pms = array_replace_recursive($pms, array());
			extract( $pms );

			$ret = array(
				'status'		=> 'invalid',
				'html'			=> '',
			);

			// code for woocommerce pages/shortcodes check
			{
				//$current = get_option( $this->the_plugin->alias . "_dismiss_notice", array() );
				//$current = !empty($current) && is_array($current) ? $current : array();

				$check_pages = array();
				if ( in_array('woo_cart', $what_pages) ) {
					$check_pages = array_replace_recursive($check_pages, array(
						_x( 'Cart Page', 'Page setting', 'woocommerce' ) => array(
							'option' => 'woocommerce_cart_page_id',
							'shortcode' => '[' . apply_filters( 'woocommerce_cart_shortcode_tag', 'woocommerce_cart' ) . ']'
						),
					));
				}
				if ( in_array('woo_checkout', $what_pages) ) {
					$check_pages = array_replace_recursive($check_pages, array(
						_x( 'Checkout Page', 'Page setting', 'woocommerce' ) => array(
							'option' => 'woocommerce_checkout_page_id',
							'shortcode' => '[' . apply_filters( 'woocommerce_checkout_shortcode_tag', 'woocommerce_checkout' ) . ']'
						),
					));
				}

				if ( class_exists( 'WooCommerce' ) ) {

					foreach ( $check_pages as $page_name => $values ) {
  
  						$_status = 'valid';

						$page_id = get_option( $values['option'], false );
	
						// Page ID check
						if ( ! $page_id ) {
							$_status = 'invalid';
				            $ret = array_replace_recursive(array(
				            	'status'		=> $_status,
				            	'html'			=> sprintf( __( 'You need to install default WooCommerce page: %s', 'woocommerce' ), $page_name ),
				            ));
						} else {
							// Shortcode check
							if ( $values['shortcode'] ) {
								$page = get_post( $page_id );
								$_wpnonce_untrash = wp_create_nonce( 'untrash-post_' . $page_id );
				
								//var_dump('<pre>',$page ,'</pre>'); 
								if ( empty( $page ) ) {
									//if( !isset($current['pageinstall']) || !$current['pageinstall'] ){
										// | <a class="dismiss-notice" href="' . ( admin_url( 'admin-ajax.php?action=WooZoneLiteDismissNotice&id=pageinstall' ) ) . '" target="_parent">Dismiss this notice</a>
										$_status = 'invalid';
							            $ret = array_replace_recursive(array(
							            	'status'		=> $_status,
							            	'html'			=> sprintf( '<strong>%s</strong> page does not exist. Please install Woocommerce default pages from <a href="' . admin_url('admin.php?page=wc-status&tab=tools') . '" target="_blank">here</a>.', $page_name ),
							            ));
									//}
								} elseif ( ! strstr( $page->post_content, $values['shortcode'] ) ) {
									//if( !isset($current['pageshortcode']) || !$current['pageshortcode'] ){
										// | <a class="dismiss-notice" href="' . ( admin_url( 'admin-ajax.php?action=WooZoneLiteDismissNotice&id=pageshortcode' ) ) . '" target="_parent">Dismiss this notice</a>
										$_status = 'invalid';
							            $ret = array_replace_recursive(array(
							            	'status'		=> $_status,
							            	'html'			=> 'The <strong>' . $page->post_title . '</strong> page does not contain the shortcode: <strong>' . $values['shortcode'] . '</strong>',
							            ));
									//}
								} elseif ( $page->post_status == 'trash' ) {
									//if( !isset($current['pagetrash']) || !$current['pagetrash'] ){
										// | <a class="dismiss-notice" href="' . ( admin_url( 'admin-ajax.php?action=WooZoneLiteDismissNotice&id=pagetrash' ) ) . '" target="_parent">Dismiss this notice</a>
										$_status = 'invalid';
							            $ret = array_replace_recursive(array(
							            	'status'		=> $_status,
							            	'html'			=> 'The <strong>' . $page->post_title . '</strong> Woocommerce default page is in trash. Please <a href="' . admin_url('post.php?post=' . $page_id . '&action=untrash&_wpnonce=' . $_wpnonce_untrash) . '" target="_blank">restore it</a>.',
							            ));
									//}
								}
							} // end Shortcode check
						}

						if ( 'valid' == $_status ) {
							$ret = array_replace_recursive(array(
							   	'status'		=> 'valid',
							   	'html'			=> '#' . absint( $page_id ) . ' - ' . str_replace( home_url(), '', get_permalink( $page_id ) ),
							));
						}
					} // end foreach
				}
			} // end code for woocommerce pages/shortcodes check

			return $ret;
        }

		public function sr_check_memory_limit( $pms=array() ) {
			$pms = array_replace_recursive(array(
				'type'		=> 1,
			), $pms);
			extract( $pms );

			$ret = array(
				'status'		=> 'invalid',
				'html'			=> '',
			);

			$memory = $this->__let_to_num( WP_MEMORY_LIMIT );
			
			$html = array();
            if ( $memory < 127108864 ) {
            	$status = 'invalid';
            	$html[] = $this->__format_msg($type, array(
            		'msg_stat'	=> $status,
            		'msg_text'	=> sprintf( __( '%s - We recommend setting memory to at least 128MB. See: <a href="%s" target="_blank" class="WooZoneLite-form-button WooZoneLite-form-button-info">Increasing memory allocated to PHP</a>', $this->the_plugin->localizationName ), size_format( $memory ), 'http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP' ),
            	));
            }
            else {
            	$status = 'valid';
            	$html[] = $this->__format_msg($type, array(
            		'msg_stat'	=> $status,
            		'msg_text'	=> size_format( $memory ),
            	));
            }
            $html = implode(PHP_EOL, $html);

            $ret = array_replace_recursive($ret, array(
            	'status'		=> $status,
            	'html'			=> $html,
            ));
            return $ret;
		}

		public function sr_check_soap( $pms=array() ) {
			$pms = array_replace_recursive(array(
				'type'		=> 1,
			), $pms);
			extract( $pms );

			$ret = array(
				'status'		=> 'invalid',
				'html'			=> '',
			);

			$html = array();
			if ( extension_loaded('soap') || class_exists("SOAPClient") || class_exists("SOAP_Client") ) {
				$status = 'valid';
				$html[] = $this->__format_msg($type, array(
            		'msg_stat'	=> $status,
            		'msg_text'	=> __('Your server has the SOAP Client class enabled.', $this->the_plugin->localizationName ),
            	));
			} else {
        		$status = 'invalid';
        		$html[] = $this->__format_msg($type, array(
            		'msg_stat'	=> $status,
            		'msg_text'	=> sprintf( __( 'Your server does not have the <a href="%s" target="_blank">SOAP Client</a> class enabled - some gateway plugins which use SOAP may not work as expected.', $this->the_plugin->localizationName ), 'http://php.net/manual/en/class.soapclient.php' ),
            	));
        	}
            $html = implode(PHP_EOL, $html);

            $ret = array_replace_recursive($ret, array(
            	'status'		=> $status,
            	'html'			=> $html,
            ));
            return $ret;
		}

		public function sr_check_simplexml( $pms=array() ) {
			$pms = array_replace_recursive(array(
				'type'		=> 1,
			), $pms);
			extract( $pms );

			$ret = array(
				'status'		=> 'invalid',
				'html'			=> '',
			);

			$html = array();
			if ( function_exists('simplexml_load_string') ) {
				$status = 'valid';
				$html[] = $this->__format_msg($type, array(
            		'msg_stat'	=> $status,
            		'msg_text'	=> __('Your server has the SimpleXML library enabled.', $this->the_plugin->localizationName ),
            	));
			} else {
        		$status = 'invalid';
        		$html[] = $this->__format_msg($type, array(
            		'msg_stat'	=> $status,
            		'msg_text'	=> sprintf( __( 'Your server does not have the <a href="%s" target="_blank">SimpleXML</a> library enabled - some gateway plugins which use SimpleXML library may not work as expected.', $this->the_plugin->localizationName ), 'http://php.net/manual/en/book.simplexml.php' ),
            	));
        	}
            $html = implode(PHP_EOL, $html);

            $ret = array_replace_recursive($ret, array(
            	'status'		=> $status,
            	'html'			=> $html,
            ));
            return $ret;
		}

		public function sr_check_remote_get( $pms=array() ) {
			$pms = array_replace_recursive(array(
				'type'		=> 1,
			), $pms);
			extract( $pms );

			$ret = array(
				'status'		=> 'invalid',
				'html'			=> '',
			);

			// WP Remote Get Check
			$params = array(
				'sslverify' 	=> false,
	        	'timeout' 		=> 20,
	        	'body'			=> isset($request) ? $request : array()
			);
			$response = wp_remote_post( 'http://webservices.amazon.com/AWSECommerceService/AWSECommerceService.wsdl', $params );

			$html = array();
			if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
        		$msg = __('wp_remote_get() was successful - Webservices Amazon is working.', $this->the_plugin->localizationName );
				$status = 'valid';
        	} elseif ( is_wp_error( $response ) ) {
        		$msg = __( 'wp_remote_get() failed. Webservices Amazon won\'t work with your server. Contact your hosting provider. Error:', $this->the_plugin->localizationName ) . ' ' . $response->get_error_message();
				$status = 'invalid';
        	} else {
            	$msg = __( 'wp_remote_get() failed. Webservices Amazon may not work with your server.', $this->the_plugin->localizationName );
				$status = 'invalid';
        	}
			$html[] = $this->__format_msg($type, array(
           		'msg_stat'	=> $status,
           		'msg_text'	=> $msg,
           	));
            $html = implode(PHP_EOL, $html);

            $ret = array_replace_recursive($ret, array(
            	'status'		=> $status,
            	'html'			=> $html,
            ));
            return $ret;
		}

		public function sr_check_cron_status( $pms=array() ) {
			$pms = array_replace_recursive(array(
				'type'		=> 1,
			), $pms);
			extract( $pms );

			$ret = array(
				'status'		=> 'invalid',
				'html'			=> '',
			);

			$cron_status = $this->test_cron_spawn();

			$html = array();
            if ( is_wp_error( $cron_status ) ) {
                $msg = sprintf( __( 'Issue encountered when trying to spawn a call to the WP-Cron system on your website. The WP-Cron jobs on your website may not work. The issue details: %s', 'WooZoneLite' ), '<br /><strong>' . esc_html( $cron_status->get_error_message() ) . '</strong>' );
				$status = 'invalid';
            } else {
                $msg = sprintf( __( 'Successfully spawn a call to the WP-Cron system on your website. The WP-Cron jobs on your website seems to work fine.', 'WooZoneLite' ) );
                $status = 'valid';
            }
			$html[] = $this->__format_msg($type, array(
           		'msg_stat'	=> $status,
           		'msg_text'	=> $msg,
           	));
            $html = implode(PHP_EOL, $html);

            $ret = array_replace_recursive($ret, array(
            	'status'		=> $status,
            	'html'			=> $html,
            ));
            return $ret;
		}

		public function sr_check_woo_page_cart( $pms=array() ) {
			$pms = array_replace_recursive(array(
				'type'		=> 1,
			), $pms);
			extract( $pms );

			$ret = array(
				'status'		=> 'invalid',
				'html'			=> '',
			);

			$checkit = $this->woo_check_pages(array( 'what_pages' => array('woo_cart') ));
			$checkit['html'] = $this->__format_msg($type, array(
           		'msg_stat'	=> $checkit['status'],
           		'msg_text'	=> $checkit['html'],
           	));

            $ret = array_replace_recursive($ret, $checkit);
            return $ret;
		}

		public function sr_check_woo_page_checkout( $pms=array() ) {
			$pms = array_replace_recursive(array(
				'type'		=> 1,
			), $pms);
			extract( $pms );

			$ret = array(
				'status'		=> 'invalid',
				'html'			=> '',
			);

			$checkit = $this->woo_check_pages(array( 'what_pages' => array('woo_checkout') ));
			$checkit['html'] = $this->__format_msg($type, array(
           		'msg_stat'	=> $checkit['status'],
           		'msg_text'	=> $checkit['html'],
           	));

            $ret = array_replace_recursive($ret, $checkit);
            return $ret;
		}

		public function sr_check( $pms=array() ) {
			$pms = array_replace_recursive(array(
				'type'		=> 1,
				'what'		=> array(
					'check_memory_limit',
					'check_soap',
					'check_simplexml',
					'check_remote_get',
					'check_cron_status',
					'check_woo_page_cart',
					'check_woo_page_checkout',
				),
			), $pms);
			extract( $pms );

			$cache_sr = get_transient( 'WooZoneLite-Wizard-System-Requirements' );
			if ( is_array($cache_sr) && ! empty($cache_sr) ) {
				return $cache_sr;
			}

			$ret = array(
				'status'		=> 'invalid',
				'check'			=> array(),
			);

			$check = array();

			$status = true;
			foreach ($what as $what_) {
				$func = 'sr_'.$what_;
				$check["$what_"] = $this->$func(array(
					'type'		=> $type,
				));

				$_status = $check["$what_"]['status'];
				$_status = 'valid' == $_status ? true : false;
				$status = $status && $_status;
			}

            $ret = array_replace_recursive(array(
            	'status'		=> $status ? 'valid' : 'invalid',
            	'check'			=> $check,
            ));
            set_transient( 'WooZoneLite-Wizard-System-Requirements', $ret, 600 ); // expire in seconds
			return $ret;
		}


		/**
		 * Check Amazon Keys
		 *
		 */
        // setup amazon object for making request
        public function setupAmazonWS( $params=array() ) {
        	global $WooZoneLite;

        	//$this->amz_settings = $this->the_plugin->getAllSettings('array', 'amazon');
            $settings = $WooZoneLite->settings();

            $def = array(
            	'AccessKeyID' 		=> 'zzz',
            	'SecretAccessKey' 	=> 'zzz',
            	'country'			=> 'com',
            	'main_aff_id' 		=> 'aateam',
            );
            foreach ($def as $key => $val) {
            	if ( ! isset($settings["$key"]) || ('' == $settings["$key"]) ) {
            		$settings["$key"] = $val;
            	}
            }

            //:: overwrite plugin settings
            $settings_new = $settings;
            foreach ($params as $key => $val) {
           		$settings_new["$key"] = $val;
            }
			$WooZoneLite->build_amz_settings($settings_new);
			$this->the_plugin = $WooZoneLite;
			//:: end overwrite plugin settings

			/*
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
            */
            //if ( ! is_object($this->amzHelper) ) {
            if (1) {
				require_once( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'aa-framework/amz.helper.class.php' );
				if ( class_exists('WooZoneLiteAmazonHelper') ) {
					//$this->amzHelper = WooZoneLiteAmazonHelper::getInstance( $this->the_plugin );
					$this->amzHelper = new WooZoneLiteAmazonHelper( $this->the_plugin ); //$this->the_plugin | $WooZoneLite
				}
			}
            $params_new = array(
				'AccessKeyID'		=> isset($params['AccessKeyID']) ? $params['AccessKeyID'] : $settings['AccessKeyID'],
				'SecretAccessKey'	=> isset($params['SecretAccessKey']) ? $params['SecretAccessKey'] : $settings['SecretAccessKey'],
				'country'			=> isset($params['country']) ? $params['country'] : $settings['country'],
				'main_aff_id'		=> isset($params['main_aff_id']) ? $params['main_aff_id'] : $this->the_plugin->main_aff_id(),
            );
            $_status = $this->the_plugin->verify_amazon_keys( $params_new );
            $params_new = $_status['settings'];

            //$this->amzHelper->setupAmazonWS( $params_new );
            $this->amzHelper->init_settings( $params_new, true );
			$this->aaAmazonWS = $this->amzHelper->aaAmazonWS;
        }


        /**
         * Ajax
         *
         */
        public function load_step( $pms=array() ) {
			$pms = array_replace_recursive(array(
				'step'		=> 'index',
			), $pms);
			extract( $pms );

			// init
			$this->wizard_init();

			$step_html = array();

			/*
			//DEBUG
			ob_start();
			var_dump('<pre>', $this->save_status ,'</pre>');
			$step_html[] = '<div>' . ob_get_clean() . '</div>';
			*/

			//$step_html[] = '<div>';

        	ob_start();

        	//:: header
			$filename = $this->module_folder_path . 'steps/_header_html.php';
			require_once( $filename );

			//:: content
			$this->setup_wizard_content();

			//:: footer
			$filename = $this->module_folder_path . 'steps/_footer_html.php';
			require_once( $filename );

			$step_html[] = ob_get_clean();

			//$step_html[] = '</div>';

			$step_html = implode(PHP_EOL, $step_html);
			return $step_html;
        }
	}
}

// Initialize the WooZoneLiteWizard class
$WooZoneLiteWizard = WooZoneLiteWizard::getInstance();