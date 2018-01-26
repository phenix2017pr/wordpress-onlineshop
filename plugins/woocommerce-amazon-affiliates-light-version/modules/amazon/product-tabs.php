<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if (!class_exists('amzAffReviewProductTab')) :

class amzAffReviewProductTab {
	private $tab_data = false;
	const VERSION = "1.0";

	private $tab_title;

	public function __construct() {
		global $WooZoneLite;
		
		$this->tab_title = __('Amazon Customer Reviews', 'woozonelite');
		
		$config = is_object($WooZoneLite) ? $WooZoneLite->settings() : array();
		
		if( isset($config['show_review_tab']) && $config['show_review_tab'] == 'yes') {
			add_action( 'init', array( $this, 'init' ));
		}
		
		// Installation
		if (is_admin() && !defined('DOING_AJAX')) $this->install();
	}

	public function init() {
		
		// backend stuff
		add_action('woocommerce_product_write_panel_tabs', array($this, 'product_write_panel_tab'));
		add_action('woocommerce_product_write_panels', array($this, 'product_write_panel'));
		add_action('woocommerce_process_product_meta', array($this, 'product_save_data'), 10, 2);

		// frontend stuff
		add_action('woocommerce_product_tabs', array($this, 'custom_product_tabs'), 25);  // in between the attributes and reviews panels
	}

	/**
	 * Write the custom tab on the product view page.  In WooCommerce these are
	 * handled by templates.
	 */
	public function custom_product_tabs( $tabs = array() ) {
		global $product, $WooZoneLite;

		if($this->product_has_custom_tabs($product)) {

			$priority = 15;
			foreach($this->tab_data as $tab) {

				$tabs[$tab['id']] = array(
					'title'    => $this->tab_title,
					'priority' => $priority,
					'callback' => array($this, 'product_review_tab')
				);
			}
		}

		return $tabs;
	}

	public function product_review_tab( $tab ) {
		global $product;

		if($this->product_has_custom_tabs($product)) {
			$content = $this->tab_data[0]['content'];
			
			preg_match('/src="([^"]+)"/', $content, $match);
			$url = $match[1];
			
			if( trim($url) != "" ){
				// now try to parse the string 
				parse_str( $url, $params );
				
				// verify if link expire 
				if( trim($params['exp']) != "" ){
					$expire_on = strtotime($params['exp']);
					
					if( time() > $expire_on ){
						// need to update the custom 
						global $post, $WooZoneLite;
						
						$post_id = (int)$post->ID > 0 ? $post->ID : 0;
						if( $post_id > 0 ){
							$new_url = $WooZoneLite->amzHelper->updateProductReviews( $post_id );
							
							// update the url into content iframe tag
							$content = str_replace( $url, $new_url, $content);
						} 
					}  
					// keep some debugs
					//var_dump('<pre>', date( "F j, Y, g:i a", strtotime($params['exp'])),'</pre>'); die;  
				}
			}  
			
			echo str_replace( "http://", "//", $content );
		}
	}

	/**
	 * Lazy-load the product_tabs meta data, and return true if it exists,
	 * false otherwise
	 *
	 * @return true if there is custom tab data, false otherwise
	 */
	private function product_has_custom_tabs($product) {
		global $WooZoneLite;

		if($this->tab_data === false) {
			$prod_id = 0;
			if ( is_object($product) ) {
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
			}

			$reviews = maybe_unserialize( get_post_meta($prod_id, 'amzaff_woo_product_tabs', true) );
			//if ( isset($reviews[0]) ) $this->tab_data[] = $reviews[0];
			$this->tab_data[] = isset($reviews, $reviews[0]) ? $reviews[0] : array('content' => ''); //fixed!
			 
			if( isset($cross_selling) && count($cross_selling) > 1 ){
				$this->tab_data[] = array(
					'id' => $WooZoneLite->alias . '_cross_selling_tab',
					'content' => $this->item_cross_selling( $cross_selling )
				);
			}
		}
  
		// tab must at least have a title to exist
		return !empty($this->tab_data) && !empty($this->tab_data[0]) && !empty($this->tab_data[0]['content']);
	}

	/**
	 * Adds a new tab to the Product Data postbox in the admin product interface
	 */
	public function product_write_panel_tab() {
		echo "<li><a style=\"color:#555555;line-height:16px;padding:9px;text-shadow:0 1px 1px #FFFFFF;\" href=\"#product_tabs\">". $this->tab_title ."</a></li>";
	}

	/**
	 * Adds the panel to the Product Data postbox in the product interface
	 */
	public function product_write_panel() {
		global $post;  // the product

		// pull the custom tab data out of the database
		$tab_data = maybe_unserialize( get_post_meta($post->ID, 'amzaff_woo_product_tabs', true) );

		if(empty($tab_data)) {
			$tab_data[] = array('title' => '', 'content' => '');
		}

		foreach($tab_data as $tab) {
			// display the custom tab panel
			echo '<div id="product_tabs" class="panel woocommerce_options_panel">';
			$this->woocommerce_wp_textarea_input( array( 'id' => '_tab_content', 'label' => __('Content'), 'placeholder' => __('HTML and text to display.'), 'value' => $tab['content'], 'style' => 'width:70%;height:21.5em;' ) );
			echo '</div>';
		}
	}

	private function woocommerce_wp_textarea_input( $field ) {
		global $thepostid, $post;

		if (!$thepostid) $thepostid = $post->ID;
		if (!isset($field['placeholder'])) $field['placeholder'] = '';
		if (!isset($field['class'])) $field['class'] = 'short';
		if (!isset($field['value'])) $field['value'] = get_post_meta($thepostid, $field['id'], true);

		echo '<p class="form-field '.$field['id'].'_field"><label for="'.$field['id'].'">'.$field['label'].'</label><textarea class="'.$field['class'].'" name="'.$field['id'].'" id="'.$field['id'].'" placeholder="'.$field['placeholder'].'" rows="2" cols="20"'.(isset($field['style']) ? ' style="'.$field['style'].'"' : '').'">'.esc_textarea( $field['value'] ).'</textarea> ';

		if (isset($field['description']) && $field['description']) echo '<span class="description">' .$field['description'] . '</span>';

		echo '</p>';
	}

	/**
	 * Saves the data inputed into the product boxes, as post meta data
	 * identified by the name 'amzaff_woo_product_tabs'
	 *
	 * @param int $post_id the post (product) identifier
	 * @param stdClass $post the post (product)
	 */
	public function product_save_data( $post_id, $post ) {

		$tab_content = stripslashes($_POST['_tab_content']);

		if(empty($tab_title) && empty($tab_content) && get_post_meta($post_id, 'amzaff_woo_product_tabs', true)) {
			// clean up if the custom tabs are removed
			delete_post_meta($post_id, 'amzaff_woo_product_tabs');
		} elseif(!empty($tab_content)) {
			$tab_data = array();


			// save the data to the database
			$tab_data[] = array('id' => 'amzAff-customer-review',
								'content' => $tab_content);
			update_post_meta($post_id, 'amzaff_woo_product_tabs', $tab_data);
		}
	}

	/**
	 * Run every time since the activation hook is not executed when updating a plugin
	 */
	private function install() {
		if(get_option('woocommerce_custom_product_tabs_lite_db_version') != amzAffReviewProductTab::VERSION) {
			$this->upgrade();

			// new version number
			update_option('woocommerce_custom_product_tabs_lite_db_version', amzAffReviewProductTab::VERSION);
		}
	}

	/**
	 * Run when plugin version number changes
	 */
	private function upgrade() {
		global $wpdb;
		if(!get_option('woocommerce_custom_product_tabs_lite_db_version')) {
			// this is one of the couple of original users who installed before I had a version option in the db
			//  rename the post meta option 'product_tabs' to 'amzaff_woo_product_tabs'
			$wpdb->query("UPDATE {$wpdb->postmeta} SET meta_key='amzaff_woo_product_tabs' WHERE meta_key='product_tabs';");
		}
	}

	/**
	 * Runs various functions when the plugin first activates (and every time
	 * its activated after first being deactivated), and verifies that
	 * the WooCommerce plugin is installed and active
	 *
	 * @see register_activation_hook()
	 * @link http://codex.wordpress.org/Function_Reference/register_activation_hook
	 */
	public static function on_activation() {
		// checks if the woocommerce plugin is running and disables this plugin if it's not (and displays a message)
		if (!is_plugin_active('woocommerce/woocommerce.php') || !is_plugin_active('envato-wordpress-toolkit/woocommerce.php')) {
			deactivate_plugins(plugin_basename(__FILE__));
			wp_die(__('The WooCommerce Product Tabs <a href="http://www.woothemes.com/woocommerce/" target="_blank">WooCommerce</a> to be activated in order to work. Please install and activate <a href="http://www.woothemes.com/woocommerce/" target="_blank">WooCommerce</a> first. <a href="'.admin_url('plugins.php').'"> <br> &laquo; Go Back</a>'));
		}

		// set version number
		update_option('woocommerce_custom_product_tabs_lite_db_version', amzAffReviewProductTab::VERSION);
	}
}

/**
 * instantiate class
 */
$woocommerce_product_tabs_lite = new amzAffReviewProductTab();

endif; // class exists check

/**
 * run the plugin activation hook
 */
register_activation_hook(__FILE__, array('amzAffReviewProductTab', 'on_activation'));
