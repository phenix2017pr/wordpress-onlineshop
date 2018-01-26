<?php
/*
* Define class WooZoneLiteFrontend
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
!defined('ABSPATH') and exit;
   
if (class_exists('WooZoneLiteFrontend') != true) {
    class WooZoneLiteFrontend
    {
        /*
        * Some required plugin information
        */
        const VERSION = '1.0';
		
        /*
        * Store some helpers config
        */
        public $the_plugin 		= null;
		public $amzHelper 	= null;
		public $is_admin		= null;

		public $amz_settings = array();
		public $p_type = null;
		public $countryflags_aslink = false;

        static protected $_instance;

		public $alias;
		public $localizationName;

		private $current_theme = null;
		

        /*
        * Required __construct() function that initalizes the AA-Team Framework
        */
        public function __construct( $parent )
        {
            $this->the_plugin = $parent;
			$this->amzHelper = $this->the_plugin->amzHelper;
			$this->is_admin = $this->the_plugin->is_admin;
			
			$this->amz_settings = $this->the_plugin->amz_settings;
			$this->p_type = isset($this->amz_settings['onsite_cart']) && $this->amz_settings['onsite_cart'] == "no" ? 'external' : 'simple';
			$this->countryflags_aslink = isset($this->amz_settings['product_countries_countryflags'])
				&& $this->amz_settings['product_countries_countryflags'] == "yes" ? true : false;

			$this->alias = $this->the_plugin->alias;
			$this->localizationName = $this->the_plugin->localizationName;
			
			$this->current_theme = wp_get_theme(); //get_current_theme() - deprecated notice!
			//var_dump('<pre>',$this->current_theme,'</pre>');  

			// wp actions - frontend
			if ( ! $this->is_admin ) {
				add_action( 'init' , array( $this, 'init' ) );

				// cross sell shortcode
				add_shortcode( 'amz_corss_sell', array($this, 'cross_sell_box') );
			}
			// executed only on frontend
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
			
			// woocommerce fix thumb for remote images with https - on frontend
			add_action( 'woocommerce_before_mini_cart', array( $this, 'woocommerce_before_mini_cart' ) );

			// wp ajax actions
			add_action('wp_ajax_WooZoneLite_frontend', array( $this, 'ajax_requests') );
			add_action('wp_ajax_nopriv_WooZoneLite_frontend', array( $this, 'ajax_requests') );

			// checkout email: wp ajax actions
			if ( $this->p_type == 'simple' ) {
				if ( isset($this->amz_settings['checkout_email']) && $this->amz_settings['checkout_email'] == 'yes' ) {
					add_action( 'wp_ajax_WooZoneLite_before_user_checkout', array( $this, 'woocommerce_ajax_before_user_checkout') );
					add_action( 'wp_ajax_nopriv_WooZoneLite_before_user_checkout', array( $this, 'woocommerce_ajax_before_user_checkout') );
				}
			}
			
			// cross sell checkout - !needs to be bellow Amazon helper
			$this->cross_sell_checkout();
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
		 * Inits...
		 */
		// wp enqueue scripts & stypes
		public function wp_enqueue_scripts() {

			if( !wp_style_is($this->alias . '-frontend-style') ) {
				wp_enqueue_style( $this->alias . '-frontend-style', $this->the_plugin->cfg['paths']['plugin_dir_url'] . 'lib/frontend/css/frontend.css' );
			}
			
			if( !wp_script_is($this->alias . '-frontend-script') ) {
				wp_enqueue_script( $this->alias . '-frontend-script' , $this->the_plugin->cfg['paths']['plugin_dir_url'] . 'lib/frontend/js/frontend.js', array( 'jquery' ) );

				$_checkout_url = wc_get_checkout_url();
				$_checkout_url = is_string($_checkout_url) ? esc_url( $_checkout_url ) : '';

				$vars = array(
					'ajax_url'				=> admin_url('admin-ajax.php'),
					'checkout_url' 		=> $_checkout_url,
					'lang' 					=> array(
						'loading'								=> __('Loading...', $this->localizationName),
						'closing'                   				=> __('Closing...', $this->localizationName),
						'saving'                   				=> __('Saving...', $this->localizationName),
						'amzcart_checkout'       		=> __('checkout done', $this->localizationName),
						'amzcart_cancel' 					=> __('canceled', $this->localizationName),
						'amzcart_checkout_msg'		=> __('all good.', $this->localizationName),
						'amzcart_cancel_msg'			=> __('You must check or cancel all amazon shops!', $this->localizationName),
						'available_yes'						=> __('available', $this->localizationName),
						'available_no' 						=> __('not available', $this->localizationName),
						'load_cross_sell_box'			=> __('Frequently Bought Together Loading...', $this->localizationName),
					),
				);
				wp_localize_script( 'WooZoneLite-frontend-script', 'woozone_vars', $vars );
			}
		}

		// wp 'init' hook
		public function init() {
			add_action( 'wp_footer', array( $this, 'wp_footer' ), 1 );
			
			//::::::::::::::::::::::::::::::::::::
			// start box with product country check
			$is_country_check = ( ! isset($this->amz_settings['product_countries'])
				|| 'yes' == $this->amz_settings['product_countries'] ? true : false );
			if ( $is_country_check ) {

				// single product page
				$box_countries_pos = isset($this->amz_settings['product_countries_main_position'])
					? $this->amz_settings['product_countries_main_position'] : 'before_add_to_cart';
				/**
				 * woocommerce_single_product_summary hook
				 *
				 * @hooked woocommerce_template_single_title - 5
				 * @hooked woocommerce_template_single_rating - 10
				 * @hooked woocommerce_template_single_price - 10
				 * @hooked woocommerce_template_single_excerpt - 20
				 * @hooked woocommerce_template_single_add_to_cart - 30
				 * @hooked woocommerce_template_single_meta - 40
				 * @hooked woocommerce_template_single_sharing - 50
				 */
				switch ($box_countries_pos) {
					case 'before_add_to_cart':
						add_action( 'woocommerce_single_product_summary', array($this, 'woocommerce_single_product_summary'), 21 );
						if ( 'Kingdom - Woocommerce Amazon Affiliates Theme' == $this->current_theme ) {
							add_action( 'WooZoneLite_frontend_footer', array( $this, '__before_add_to_cart' ), 1 );
						}
						break;
					
					case 'before_title_and_thumb':
						add_action( 'WooZoneLite_frontend_footer', array( $this, '__before_title_and_thumb' ), 1 );
						break;

					case 'before_woocommerce_tabs':
						add_action( 'WooZoneLite_frontend_footer', array( $this, '__before_woocommerce_tabs' ), 1 );
						break;
						
					case 'as_woocommerce_tab':
						add_action('woocommerce_product_tabs', array($this, 'woocommerce_product_tabs'), 0);
						break;		
				}

				//$where_country_check = isset($this->amz_settings['product_countries_where'])
				//	? (array) $this->amz_settings['product_countries_where'] : array(); //'maincart', 'minicart'
				$product_countries_maincart = ( ! isset($this->amz_settings['product_countries_maincart'])
				|| 'yes' == $this->amz_settings['product_countries_maincart'] ? true : false );
				$where_country_check = $product_countries_maincart ? array('maincart') : array();

				// view main cart
				if ( in_array('maincart', $where_country_check) )
					add_filter( 'woocommerce_cart_item_quantity', array($this, 'woocommerce_cart_item_quantity'), 10, 3 );

				// view mini cart
				if ( in_array('minicart', $where_country_check) ) {
					add_filter( 'woocommerce_widget_cart_item_quantity', array($this, 'woocommerce_widget_cart_item_quantity'), 10, 3 );
					if ( 'Kingdom - Woocommerce Amazon Affiliates Theme' == $this->current_theme ) {
						add_action( 'WooZoneLite_frontend_footer', array( $this, '__widget_cart_item_quantity' ), 1 );
					}
				}
				
				// cart page
				//add_action( 'woocommerce_after_cart_table', array($this, 'woocommerce_after_cart') ); // don't work - already have a form
				add_action( 'woocommerce_after_cart', array($this, 'woocommerce_after_cart') );
			}
			// end box with product country check
			//::::::::::::::::::::::::::::::::::::
			
			$redirect_cart = (isset($_REQUEST['redirectCart']) && $_REQUEST['redirectCart']) != '' ? $_REQUEST['redirectCart'] : '';
			if( isset($redirect_cart) && $redirect_cart == 'true' ) {
				if ( ! $this->the_plugin->disable_amazon_checkout )
					$this->redirect_cart();
			}

			add_action( 'woocommerce_after_add_to_cart_button', array($this, 'woocommerce_external_add_to_cart'), 10 );

			// non-external products pages
			if ( $this->p_type == 'simple' ) {
				// cart checkout
				if ( ! $this->the_plugin->disable_amazon_checkout ) {
					add_action( 'woocommerce_checkout_init', array($this, 'woocommerce_external_checkout'), 10 );
				}

				// checkout email
				if( isset($this->amz_settings['checkout_email']) && $this->amz_settings['checkout_email'] == 'yes' ) {
					add_filter( 'woocommerce_before_cart_totals', array($this, 'woocommerce_before_checkout'), 10 );
				}
			}
		}

		// 'wp_footer' hook
		public function wp_footer() {
			global $wp_query;
			
			echo PHP_EOL . "<!-- start/ " . ($this->the_plugin->alias) . " wp_footer hook -->" . PHP_EOL;
			
			if ( ! has_action('WooZoneLite_frontend_footer') )
				return true;
			
			do_action( 'WooZoneLite_frontend_footer' );
			
			echo "<!-- end/ " . ($this->the_plugin->alias) . " wp_footer hook -->" . PHP_EOL.PHP_EOL;
			
			return true;
		}
		
		
		/**
		 * Hooks functions
		 */
		// wp 'hooks' functions
		// amazon shops checkout on cart page
		public function woocommerce_after_cart() {
			//$is_cart_page = is_cart();
			//if ( ! $is_cart_page ) return ;
   
			$box = $this->box_amazon_shops_checkout();
			if ( !empty($box) )
				echo $box;
		}

		// product country on product details page
		public function woocommerce_single_product_summary() {
			global $product;
   
			$box = $this->box_country_check_details( $product );
			if ( !empty($box) )
				echo $box;
		}
		
		// product country on main cart
		public function woocommerce_cart_item_quantity($product_quantity, $cart_item_key, $cart_item=null) {
			$str = $product_quantity;

			// theme: kingdom
			if ( empty($cart_item) ) {
				$cart_items_nb = (int) WC()->cart->get_cart_contents_count();
				if ( $cart_items_nb )
					$cart_item = WC()->cart->get_cart_item( $cart_item_key);
			}

			$box = $this->box_country_check_small( isset($cart_item['product_id']) ? $cart_item['product_id'] : 0 );
			if ( !empty($box) ) {
				//$str .= $box;
				$str = str_replace('</div>', $box . '</div>', $str);
			}
			echo $str;
		}
		
		// product country on mini cart
		public function woocommerce_widget_cart_item_quantity($product_quantity, $cart_item, $cart_item_key) {
			$str = $product_quantity;
			$box = $this->box_country_check_small( isset($cart_item['product_id']) ? $cart_item['product_id'] : 0 );
			if ( !empty($box) ) {
				//$str .= $box;
				$str = str_replace('</span></span>', '</span></span>' . $box, $str);
			}
			echo $str;
		}
		public function __widget_cart_item_quantity() {
			$pms = array('box_position' => 'minicart');
			$box = $this->box_country_check_minicart( $pms );
			if ( !empty($box) )
				echo $box;
		}
		
		// main box as woocommerce tab
		public function woocommerce_product_tabs( $tabs ) {
			$tabs['woozone_tab_countries_availability'] = array(
				'title'				=> __( 'Countries availability', $this->localizationName ),
				'priority'		=> 15,
				'callback'		=> array($this, '__woo_tab_countries_availability')
			);

			return $tabs;
		}
		public function __woo_tab_countries_availability( $tab ) {
			global $product;

			$box = $this->box_country_check_details( $product );
			if ( !empty($box) )
				echo $box;
		}

		// main box positioning
		public function __single_product_summary( $pms=array() ) {
			$is_product_page = is_product();
			if ( !$is_product_page ) return;

			global $product;

			$box = $this->box_country_check_details( $product, $pms );
			if ( !empty($box) )
				echo $box;
		}
		public function __before_add_to_cart() {
			$this->__single_product_summary( array('box_position' => 'before_add_to_cart') );
		}
		public function __before_title_and_thumb() {
			$this->__single_product_summary( array('box_position' => 'before_title_and_thumb') );
		}
		public function __before_woocommerce_tabs() {
			$this->__single_product_summary( array('box_position' => 'before_woocommerce_tabs') );
		}
		
		
		/**
		 * box: product country check
		 */
		// build minicart box with product country check
		private function box_country_check_minicart( $pms=array() ) {
			// parameters
			$pms = array_merge(array(
				'with_wrapper'			=> true,
				'box_position'			=> false,
			), $pms);
			extract($pms);
			
			// theme: kingdom
			$cart_items_nb = (int) WC()->cart->get_cart_contents_count();
			if ( !$cart_items_nb )
				return false;

			$minicart_items = array();

			$cart_items = WC()->cart->get_cart();
			foreach ( $cart_items as $key => $value ) {

				//$prod_id = isset($value['variation_id']) && (int)$value['variation_id'] > 0 ? $value['variation_id'] : $value['product_id'];
				$product_id = $value['product_id'];

				$asin = get_post_meta( $product_id, '_amzASIN', true );
				if ( empty($asin) ) continue 1;

				$product_country = $this->get_product_country_current( $product_id );
				$product_country__ = $product_country;
				if ( !empty($product_country) && isset($product_country['website']) ) {
					$product_country = substr($product_country['website'], 1);
				}
				
				$country_name = $product_country__['name'];
				
				$country_status = $product_country__['available'];
				$country_status_css = 'available-todo'; $country_status_text = __('not verified yet', $this->localizationName);
				switch ($country_status) {
					case 1:
						$country_status_css = 'available-yes';
						$country_status_text = __('is available', $this->localizationName);
						break;
						
					case 0:
						$country_status_css = 'available-no';
						$country_status_text = __('not available', $this->localizationName);
						break;
				}
				
				$minicart_items[] = array(
					'cart_item_key'				=> $key,
					'product_id'					=> $product_id,
					'asin'								=> $asin,
					'product_country'			=> $product_country,
					'country_name'				=> $country_name,
					'country_status_css'		=> $country_status_css,
					'country_status_text'	=> $country_status_text,
				);
			}

			ob_start();
		?>

<div class="WooZoneLite-cc-small-cached" style="display: none;"><?php echo json_encode( $minicart_items ); ?></div>
<script type="text/template" id="WooZoneLite-cc-small-template">
	<span class="WooZoneLite-country-check-small WooZoneLite-cc-custom">
		
		<span>
			<span class="WooZoneLite-cc_domain"></span>
			<span class="WooZoneLite-cc_status"></span>
		</span>

	</span>
</script>

		<?php
			$contents = ob_get_clean();
			return $contents;
		}

		// build small box with product country check
		private function box_country_check_small( $product, $pms=array() ) {
			// get product id
			$product_id = $product;
			if ( is_object($product) ) {
				$prod_id = 0;
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
				$product_id = $prod_id;
			}
			if ( empty($product_id) ) return false;

			// parameters
			$pms = array_merge(array(
				'with_wrapper'			=> true,
				'box_position'			=> false,
			), $pms);
			extract($pms);

			// get asin meta key
			$asin = get_post_meta($product_id, '_amzASIN', true);
			if ( empty($asin) ) return false; // verify to be amazon product!
			//$asin = 'B000P0ZSHK'; // DEBUG
			//var_dump('<pre>',$asin,'</pre>');

			$product_country = $this->get_product_country_current( $product_id );
			$product_country__ = $product_country;
			//var_dump('<pre>', $product_id, $product_country, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			if ( !empty($product_country) && isset($product_country['website']) ) {
				$product_country = substr($product_country['website'], 1);
			}
			
			//$all_countries_affid = $this->amzHelper->get_countries('main_aff_id');
			//$country_affid = $product_country__['key'];
			//$country_name = isset($all_countries_affid["$country_affid"]) ? $all_countries_affid["$country_affid"] : 'missing country name';
			$country_name = $product_country__['name'];

			$country_status = $product_country__['available'];
			$country_status_css = 'available-todo'; $country_status_text = __('not verified yet', $this->localizationName);
			switch ($country_status) {
				case 1:
					$country_status_css = 'available-yes';
					$country_status_text = __('is available', $this->localizationName);
					break;
					
				case 0:
					$country_status_css = 'available-no';
					$country_status_text = __('not available', $this->localizationName);
					break;
			}

			ob_start();
		?>

<?php if ($with_wrapper) { ?>
<span class="WooZoneLite-country-check-small" data-prodid="<?php echo $product_id; ?>" data-asin="<?php echo $asin; ?>" data-prodcountry="<?php echo $product_country; ?>">
<?php } ?>

		<span>
			<span class="WooZoneLite-cc_domain <?php echo str_replace('.', '-', $product_country); ?>" title="<?php echo $country_name; ?>"></span>
			<span class="WooZoneLite-cc_status <?php echo $country_status_css; ?>" title="<?php echo $country_status_text; ?>"></span>
		</span>

<?php if ($with_wrapper) { ?>
</span>
<?php } ?>

		<?php
			$contents = ob_get_clean();
			return $contents;
		}

		// build main box with product country check
		private function box_country_check_details( $product, $pms=array() ) {
			// get product id
			$product_id = $product;
			if ( is_object($product) ) {
				$prod_id = 0;
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
				$product_id = $prod_id;
			}
			if ( empty($product_id) ) return false;

			// parameters
			$pms = array_merge(array(
				'with_wrapper'			=> true,
				'box_position'			=> false,
			), $pms);
			extract($pms);
			
			// get asin meta key
			$asin = get_post_meta($product_id, '_amzASIN', true);
			if ( empty($asin) ) return false; // verify to be amazon product!
			//$asin = 'B000P0ZSHK'; // DEBUG
			//var_dump('<pre>',$asin,'</pre>');
			
			$available_countries = $this->get_product_countries_available( $product_id );
			//var_dump('<pre>', $product_id, $available_countries, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;   
			if ( empty($available_countries) ) return false;

			$product_country = $this->get_product_country_current( $product_id );
			//var_dump('<pre>', $product_id, $product_country, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			if ( !empty($product_country) && isset($product_country['website']) ) {
				$product_country = substr($product_country['website'], 1);
			}
			
			// aff ids
			$aff_ids = $this->the_plugin->get_aff_ids();

			ob_start();
		?>

<?php if ($with_wrapper) { ?>
<ul class="WooZoneLite-country-check" data-prodid="<?php echo $product_id; ?>" data-asin="<?php echo $asin; ?>" data-prodcountry="<?php echo $product_country; ?>" data-boxpos="<?php echo $box_position; ?>" <?php echo !empty($box_position) ? 'style="display: none;"' : ''; ?>>
<?php } ?>

	<div class="WooZoneLite-country-cached" style="display: none;"><?php echo json_encode( $available_countries ); ?></div>
	<div class="WooZoneLite-country-affid" style="display: none;"><?php echo json_encode( $aff_ids ); ?></div>
	<div class="WooZoneLite-country-loader">
		<div>
			<div id="floatingBarsG">
				<div class="blockG" id="rotateG_01"></div>
				<div class="blockG" id="rotateG_02"></div>
				<div class="blockG" id="rotateG_03"></div>
				<div class="blockG" id="rotateG_04"></div>
				<div class="blockG" id="rotateG_05"></div>
				<div class="blockG" id="rotateG_06"></div>
				<div class="blockG" id="rotateG_07"></div>
				<div class="blockG" id="rotateG_08"></div>
			</div>
			<div class="WooZoneLite-country-loader-text"></div>
		</div>
	</div>
	<div class="WooZoneLite-country-loader bottom">
		<div>
			<div id="floatingBarsG">
				<div class="blockG" id="rotateG_01"></div>
				<div class="blockG" id="rotateG_02"></div>
				<div class="blockG" id="rotateG_03"></div>
				<div class="blockG" id="rotateG_04"></div>
				<div class="blockG" id="rotateG_05"></div>
				<div class="blockG" id="rotateG_06"></div>
				<div class="blockG" id="rotateG_07"></div>
				<div class="blockG" id="rotateG_08"></div>
			</div>
			<div class="WooZoneLite-country-loader-text"></div>
		</div>
	</div>
	<div style="display: none;" id="WooZoneLite-cc-template">
		<li>
			<?php if ( 'external' != $this->p_type ) { ?>
			<span class="WooZoneLite-cc_checkbox">
				<input type="radio" name="WooZoneLite-cc-choose[<?php echo $asin; ?>]" />
			</span>
			<?php } ?>
			<span class="WooZoneLite-cc_domain<?php echo $this->countryflags_aslink ? ' WooZoneLite-countryflag-aslink' : ''; ?>">
				<?php if ( $this->countryflags_aslink ) { ?>
				<a href="#" target="_blank"></a>
				<?php } ?>
			</span>
			<span class="WooZoneLite-cc_name"><a href="#" target="_blank"></a></span>
			-
			<span class="WooZoneLite-cc-status">
				<span class="WooZoneLite-cc-loader">
					<span class="WooZoneLite-cc-bounce1"></span>
					<span class="WooZoneLite-cc-bounce2"></span>
					<span class="WooZoneLite-cc-bounce3"></span>
				</span>
			</span>
		</li>
	</div>

<?php if ($with_wrapper) { ?>
</ul>
<?php } ?>

		<?php
			$contents = ob_get_clean();
			return $contents;
		}


		/**
		 * box: amazon shops checkout on cart page
		 */
		public function box_amazon_shops_checkout() {
			$shops = $this->woo_cart_get_amazon_prods_bycountry();
			if ( empty($shops) ) return false;
			
			$is_multiple = $this->woo_cart_is_amazon_multiple( $shops );
			if ( empty($is_multiple) || $is_multiple <= 1 ) return false;
			
			ob_start();
		?>

<div class="WooZoneLite-cart-checkout">
	<ul class="WooZoneLite-cart-shops">
	<?php
	foreach ($shops as $key => $value) {
		if ( empty($value) ) continue 1;

		//$country_name = array_shift(array_slice($array, 0, 1)); // get first element from array if a array "copy" is needed
		$domain = $value['domain'];
		$affID = $value['affID'];
		$country_name = $value['name'];

		$products = $value['products'];
		$nb_products = count($products);
		
		$prods_available = array();
		foreach ($products as $pkey => $pvalue)
			if ( $pvalue['available'] == 1 ) $prods_available[] = $pkey;
		$nb_available = count($prods_available);
	?>
		<li data-domain="<?php echo $domain; ?>">
			<span class="WooZoneLite-cc_domain <?php echo str_replace('.', '-', $domain); ?>"></span>
			<span class="WooZoneLite-cc_name"><?php echo $country_name; ?></span>
			<span class="WooZoneLite-cc_count"><?php echo sprintf( _n('(%s available from %s product)', '(%s available from %s products)', $nb_available, $nb_products, $this->localizationName),  $nb_available, $nb_products ); ?></span>
			<span class="WooZoneLite-cc_checkout">

				<form target="_blank" method="GET" action="//www.amazon.<?php echo $domain; ?>/gp/aws/cart/add.html">
					<input type="hidden" name="AssociateTag" value="<?php echo $affID; ?>"/>
					<?php /*<input type="hidden" name="SubscriptionId" value="<?php echo $this->amz_settings['AccessKeyID'];?>"/>*/ ?>
					<input type="hidden" name="AWSAccessKeyId" value="<?php echo $this->amz_settings['AccessKeyID'];?>"/>
					<?php 
					$cc = 1; 
					foreach ($products as $pkey => $pvalue){
					?>      
						<input type="hidden" name="ASIN.<?php echo $cc;?>" value="<?php echo $pvalue['asin'];?>"/>
						<input type="hidden" name="Quantity.<?php echo $cc;?>" value="<?php echo $pvalue['quantity'];?>"/>
					<?php
						$cc++;
					} // end foreach
					$redirect_in = isset($this->amz_settings['redirect_time']) && (int) $this->amz_settings['redirect_time'] > 0 ? ( (int) $this->amz_settings['redirect_time'] * 1000 ) : 1;
					?>
					<input type="submit" value="<?php _e('Proceed to Amazon Checkout', $this->localizationName); ?>" class="WooZoneLite-button">
					<input type="button" value="<?php _e('Cancel', $this->localizationName); ?>" class="WooZoneLite-button cancel">
				</form>

			</span>
			<span class="WooZoneLite-cc_status"></span>
		</li>
	<?php
	} // end foreach
	?>
	</ul>
	<div class="WooZoneLite-cart-msg"></div>
</div>

		<?php
			$contents = ob_get_clean();
			return $contents;
		}


		/**
		 * Cart related
		 */
		public function woocommerce_external_add_to_cart() { 
			echo '<script>jQuery(".single_add_to_cart_button").attr("target", "_blank");</script>'; 
		}

		public function woocommerce_external_checkout() {
			if( is_checkout() == true ){
				$this->redirect_cart();
			}
		}
		
		public function redirect_cart() {
			//global $woocommerce;

			$shops = $this->woo_cart_get_amazon_prods_bycountry();

			$is_multiple = $this->woo_cart_is_amazon_multiple( $shops );
			if ( empty($is_multiple) ) return true;

			// more than 1 amazon shops: product belonging to different amazon shops
			if ( $is_multiple > 1 ) {
				$this->woo_cart_update_meta_amazon_prods();
				$this->woo_cart_delete_amazon_prods();
				//echo '<script>setTimeout(function() { window.location.reload(true); }, 1);</script>'; 
				return true;
			}

			// single amazon shops: all products from cart will go to single amazon shop at checkout
			foreach ($shops as $key => $value) {
				if ( empty($value) ) continue 1;

				$domain = $value['domain'];
				$affID = $value['affID'];
				$country_name = $value['name'];
				$products = $value['products'];
				$nb_products = count($products);
			}
			//var_dump('<pre>', $domain, $affID, $country_name, $nb_products, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;    
			if ( ! $nb_products ) return true;

			$html = array();
			if ( isset($this->amz_settings["redirect_checkout_msg"]) && trim($this->amz_settings["redirect_checkout_msg"]) != "" ) {
				$html[] = '<img src="' . ( $this->the_plugin->cfg['paths']['freamwork_dir_url'] . 'images/checkout_loading.gif'  ) . '" style="margin: 10px auto;">';
				$html[] = "<h3>" . ( str_replace( '{amazon_website}', 'www.amazon.' . $domain, $this->amz_settings["redirect_checkout_msg"]) ) . "</h3>";
			}

			//$checkout_type =  isset($this->amz_settings['checkout_type']) && $this->amz_settings['checkout_type'] == '_blank' ? '_blank' : '_self';
			$checkout_type = '_self';

			ob_start();
			?>

			<form target="<?php echo $checkout_type;?>" id="amzRedirect" method="GET" action="//www.amazon.<?php echo $domain; ?>/gp/aws/cart/add.html">
				<input type="hidden" name="AssociateTag" value="<?php echo $affID;?>"/>
				<?php /*<input type="hidden" name="SubscriptionId" value="<?php echo $this->amz_settings['AccessKeyID'];?>"/>*/ ?>
				<input type="hidden" name="AWSAccessKeyId" value="<?php echo $this->amz_settings['AccessKeyID'];?>"/>
				<?php 
					$cc = 1; 
					foreach ($products as $key => $value){
				?>      
						<input type="hidden" name="ASIN.<?php echo $cc;?>" value="<?php echo $value['asin'];?>"/>
						<input type="hidden" name="Quantity.<?php echo $cc;?>" value="<?php echo $value['quantity'];?>"/>
				<?php
						$cc++;
					} // end foreach

   					$redirect_in = isset($this->amz_settings['redirect_time']) && (int) $this->amz_settings['redirect_time'] > 0 ? ( (int) $this->amz_settings['redirect_time'] * 1000 ) : 1;
				?>
			</form>

			<script type="text/javascript">
				setTimeout(function() {
					document.getElementById("amzRedirect").submit();
					<?php 
						//if( (int)$woocommerce->cart->cart_contents_count > 0 && $checkout_type == '_blank' ){
						if ( $nb_products && $checkout_type == '_blank' ) {
					?>
					setTimeout(function() { window.location.reload(true); }, 1);
					<?php
						}
					?>
				}, <?php echo $redirect_in;?>);
			</script>

			<?php 
			$html[] = ob_get_contents(); //ob_clean();
			echo implode(PHP_EOL, $html);

			$this->woo_cart_update_meta_amazon_prods();
			$this->woo_cart_delete_amazon_prods();
			exit();
			return true;
		}


		/**
		 * Ajax request
		 */
		public function ajax_requests()
		{
			$action = isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : 'none';
    
			$allowed_action = array( 'save_countries', 'save_product_country', 'load_cross_sell', 'cross_sell_empty_cache' );

			if( !in_array($action, $allowed_action) ){
				die(json_encode(array(
					'status'		=> 'invalid',
					'html'		=> 'Invalid action!'
				)));
			}

			if ( 'save_countries' == $action ) {
				$req = array(
					'product_id'			=> isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0,
					'product_country'	=> isset($_REQUEST['product_country']) ? trim( $_REQUEST['product_country'] ) : 0,
					'countries'				=> isset($_REQUEST['countries']) ? stripslashes(trim( $_REQUEST['countries'] )) : '',
				);
				extract($req);
				//var_dump('<pre>', $req, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
				
				$countries = json_decode( $countries, true );
				if ( $countries ) {
					foreach ($countries as $key => $val) {
						unset($countries["$key"]['name']);
					}
				}
				//var_dump('<pre>', $countries, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
				
				// save it
				if ( $product_id && $countries ) {
					$meta_value = array(
						'countries'							=> $countries,
						'countries_cache_time'		=> time(),
					);
					update_post_meta( $product_id, '_amzaff_frontend', $meta_value );
				}
				
				// get asin meta key
				$asin = get_post_meta($product_id, '_amzASIN', true);
				//var_dump('<pre>',$asin,'</pre>');
				
				// save product country
				$_SESSION['WooZoneLite']['product_country']["$asin"] = $product_country;

				die(json_encode(array(
					'status'		=> 'valid',
					'html'		=> 'ok'
				)));
			}

			if ( 'save_product_country' == $action ) {
				$req = array(
					'product_id'			=> isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0,
					'product_country'	=> isset($_REQUEST['product_country']) ? trim( $_REQUEST['product_country'] ) : 0,
				);
				extract($req);
				//var_dump('<pre>', $req, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
				
				// get asin meta key
				$asin = get_post_meta($product_id, '_amzASIN', true);
				//var_dump('<pre>',$asin,'</pre>');
				
				// save product country
				$_SESSION['WooZoneLite']['product_country']["$asin"] = $product_country;

				die(json_encode(array(
					'status'		=> 'valid',
					'html'		=> 'ok'
				)));
			}
			
			if ( 'load_cross_sell' == $action ) {
				$req = array(
					'asin'			=> isset($_REQUEST['asin']) ? (string) $_REQUEST['asin'] : 0,
				);
				extract($req);
				//var_dump('<pre>', $req, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

				$boxRsp = $this->_cross_sell_box( array('asin' => $asin) );

				die(json_encode(array(
					'status'		=> 'valid',
					'html'		=> $boxRsp['html'],
					'debug'		=> $boxRsp['debug'],
				)));
			}
			
			if ( 'cross_sell_empty_cache' == $action ) {
				$req = array(
					'asin'			=> isset($_REQUEST['asin']) ? (string) $_REQUEST['asin'] : 0,
				);
				extract($req);
				//var_dump('<pre>', $req, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

				$this->_cross_sell_empty_cache( array('asin' => $asin) );

				die(json_encode(array(
					'status'		=> 'valid',
				)));
			}

			die(json_encode(array(
				'status' 		=> 'invalid',
				'html'		=> 'Invalid action!'
			)));
		}


		/**
		 * checkout email
		 */
		public function woocommerce_before_checkout()
		{
			$return = '<div class="woozone_email_wrapper">';
			$return .= '<label for="woozone_checkout_user_email">E-mail:</label>';
			if( isset($this->amz_settings['checkout_email_mandatory']) && $this->amz_settings['checkout_email_mandatory'] == 'yes' ) {
				$return .= '<input type="hidden" id="woozone_checkout_email_required" name="woozone_checkout_email_required" value="1"/>';
			}
			$return .= '<input type="hidden" id="woozone_checkout_email_nonce" name="woozone_checkout_email_nonce" value="' . ( wp_create_nonce('woozone_checkout_email_nonce') ) . '"/>';
			$return .= '<input type="text" id="woozone_checkout_email" name="woozone_checkout_email" placeholder="email@example.com"/>';
			$return .= '</div>';

			echo $return;
		}
		
		public function woocommerce_ajax_before_user_checkout()
		{
			if( ! wp_verify_nonce( $_REQUEST['_nonce'], 'woozone_checkout_email_nonce')) die ('Busted!');
			unset($_REQUEST['_nonce']);
			
			$email = sanitize_email( $_REQUEST['email'] );
			$users_email = array();
			$users_email = get_option('WooZoneLite_clients_email');
			
			if( is_email($email) ) {
				if( in_array($email, $users_email) ) {
					echo 'email_exists';
					die;
				}
				$users_email[] = $email;
				update_option('WooZoneLite_clients_email', $users_email);
				echo 'success';
			}else{
				echo 'invalid_email';
			}
			
			die;
		}
	

		/**
		 * product country check
		 */
		// get product available amazon countries shops
		public function get_product_countries_available( $product ) {
			// get product id
			$product_id = $product;
			if ( is_object($product) ) {
				$prod_id = 0;
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
				$product_id = $prod_id;
			}
			if ( empty($product_id) ) return false;

			// amazon location & main affiliate ids
			$affIds = (array) ( isset($this->amz_settings['AffiliateID']) ? $this->amz_settings['AffiliateID'] : array() );
			if ( empty($affIds) ) return false;

			$main_aff_id = $this->the_plugin->main_aff_id();
			$main_aff_site = $this->the_plugin->main_aff_site();

			// countries
			$all_countries = $this->amzHelper->get_countries('country');
			$all_countries_affid = $this->amzHelper->get_countries('main_aff_id');

			// loop through setted affiliate ids from amazon config
			$available = array(); $cc = 0;
			foreach ($affIds as $key => $val) {
				if ( empty($val) ) continue 1;

				$convertCountry = $this->the_plugin->discount_convert_country2country();
				$domain = isset($convertCountry['amzwebsite']["$key"]) ? $convertCountry['amzwebsite']["$key"] : '';
				if ( empty($domain) ) continue 1;

				$available[$cc] = array(
					'domain'	=> $domain,
					'name'		=> isset($all_countries_affid["$key"]) ? $all_countries_affid["$key"] : 'missing country name',
				);
				$cc++;
			}
			if ( empty($available) ) return false;

			// verify affiliate ids based on product cached/saved available countries
			$meta_frontend = get_post_meta($product_id, '_amzaff_frontend', true);
			$cache_countries = isset($meta_frontend['countries']) && is_array($meta_frontend['countries']) ? $meta_frontend['countries'] : array();
			$cache_time = isset($meta_frontend['countries_cache_time']) ? $meta_frontend['countries_cache_time'] : 0;

			$cache_need_refresh = empty($cache_countries)
				|| !$cache_time
				|| ( ($cache_time + $this->the_plugin->ss['countries_cache_time']) < time() );

			// product amazon countries availability needs refresh (mandatory)
			if ( $cache_need_refresh ) return $available;

			// may need refresh if one country availability verification is missing!
			// verification for refresh is done in javascript/json based on 'available' key
			foreach ($available as $key => $val) {
				foreach ($cache_countries as $key2 => $val2) {
					// country founded
					if ( isset($val2['domain'], $val2['available']) && ($val['domain'] == $val2['domain']) ) {
						$available["$key"]['available'] = $val2['available'];
						break 1;
					}
				}
			}

			return $available;
		}

		// get product default country when added to cart (based on client country and main affiliate id)
		public function get_product_country_default( $product, $find_client_country=true ) {
			// get product id
			$product_id = $product;
			if ( is_object($product) ) {
				$prod_id = 0;
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
				$product_id = $prod_id;
			}
			if ( empty($product_id) ) return false;

			// client country
			$client_country = false;
			if ( $find_client_country ) {
				$client_country = $this->the_plugin->get_country_perip_external();
			}
			//var_dump('<pre>', $client_country, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			// return is of type:
			//array(3) {
  			//	["key"]			=> string(3) "com"
  			//	["website"]	=> string(4) ".com"
  			//	["affID"]		=> string(8) "jimmy-us"
			//}

			// product available countries
			$available_countries = $this->get_product_countries_available( $product_id );
			$found = false; $first = false; $first_available = false;
			//var_dump('<pre>', $product_id, $available_countries, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			if ( !empty($available_countries) ) {
				foreach ($available_countries as $key => $val) {

					if ( empty($first) )
						$first = $val['domain'];

					if ( isset($val['available']) ) {
						if ( empty($first) )
							$first = $val['domain'];
						if ( empty($first_available) && $val['available'] )
							$first_available = $val['domain'];
					}
  
					if ( ! empty($client_country) && isset($client_country['website'])
						&& substr($client_country['website'], 1) == $val['domain'] ) {
						$found = $val['domain'];
					}
				}
			}
			//var_dump('<pre>',$found, $first, $first_available,'</pre>');  

			// default country based on: first from all valid countries, first available country or found client country
			$the_country = false;
			if ( !empty($first) ) 
				$the_country = $first;
			if ( !empty($first_available) ) 
				$the_country = $first_available;
			if ( !empty($found) ) 
				$the_country = $found;

			$country = $this->the_plugin->domain2amzForUser( $the_country );
			if ( !empty($available_countries) ) {
				foreach ($available_countries as $key => $val) {
					if ( substr($country['website'], 1) == $val['domain'] ) {
						$country = array_merge($country, array(
							'name'			=> $val['name'],
							'available'		=> isset($val['available']) ? $val['available'] : -1,
						));
					}
				}
			}
			return $country;
		}

		// get product current country when added to cart (based on default country and if client choose a country by himself)
		public function get_product_country_current( $product, $find_client_country=true ) {
			// get product id
			$product_id = $product;
			if ( is_object($product) ) {
				$prod_id = 0;
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
				$product_id = $prod_id;
			}
			if ( empty($product_id) ) return false;
   
			$the_country = $this->get_product_country_default( $product_id, $find_client_country );
			$country = $the_country;
			
			// get asin meta key
			$asin = get_post_meta($product_id, '_amzASIN', true);
			//var_dump('<pre>',$asin,'</pre>');

			//unset($_SESSION['WooZoneLite']);
			//var_dump('<pre>', $the_country, $_SESSION, '</pre>');

			if ( !empty($asin)
				 && isset(
					$_SESSION['WooZoneLite'],
					$_SESSION['WooZoneLite']['product_country'],
					$_SESSION['WooZoneLite']['product_country']["$asin"]
				 )
				 && !empty($_SESSION['WooZoneLite']['product_country']["$asin"])
			) {
				$sess_country = $_SESSION['WooZoneLite']['product_country']["$asin"];

				// product available countries
				$available_countries = $this->get_product_countries_available( $product_id );

				if ( !empty($available_countries) ) {
					foreach ($available_countries as $key => $val) {

						if ( $sess_country == $val['domain'] ) {
							$the_country = $sess_country;
							$country = $this->the_plugin->domain2amzForUser( $the_country );
							$country = array_merge($country, array(
								'name'			=> $val['name'],
								'available'		=> isset($val['available']) ? $val['available'] : -1,
							));
						}
					}
				}
			}

			return $country;
		}
		
		// get amazon products from cart
		public function woo_cart_get_amazon_prods() {
			//global $woocommerce;

			$amz_products = array();

			$cart_items_nb = (int) WC()->cart->get_cart_contents_count();
			if ( ! $cart_items_nb ) return false;

			$cart_items = WC()->cart->get_cart();
			//var_dump('<pre>', $cart_items, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;    
			foreach ($cart_items as $key => $value) {

				$prod_id = isset($value['variation_id']) && (int)$value['variation_id'] > 0 ? $value['variation_id'] : $value['product_id'];
				$amzASIN = $prod_id ? get_post_meta( $prod_id, '_amzASIN', true ) : '';
				
				$parent_id = isset($value['variation_id']) && (int)$value['variation_id'] > 0 ? $value['product_id'] : 0;
				$parent_amzASIN = $parent_id ? get_post_meta( $parent_id, '_amzASIN', true ) : '';
				
				//if ( empty($amzASIN) || strlen($amzASIN) != 10 )
				if ( empty($amzASIN) ) continue 1;

				$amz_products["$key"] = array(
					'cart_item_key'				=> $key,
					'product_id'					=> $prod_id,
					'asin'								=> $amzASIN,
					'parent_id'						=> $parent_id,
					'parent_asin' 				=> $parent_amzASIN,
					'quantity'						=> $value['quantity'],
				);
			} // end foreach
    
			return $amz_products;
		}
		
		// get amazon products from cart by country availability
		public function woo_cart_get_amazon_prods_bycountry() {
			$prods = $this->woo_cart_get_amazon_prods();
			//var_dump('<pre>', $prods, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;    
			if ( empty($prods) ) return false;

			foreach ($prods as $key => $value) {
				$prod_id = $value['parent_id'] ? $value['parent_id'] : $value['product_id'];
				$product_country = $this->get_product_country_current( $prod_id );

				$prods["$key"] = array_merge($prods["$key"], $product_country);
			} // end foreach
			//var_dump('<pre>', $prods, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$bycountry = array();
			foreach ($prods as $key => $value) {
				$domain = substr($value['website'], 1);

				if ( ! isset($bycountry["$domain"]) ) {
					$bycountry["$domain"] = array(
						'domain'			=> $domain,
						'affID'				=> $value['affID'],
						'name'				=> $value['name'],
						'products'			=> array(),
					);
				}
				$bycountry["$domain"]["products"]["$key"] = $value;
			} // end foreach
			//var_dump('<pre>', $bycountry, '</pre>');    

			return $bycountry;
		}

		// woocommerce cart contains multiple amazon shops
		public function woo_cart_is_amazon_multiple( $shops=array() ) {
			if ( empty($shops) )
				$shops = $this->woo_cart_get_amazon_prods_bycountry();
			if ( empty($shops) ) return false;

			$domains = array();
			foreach ($shops as $key => $value) {
				if ( empty($value) ) continue 1;
				
				$domain = $value['domain'];
				if ( ! in_array($domain, $domains) )
					$domains[] = $domain;
			}
			return count($domains);
		}
		
		// update meta (redirect to amazon) for amazon products from cart
		public function woo_cart_update_meta_amazon_prods() {
			$prods = $this->woo_cart_get_amazon_prods();
			//var_dump('<pre>', $prods, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;    
			if ( empty($prods) ) return false;
   
			foreach ($prods as $key => $value) {
				if ( ! isset($value['asin']) || trim($value['asin']) == '' ) continue 1;

				$post_id = $this->the_plugin->get_post_id_by_meta_key_and_value('_amzASIN', $value['asin']);

				$redirect_to_amz = (int) get_post_meta($post_id, '_amzaff_redirect_to_amazon', true);
				update_post_meta($post_id, '_amzaff_redirect_to_amazon', (int)($redirect_to_amz+1));

				$redirect_to_amz2 = (int) get_post_meta($post_id, '_amzaff_redirect_to_amazon_prev', true);
				update_post_meta($post_id, '_amzaff_redirect_to_amazon_prev', (int)($redirect_to_amz2+1));
			} // end foreach
		}
		
		// delete amazon products from cart
		public function woo_cart_delete_amazon_prods() {
			$prods = $this->woo_cart_get_amazon_prods();
			//var_dump('<pre>', $prods, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;    
			if ( empty($prods) ) return false;

			foreach ($prods as $key => $value) {
				if ( ! isset($value['asin']) || trim($value['asin']) == '' ) continue 1;

				//var_dump('<pre>', $key, $value,'</pre>');

				// Remove it from the cart
				//WC()->cart->set_quantity( $value['key'], 0 );
                WC()->cart->remove_cart_item($key);

				//$cart_item = WC()->cart->get_cart_item( $value['key'] );
				//var_dump('<pre>','after delete:', $cart_item,'</pre>');
			} // end foreach

			$cart_items_nb = (int) WC()->cart->get_cart_contents_count();
			//var_dump('<pre>', $cart_items_nb, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		}


		/**
		 * Cross Sell - Similarity Products
		 */
		public function cross_sell_checkout()
		{
			$amz_cross_sell = isset($_GET['amz_cross_sell']) ? (string) $_GET['amz_cross_sell'] : false;
			if ( false === $amz_cross_sell ) return '';
			
			$asins = isset($_GET['asins']) ? $_GET['asins'] : '';
			$asins = trim($asins);
			if ( '' == $asins ) return '';
			
			$asins = explode(',', $asins);
			if ( empty($asins) ) return '';

			// I: use amazon api to add products to cart
			if (0) {

				//$GLOBALS['WooZoneLite'] = $this;
				
				if ( $this->the_plugin->is_aateam_demo_keys() ) {
					return '';
				}

				$selectedItems = array();
				foreach ($asins as $key => $value){
					$selectedItems[] = array(
						'offerId' => $value,
						'quantity' => 1
					);
				}
   
				$provider = 'amazon';
				$rsp = $this->the_plugin->get_ws_object( $provider )->api_make_request(array(
					'amz_settings'          => $this->amz_settings,
					'from_file'             => str_replace($this->the_plugin->cfg['paths']['plugin_dir_path'], '', __FILE__),
					'from_func'             => __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
					'requestData'           => array(
						'selectedItems'         => $selectedItems,
					),
					//'optionalParameters'  => array(),
					'responseGroup'         => 'Cart',
					'method'                => 'cartThem',
				));
				$cart = $rsp['response'];
      
				// debug only
				//$this->amzHelper->aaAmazonWS->cartKill();
				//$cart = $this->amzHelper->aaAmazonWS->responseGroup('Cart')->cartThem($selectedItems);
				//unset($_SESSION['amzCart']);

				$user_country = $this->the_plugin->get_country_perip_external();
				$config = $this->amz_settings;
				// AssociateTag => $user_country['affID']
				// SubscriptionId => $config['AccessKeyID']
    
				$cart_items = isset($cart['CartItems']['CartItem']) ? $cart['CartItems']['CartItem'] : array();
				//var_dump('<pre>', $cart['PurchaseURL'], $cart_items, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;    
				if( count($cart_items) ){
					header('Location: ' . $cart['PurchaseURL'] . "%26tag=" . $user_country['affID']); // & = %26 => link must be encoded
					exit();
				}

			} // end I

			// II: create a fake form and submit it with javascript
			if (1) {

			$user_country = $this->the_plugin->get_country_perip_external();
			$main_aff_id = $this->the_plugin->main_aff_id();
			$main_aff_site = $this->the_plugin->main_aff_site();

			$products = array();
			foreach ($asins as $key => $value){
				$products[] = array(
					'asin' => $value,
					'quantity' => 1
				);
			}
			
			if ( empty($products) ) return true;

			$domain = substr($user_country['website'], 1); //$this->amz_settings['country']; //substr($user_country['website'], 1);
			$affID = $user_country['affID'];

			$html = array();
			if ( isset($this->amz_settings["redirect_checkout_msg"]) && trim($this->amz_settings["redirect_checkout_msg"]) != "" ) {
				$html[] = '<img src="' . ( $this->the_plugin->cfg['paths']['freamwork_dir_url'] . 'images/checkout_loading.gif'  ) . '" style="margin: 10px auto;">';
				$html[] = "<h3>" . ( str_replace( '{amazon_website}', 'www.amazon.' . $domain, $this->amz_settings["redirect_checkout_msg"]) ) . "</h3>";
			}
    	
			//$checkout_type =  isset($this->amz_settings['checkout_type']) && $this->amz_settings['checkout_type'] == '_blank' ? '_blank' : '_self';
			$checkout_type = '_self';
			
			ob_start();
			?>

			<form target="<?php echo $checkout_type;?>" id="amzRedirect" method="GET" action="//www.amazon.<?php echo $domain; ?>/gp/aws/cart/add.html">
				<input type="hidden" name="AssociateTag" value="<?php echo $affID;?>"/>
				<?php /*<input type="hidden" name="SubscriptionId" value="<?php echo $this->amz_settings['AccessKeyID'];?>"/>*/ ?>
				<input type="hidden" name="AWSAccessKeyId" value="<?php echo $this->amz_settings['AccessKeyID'];?>"/>
				<?php 
					$cc = 1; 
					foreach ($products as $key => $value){
				?>      
						<input type="hidden" name="ASIN.<?php echo $cc;?>" value="<?php echo $value['asin'];?>"/>
						<input type="hidden" name="Quantity.<?php echo $cc;?>" value="<?php echo $value['quantity'];?>"/>
				<?php
						$cc++;
					} // end foreach

   					//$redirect_in = isset($this->amz_settings['redirect_time']) && (int) $this->amz_settings['redirect_time'] > 0 ? ( (int) $this->amz_settings['redirect_time'] * 1000 ) : 1;
   					$redirect_in = 1;
				?>
			</form>

			<script type="text/javascript">
				setTimeout(function() {
					document.getElementById("amzRedirect").submit();
				}, <?php echo $redirect_in;?>);
			</script>

			<?php 
			$html[] = ob_get_contents(); //ob_clean();
			echo implode(PHP_EOL, $html);
			exit;
			
			} // end II
		}

		public function cross_sell_box( $atts ) {
			extract( shortcode_atts( array(
				'asin' => ''
			), $atts ) );

			$cross_selling = (isset($this->amz_settings["cross_selling"]) && $this->amz_settings["cross_selling"] == 'yes' ? true : false);
			
			if( $cross_selling == false ) return '';

			$backHtml = array();
			$backHtml[] = '<div class="main-cross-sell" data-asin="' . $asin . '">';

			ob_start();
		?>

	<div class="WooZoneLite-cross-sell-loader">
		<div>
			<div id="floatingBarsG">
				<div class="blockG" id="rotateG_01"></div>
				<div class="blockG" id="rotateG_02"></div>
				<div class="blockG" id="rotateG_03"></div>
				<div class="blockG" id="rotateG_04"></div>
				<div class="blockG" id="rotateG_05"></div>
				<div class="blockG" id="rotateG_06"></div>
				<div class="blockG" id="rotateG_07"></div>
				<div class="blockG" id="rotateG_08"></div>
			</div>
			<div class="WooZoneLite-cross-sell-loader-text"></div>
		</div>
	</div>

		<?php
			$backHtml[] = ob_get_clean();

			$backHtml[] = '</div>';
			if ( $this->the_plugin->is_debug_mode_allowed() ) {
				$backHtml[] = '<div class="WooZoneLite-cross-sell-debug" data-asin="' . $asin . '"></div>';
			}
			$backHtml[] = '<div style="clear:both;"></div>';
			
			$html = implode(PHP_EOL, $backHtml);
			return $html;
		}

		public function _cross_sell_box( $atts=array() ) {
			extract($atts);

			global $product;
			
			$ret = array('status' => 'valid', 'html' => '', 'nbprods' => 0, 'debug' => '');

			// get product related items from Amazon
			$products = $this->_cross_sell_get_similarity_prods( $asin, 10 );
			
			$ret['debug'] = $this->_cross_sell_debug_msg( $products );
			$ret['nbprods'] = count($products['rows']);

			$backHtml = array();
			if ( isset($products['status'], $products['rows']) && 'valid' == $products['status'] && !empty($products['rows']) ) {
				
				$choose_variation = isset($this->amz_settings['cross_selling_choose_variation']) ? (string) $this->amz_settings['cross_selling_choose_variation'] : 'first';

				$how_many = isset($this->amz_settings['cross_selling_nbproducts']) ? (int) $this->amz_settings['cross_selling_nbproducts'] : 3;
				$how_many = $how_many + 1; // add 1 fake in products, current product

				// :: open box wrapper
				$backHtml[] = "<link rel='stylesheet' id='amz-cross-sell' href='" . ( $this->the_plugin->cfg['paths']['frontend_dir_url'] ) . "/css/cross-sell.css' type='text/css' media='all' />";

				$backHtml[] = '<div class="cross-sell">';
				$backHtml[] = '<span class="cross-sell-price-sep" data-price_dec_sep="' . wc_get_price_decimal_separator() . '" style="display: none;"></span>';
				$backHtml[] =   '<h2>' . ( __('Frequently Bought Together', $this->localizationName ) ) . '</h2>';
				$backHtml[] =   '<div style="margin-top: 0px;" class="separator"></div>';

				// :: box first row - with thumbs
				$backHtml[] =   '<ul id="feq-products">';
				$cc = 0;
				$_total_price = 0;
				foreach ($products['rows'] as $key => $value) {
					
					if ( $cc >= $how_many ) break;

					// is variable product? => get chosen variation based on option
					if ( isset($value['is_variable']) && 'Y' == $value['is_variable'] ) {

						$variation_found = array();

						// if verification
						if ( isset($value['variations'], $value['variations_filtered'])
							&& is_array($value['variations']) && ! empty($value['variations'])
							&& is_array($value['variations_filtered']) ) {

							// just in case: choose first valid variation
							$variation_found = array_values($value['variations']);
							$variation_found = isset($variation_found[0]) ? $variation_found[0] : array();

							// choose variation from option value (allowed: first, lowest price, highest price)
							foreach ( $value['variations_filtered'] as $varType => $varAsin) {
								if ( ! empty($varAsin) && isset($value['variations']["$varAsin"]) ) {
									$variation_found = $value['variations']["$varAsin"];
									if ( $choose_variation == $varType ) { // the chosen one!
										break;
									}
								}
							}
						} // end if verification

						// couldn't find a valid variation for this product
						if ( empty($variation_found) ) {
							unset($products['rows']["$key"]); // delete this invalid product!
							continue 1; // we intentionaly don't increment the counter, so we can go and verify next products!
						}

						// replace old main variable product details with its variation child details
						$value = $variation_found;
						$products['rows']["$key"] = $variation_found;
					}

					$value['price'] = str_replace(",", ".", $value['price']);
					
					$product_buy_url = $this->the_plugin->_product_buy_url( '', $value['ASIN'] );
					$prod_link = home_url('/?redirectAmzASIN=' . $value['ASIN'] );
					$prod_link = $product_buy_url;
					
					if( trim($value['thumb']) != "" ){
						$backHtml[] =   '<li>';
						$backHtml[] =   '<a target="_blank" rel="nofollow" href="' . ( $prod_link ) . '">';
						$backHtml[] =       '<img class="cross-sell-thumb" id="cross-sell-thumb-' . ( $value['ASIN'] ) . '" src="' . ( $value['thumb'] ) . '" alt="' . ( htmlentities( str_replace('"', "'", $value['Title']) ) ) . '">';
						$backHtml[] =   '</a>';
						if( $cc < (count($products['rows']) - 1) ){
							$backHtml[] =       '<div class="plus-sign">+</div>';
						}

						$backHtml[] =   '</li>';
						
						$_total_price = $_total_price + $value['price'];
					}

					
					$cc++;
				}
				$backHtml[] =   '</ul>';

				// :: box second row - with titles & prices
				$backHtml[] =   '<div class="cross-sell-buy-btn">';
				$backHtml[] =   	'<span id="cross-sell-bpt">Price for all:</span>';
				$backHtml[] =   	'<span id="cross-sell-buying-price" class="price">' . ( wc_price( $_total_price ) ) . '</span>';
				$backHtml[] =       '<div style="clear:both"></div><a href="' . home_url(). '" id="cross-sell-add-to-cart"><img src="' . ( $this->the_plugin->cfg['paths']['freamwork_dir_url'] . 'images/btn_add-to-cart.png'  ) . '"/></a>';
				$backHtml[] =   '</div>';

				$backHtml[] = '<div class="cross-sell-buy-selectable">';
				$backHtml[] =   '<ul class="cross-sell-items">';
				$cc = 0;
				foreach ($products['rows'] as $key => $value) {
					
					if ( $cc >= $how_many ) break;

					if ( $cc == 0 && ( $asin == $value['ASIN'] || $asin == $value['ParentASIN'] ) ) {
						$backHtml[] =       '<li>';
						$backHtml[] =           '<input type="checkbox" checked="checked" value="' . ( $value['ASIN'] ) . '">';
						$backHtml[] =           '<div class="cross-sell-product-title"><strong>' . __('This item:', $this->localizationName) . ' </strong>' . $value['Title'] . '</div>';
						$backHtml[] =           '<div class="cross-sell-item-price" data-item_price="' . $value['price'] . '">' . ( wc_price( $value['price'] ) ) . '</div>';
						$backHtml[] =       '</li>';
					}
					else{
						$product_buy_url = $this->the_plugin->_product_buy_url( '', $value['ASIN'] );
						$prod_link = home_url('/?redirectAmzASIN=' . $value['ASIN'] );
						$prod_link = $product_buy_url;

						$backHtml[] =       '<li>';
						$backHtml[] =           '<input type="checkbox" checked="checked" value="' . ( $value['ASIN'] ) . '">';
						$backHtml[] =           '<div class="cross-sell-product-title">' . ( '<a target="_blank" rel="nofollow" href="' . ( $prod_link ) . '">' . $value['Title'] .'</a>' ) . '</div>';
						$backHtml[] =           '<div class="cross-sell-item-price" data-item_price="' . $value['price'] . '">' . ( wc_price( $value['price'] ) ) . '</div>';
						$backHtml[] =       '</li>';
					}

					$cc++;
				}
				$backHtml[] =   '</ul>';
				$backHtml[] = '</div>';

				// :: close box wrapper
				$backHtml[] = '</div>';

				$backHtml[] = '<div style="clear:both;"></div>';

				//$backHtml[] = "<script type='text/javascript' src='" . ( $this->the_plugin->cfg['paths']['frontend_dir_url'] ) . "/js/cross-sell.js'></script>";
				
				if ( isset($_total_price) && ($_total_price > 0) ) {
					return array_merge($ret, array(
						'html'		=> implode(PHP_EOL, $backHtml), 
					));
				}
				return $ret;
			}
			return $ret;
		}

		public function _cross_sell_get_similarity_prods( $asin, $return_nr=3, $force_update=false ) {
			$max_tries = 5;
			$cache_valid_for = (60 * 60 * 24); // 24 hours in seconds

			$return_nr = $return_nr + 1; // add 1 fake in products, current product

			$ret = array('status' => 'invalid', 'rows' => array(), 'msg' => '', 'msg_extra' => array());
			$retProd = array();
			$msg_extra = array();
			$nb_tries = 'inc';

			// check for cache of this ASIN
			$db = $this->the_plugin->db;
			$cache_request = $db->get_row( $db->prepare( "SELECT * FROM " . ( $db->prefix ) . "amz_cross_sell WHERE ASIN = %s", $asin), ARRAY_A );

			// if cache found for this product & NOT force update
			if ( $cache_request != "" && count($cache_request) > 0 && $force_update === false ) {

				// get products from DB cache amz_cross_sell table
				$products = maybe_unserialize($cache_request['products']);

				$msg_extra = array(
					'asin'					=> $cache_request['ASIN'],
					'nr_products'		=> $cache_request['nr_products'],
					'is_variable'		=> $cache_request['is_variable'],
					'nb_tries'			=> $cache_request['nb_tries'],
				);

				// is valid cache?
				if ( isset($cache_request['add_date']) ) {
					$add_date = strtotime($cache_request['add_date']);
					//$add_date = gmdate("U", $add_date);
				}
    			$cache_isvalid = 
    				isset($cache_request['add_date'])
					&& ( ($add_date + $cache_valid_for) > time() )
					? true : false;

				// if cache timeout (not valid anymore) => reset nb tries
				if ( ! $cache_isvalid ) {
					$nb_tries = 0;
				}
				else {
					$msg_extra['cache_expires_in'] = $this->the_plugin->u->time_since(
						time(),
						($add_date + $cache_valid_for)
					);
					unset($msg_extra['cache_expires_in']);
				}

				// make cache invalid, because no products found saved in cache & still allowed to make tries
				if ( empty($products) && isset($cache_request['nb_tries']) && ( $cache_request['nb_tries'] < $max_tries ) ) {
					$cache_isvalid = false;
				}

				// if cache still valid, get from mysql cache & NOT force update
				if ( $cache_isvalid ) {
					$msg_extra['from_cache'] = true;
					return array('status' => 'valid', 'rows' => array_slice( $products, 0, $return_nr ), 'msg' => 'products returned from cache.', 'msg_extra' => $msg_extra);
				}
			}

			if ( $this->the_plugin->is_aateam_demo_keys() ) {
				return array_merge( $ret, array('status' => 'invalid', 'rows' => array(), 'msg' => 'you\'re not allowed to use aateam demo keys on cross sell.') );
			}

			// load the amazon webservices client class
			$aaAmazonWS = $this->amzHelper->aaAmazonWS;
			$provider = 'amazon';


			// get current product
			$rsp = $this->the_plugin->get_ws_object( $provider )->api_make_request(array(
				'amz_settings'          => $this->amz_settings,
				'from_file'             => str_replace($this->the_plugin->cfg['paths']['plugin_dir_path'], '', __FILE__),
				'from_func'             => __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
				'requestData'           => array(
					'asin'                  => $asin,
				),
				'optionalParameters'    => array(),
				'responseGroup'         => 'Large,ItemAttributes,OfferFull,Variations,VariationSummary',
				'method'                => 'lookup',
			));
			//var_dump('<pre>', $rsp, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			$thisProd = $rsp['response'];
			$thisProd_respStatus = $this->the_plugin->get_ws_object( $provider )->is_amazon_valid_response( $thisProd );
			
			// loop current product
			if ( $thisProd_respStatus['status'] == 'valid' ) { // success
				$thisProd = $thisProd['Items']['Item'];
				$prodasin = $thisProd['ASIN'];
				$foundProd = $this->_cross_sell_get_prod_fields( $thisProd );
				if ( ! empty($foundProd) ) {
					$retProd[$prodasin] = $foundProd;
				}
			}
			//var_dump('<pre>', $retProd, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL; 
			
			// get SIMILARITY products
			$rsp = $this->the_plugin->get_ws_object( $provider )->api_make_request(array(
				'amz_settings'          => $this->amz_settings,
				'from_file'             => str_replace($this->the_plugin->cfg['paths']['plugin_dir_path'], '', __FILE__),
				'from_func'             => __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
				'requestData'           => array(
					'asin'                  => $asin,
				),
				'optionalParameters'    => array(),
				'responseGroup'         => 'Large,ItemAttributes,OfferFull,Variations,VariationSummary',
				'method'                => 'similarityLookup',
			));
			//var_dump('<pre>', $rsp, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			$similarity = $rsp['response'];
			$similarity_respStatus = $this->the_plugin->get_ws_object( $provider )->is_amazon_valid_response( $similarity );

			// loop SIMILARITY products
			if ( $similarity_respStatus['status'] == 'valid' ) { // success
				foreach ($similarity['Items']['Item'] as $key => $value){
					if (
						count($similarity['Items']['Item']) > 0
						&& count($value) > 0
						&& isset($value['ASIN'])
						&& strlen($value['ASIN']) >= 10
					) {
						$thisProd = $value;
						$prodasin = $thisProd['ASIN'];
						$foundProd = $this->_cross_sell_get_prod_fields( $thisProd );
						if ( ! empty($foundProd) ) {
							$retProd[$prodasin] = $foundProd;
						}
					}
				}
			}
			//var_dump('<pre>', $retProd, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL; 

			// invalid response
			if ( empty($retProd) ) {
				$msg = array();
				if ( isset($thisProd['status'], $thisProd['msg']) && 'invalid' == $thisProd['status'] ) {
					$msg[] = $thisProd['msg'];
				}
				if ( isset($similarity['status'], $similarity['msg']) && 'invalid' == $similarity['status'] ) {
					$msg[] = $similarity['msg'];
				}
				$msg = implode('<br />', $msg);
				return array_merge( $ret, array('status' => 'invalid', 'rows' => array(), 'msg' => $msg) );
			}

			// SIMILARITY products response is invalid
			if ( $similarity_respStatus['status'] != 'valid' ) {
				// if "There are no similar items for this product" we need to save in cache
				if ( isset($similarity_respStatus['amz_code']) && 'AWS.ECommerceService.NoSimilarities' == $similarity_respStatus['amz_code'] ) {
					$retProd = array();
					$ret['msg'] = $similarity_respStatus['amz_code'];
					$noSimilarities = true;
				}
				else {
					$msg = array();
					$msg[] = $similarity_respStatus['msg'];
					return array_merge( $ret, array('status' => 'invalid', 'rows' => array(), 'msg' => implode('<br />', $msg)) );
				}
			}

			// if cache found for this product
			$savedb = array(
				'products'				=> serialize($retProd), //serialize(array_slice( $retProd, 0, $return_nr)),
				'nr_products'			=> count($retProd), //$return_nr <= count($retProd) ? $return_nr : count($retProd),
				'is_variable'			=> isset($retProd["$asin"], $retProd["$asin"]['is_variable']) ? (string) $retProd["$asin"]['is_variable'] : 'N',
			);

			if ( $cache_request != "" && count($cache_request) > 0 ) {

				$nb_tries = isset($noSimilarities) && $noSimilarities ? $max_tries : $nb_tries;
				$calcTries = $this->_cross_sell_calc_tries($nb_tries, $cache_request['nb_tries'], $force_update);

				$updateQuery = "update " . $db->prefix . "amz_cross_sell" . " set products = %s, nr_products = %s, is_variable = %s" . $calcTries['query'] . "where 1=1 and ASIN = %s;";
				$updateQuery = $db->prepare( $updateQuery, $savedb['products'], $savedb['nr_products'], $savedb['is_variable'], $asin );
				$db->query( $updateQuery );
				/*
				$db->update(
					$db->prefix . "amz_cross_sell",
					array(
						'products'			=> $savedb['products'],
						'nr_products'		=> $savedb['nr_products'],
						'is_variable'		=> $savedb['is_variable'],
						'nb_tries'			=> 'nb_tries + 1',
					),
					array( 'ASIN' => $asin ),
					array(
						'%s',
						'%d',
						'%s',
						'%d'
					),
					array(
						'%s'
					)
				);
				*/
			}
			// if cache not found for this product
			else {
				$nb_tries = isset($noSimilarities) && $noSimilarities ? $max_tries : 1;
				$calcTries = $this->_cross_sell_calc_tries($nb_tries, 0, $force_update);

				/*$db->insert(
					$db->prefix . "amz_cross_sell",
					array(
						'ASIN'				=> $asin,
						'products'			=> $savedb['products'],
						'nr_products'		=> $savedb['nr_products'],
						'is_variable'		=> $savedb['is_variable'],
						'nb_tries'			=> 1,
					),
					array(
						'%s',
						'%s',
						'%d',
						'%s',
						'%d'
					)
				);*/
				$this->the_plugin->db_custom_insert(
					$db->prefix . "amz_cross_sell",
					array(
						'values' => array(
							'ASIN'				=> $asin,
							'products'			=> $savedb['products'],
							'nr_products'		=> $savedb['nr_products'],
							'is_variable'		=> $savedb['is_variable'],
							'nb_tries'			=> $nb_tries,
						),
						'format' => array(
							'%s',
							'%s',
							'%d',
							'%s',
							'%d'
						)
					),
					true
				);
			}
			
			$msg_extra = array(
				'asin'					=> $asin,
				'nr_products'		=> $savedb['nr_products'],
				'is_variable'		=> $savedb['is_variable'],
				'nb_tries'			=> $calcTries['nb'],
			);
			if ( $force_update ) {
				$msg_extra['force_update'] = 'yes';
			}

			if ( ! empty($ret['msg']) ) {
				$ret['msg'] .= ' - ';
			}
			if ( ! empty($retProd) ) {
				$ret['msg'] .= 'products successfully returned from amazon request.';
			}
			else {
				$ret['msg'] .= 'no products returned from amazon request.';
			}
			return array_merge( $ret, array('status' => 'valid', 'rows' => array_slice( $retProd, 0, $return_nr ), 'msg_extra' => $msg_extra) );
		}

		public function _cross_sell_get_prod_fields( $thisProd, $pms=array() ) {
			$pms = array_replace_recursive(array(
				'max_variations'			=> -1, // -1 = unlimited; maximum variations to retrieve
				'is_variation_child'			=> false, // current product data is for a variation child
			), $pms);
			extract( $pms );

			$retProd = array();

			// :: main properties
			$retProd['ASIN'] = isset($thisProd['ASIN']) ? $thisProd['ASIN'] : '';
			$retProd['ParentASIN'] = isset($thisProd['ParentASIN']) ? $thisProd['ParentASIN'] : '';
			
			// :: product title
			$retProd['Title'] = isset($thisProd['ItemAttributes']['Title']) ? stripslashes($thisProd['ItemAttributes']['Title']) : '';
			
			// :: variations
			if ( ! $is_variation_child ) {
				
				$retProd['DetailPageURL'] = isset($thisProd['DetailPageURL']) ? $thisProd['DetailPageURL'] : '';

				$retProd['is_variable'] = 'N';
				$variations = isset($thisProd['Variations'], $thisProd['Variations']['Item'])
					? $thisProd['Variations']['Item'] : array();
	
	            if ( ! empty($variations) ) {

	            	if ( isset($variations['ASIN']) ) {
						$variations = array( $variations );
	            	}

					$retProd['is_variable'] = 'Y';
					$retProd['variations'] = array();
					$retProd['variations_total'] = count($variations);
					$retProd['variations_filtered'] = array(
						'first'					=> '',
						'lowest_price'	=> '',
						'highest_price'	=> '',
					);
	
					$currentPrice = array('lowest_price' => null, 'highest_price' => null);
					foreach ($variations as $idx => $variation_item) {
						$variation_asin = isset($variation_item['ASIN']) ? $variation_item['ASIN'] : '';
						$variation_details = $this->_cross_sell_get_prod_fields( $variation_item, array('is_variation_child' => true) );

						if ( ! empty($variation_details) ) {
							$retProd['variations']["$variation_asin"] = $variation_details;
							
							//first variation
							if ( empty($retProd['variations_filtered']['first']) ) {
								$retProd['variations_filtered']['first'] = $variation_asin;
							}
							
							// compare prices so we can choose lowest price & highest price variation
							if ( is_null($currentPrice['lowest_price']) || ( $currentPrice['lowest_price'] > (float) $variation_details['price'] ) ) {
								$currentPrice['lowest_price'] = (float) $variation_details['price'];
								$retProd['variations_filtered']['lowest_price'] = $variation_asin;
							}
							if ( is_null($currentPrice['highest_price']) || ( $currentPrice['highest_price'] < (float) $variation_details['price'] ) ) {
								$currentPrice['highest_price'] = (float) $variation_details['price'];
								$retProd['variations_filtered']['highest_price'] = $variation_asin;
							}
						}
					} // end foreach variations
					
					// keep only necessary variations (optimization)
					$varKeep = array();
					foreach ($retProd['variations_filtered'] as $varAsin) {
						if ( ! empty($varAsin) ) {
							$varKeep["$varAsin"] = $retProd['variations']["$varAsin"];
						}
					}
					$retProd['variations'] = $varKeep;
	            }
            }

			// :: product large image
			$retProd['thumb'] = isset($thisProd['SmallImage'], $thisProd['SmallImage']['URL'])
				? $thisProd['SmallImage']['URL'] : '';
			if ( empty($retProd['thumb']) ) {
				// Images
				$images = $this->amzHelper->build_images_data( $thisProd );
				if ( empty($images['small']) ) {
					// no images found - if has variations, try to find first image from variations
					$images = $this->amzHelper->get_first_variation_image( $thisProd );
				}
				if ( isset($images['small']) && !empty($images['small']) ) {
					$retProd['thumb'] = $images['small'][0];
				}
			}

			// :: product price
			$prodprice = $this->amzHelper->get_productPrice( $thisProd );
			$retProd['price'] = $prodprice['_price'];
			$isValid_price = false;
			if ( trim($retProd['price']) != '' && (float) $retProd['price'] > '0.00' ) {
				//$retProd['price'] = number_format($retProd['price'], 2);
				$isValid_price = true;
			}

			// :: validation
			$isValid = true;
			// remove if don't have valid price
			if ( ! $isValid_price ) {
				$isValid = false;
			}
			else if ( isset($retProd['is_variable']) && 'Y' == $retProd['is_variable'] && empty( $retProd['variations'] ) ) {
				$isValid = false;
			}
			//var_dump('<pre>', $retProd, '</pre>'); 

			return $isValid ? $retProd : array();
		}

		public function _cross_sell_calc_tries( $nb_tries, $nb_tries_orig, $force_update ) {
			$ret = array('query' => '', 'nb' => $nb_tries);

			$ret['query'] = '';
			if ( $force_update ) ; // don't count tries if you force update
			else {
				if ( 'inc' == $nb_tries ) {
					$ret['query'] = ', nb_tries = nb_tries + 1';
				}
				else {
					$ret['query'] = ', nb_tries = '.$nb_tries;
				}
				$ret['query'] = ' '.$ret['query'].' ';
			}

			// here because of force_update case above
			if ( 'inc' == $nb_tries ) {
				$ret['nb'] = $nb_tries_orig + 1;
			}

			return $ret;
		}

		public function _cross_sell_debug_msg( $pms=array() ) {
			$pms = array_replace_recursive(array(
				'msg'			=> '',
				'msg_extra'	=> array(),
			), $pms);
			extract($pms);

			$html = array();
			if ( '' != $msg ) {
				$html[] = '<div>' . $msg . '</div>';
			}
			if ( ! empty($msg_extra) && is_array($msg_extra) ) {

				$from_cache = isset($msg_extra['from_cache']) && $msg_extra['from_cache'] ? true : false;
				unset($msg_extra['from_cache']);

				$html[] = '<div>';
				$html[] = 		'<table>';
				$html[] = 			'<thead>';
				$html[] =				'<tr>';
				foreach ($msg_extra as $key => $val) {
					$html[] = 				'<th>' . str_replace('_', ' ', $key) . '</th>';
				}
				$html[] =				'</tr>';
				$html[] = 			'</thead>';
				$html[] = 			'<tbody>';
				$html[] = 				'<tr>';
				foreach ($msg_extra as $key => $val) {
					$html[] = 				'<td>' . $val . '</td>';
				}
				$html[] = 				'</tr>';
				$html[] = 			'</tbody>';
				$html[] = 		'</table>';
				$html[] = '</div>';
				
				if ( $from_cache ) {
					$html[] = '<div><button>empty cache</button></div>';
				}
			}

			return implode(PHP_EOL, $html); 
		}

		public function _cross_sell_empty_cache( $pms=array() ) {
			extract($pms);

			$db = $this->the_plugin->db;

			$asin = (string) $asin;

			$query = "DELETE FROM " . ( $db->prefix ) . "amz_cross_sell WHERE ASIN = %s;";
			$query = $db->prepare( $query, $asin );
			return $db->query( $query );
		}

		// woocommerce fix thumb for remote images with https - on frontend
		public function woocommerce_before_mini_cart() {
			echo '<div style="display: none;" class="WooZoneLite-fix-minicart"></div>';
		}
	}
}