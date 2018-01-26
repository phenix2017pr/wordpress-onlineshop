<?php
class xoo_wsc_Cart_Data{
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0 
	 * @access   private
	 * @var      string    $xoo_wsc    The ID of this plugin.
	 */
	private $xoo_wsc;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $xoo_wsc    The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $xoo_wsc ) {

		$this->xoo_wsc = $xoo_wsc;

	}


	/**
	 * Get Side Cart HTML
	 *
	 * @since     1.0.0
	 * @return    string 
	 */

	public function get_cart_markup(){
		if(is_cart() || is_checkout()){return;}
		require_once  plugin_dir_path( dirname( __FILE__ ) ).'/public/partials/xoo-wsc-markup.php';	
	}


	/**
	 * Get Side Cart Content
	 *
	 * @since     1.0.0
	 */

	public function get_cart_content(){
		$cart_data 	= WC()->cart->get_cart(); 
		$options 	= get_option('xoo-wsc-gl-options');
		$sy_options = get_option('xoo-wsc-sy-options');

		$args = array(
			'options' => $options,
			'sy_options' => $sy_options
		);

		ob_start();
		wc_get_template('xoo-wsc-content.php',$args,'',XOO_WSC_PATH.'/public/partials/');
		wc_get_template('xoo-wsc-footer.php',$args,'',XOO_WSC_PATH.'/public/partials/');
		return ob_get_clean();
	}

	/**
	Set fragments

	**/

	public function set_ajax_fragments($fragments){

		//Get User Settings
		$options = get_option('xoo-wsc-gl-options');
		$show_count = isset( $options['bk-show-bkcount']) ? $options['bk-show-bkcount'] : 1;

		if($show_count == 1){
			$count_value = WC()->cart->get_cart_contents_count();
		}
		else{
			$count_value = 0;
		}

		$cart_content = $this->get_cart_content();

		//Cart content
		$fragments['div.xoo-wsc-container'] = '<div class="xoo-wsc-container">'.$cart_content.'</div>';

		//Total Count
		$fragments['span.xoo-wsc-items-count'] = '<span class="xoo-wsc-items-count">'.$count_value.'</span>';

		return $fragments;
	}


	/**
	 * Add product to cart
	 *
	 * @since     1.0.0
	 */


	public function xoo_wsc_add_to_cart_ajax(){

		if(!isset($_POST['action']) || $_POST['action'] != 'xoo_wsc_add_to_cart' || !isset($_POST['add-to-cart'])){
			die();
		}
		
		// get woocommerce error notice
		$error = wc_get_notices( 'error' );
		$html = '';

		if( $error ){
			// print notice
			ob_start();
			foreach( $error as $value ) {
				wc_print_notice( $value, 'error' );
			}

			$js_data =  array(
				'error' => ob_get_clean()
			);

			wc_clear_notices(); // clear other notice
			wp_send_json($js_data);
		}
		
		else{
			// trigger action for added to cart in ajax
			do_action( 'woocommerce_ajax_added_to_cart', intval( $_POST['add-to-cart'] ) );
			wc_clear_notices(); // clear other notice
			WC_AJAX::get_refreshed_fragments();	
		}

		die();
	}



	/**
	 * Update product quantity in cart.
	 *
	 * @since     1.0.0
	 */

	public function update_cart_ajax(){

		//Form Input Values
		$cart_key = sanitize_text_field($_POST['cart_key']);

		//If empty return error
		if(!$cart_key){
			wp_send_json(array('error' => __('Something went wrong','side-cart-woocommerce')));
		}
		
		$cart_success = WC()->cart->remove_cart_item($cart_key);
		
		if($cart_success){
			WC_AJAX::get_refreshed_fragments();
		}
		else{
			if(wc_notice_count('error') > 0){
	    		echo wc_print_notices();
			}
		}
		die();
	}
}
?>