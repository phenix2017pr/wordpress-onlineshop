<?php
//:::::::::::::::::::::::::::::::::::::::::::::
//:: DEFAULT PLUGIN SETTINGS
//WHEN YOU DO A PLUGIN FRESH INSTALL AND IMMEDIATELY GO TO AMAZON CONFIG MODULE & SAVE SETTINGS
$woozonelite_default_settings = array();
$woozonelite_default_settings = array_replace_recursive($woozonelite_default_settings, array(
	'disable_amazon_checkout' => 'no',
	'services_used_forip' => 'www.geoplugin.net',
	'charset' => '',
	'product_buy_is_amazon_url' => 'yes',
	'frontend_show_free_shipping' => 'yes',
	'frontend_show_coupon_text' => 'yes',
	'onsite_cart' => 'yes',
	'checkout_email' => 'no',
	'checkout_email_mandatory' => 'no',
	'item_attribute' => 'yes',
	'attr_title_normalize' => 'no',
	'90day_cookie' => 'yes',
	'price_setup' => 'only_amazon',
	'merchant_setup' => 'amazon_or_sellers',
	'import_price_zero_products' => 'no',
	'product_variation' => 'yes_5',
	'default_import' => 'publish',
	'import_type' => 'default',
	'ratio_prod_validate' => '90',
	'cron_number_of_images' => '100',
	'number_of_images' => 'all',
	'rename_image' => 'product_title',
	'remove_gallery' => 'no',
	'remove_featured_image_from_gallery' => 'no',
	'show_short_description' => 'yes',
	'show_review_tab' => 'yes',
	'redirect_checkout_msg' => 'You will be redirected to {amazon_website} to complete your checkout!',
	'redirect_time' => '3',
	'product_buy_text' => '',
	'product_buy_button_open_in' => '_self',
	'spin_at_import' => 'no',
	'spin_max_replacements' => '10',
	'create_only_parent_category' => 'no',
	'variation_force_parent' => 'yes',
	'remote_amazon_images' => 'yes',
	'clean_duplicate_attributes' => 'YES',
	'clean_duplicate_category_slug' => 'YES',
	'delete_zeropriced_products' => 'YES',
	'clean_orphaned_amz_meta' => 'YES',
	'clean_orphaned_prod_assets' => 'YES',
	'clean_orphaned_prod_assets_wp' => 'YES',
	'fix_product_attributes' => 'YES',
	'fix_node_childrens' => 'YES',
	'protocol' => 'auto',
	'country' => 'com',
	'AccessKeyID' => '',
	'SecretAccessKey' => '',
	'AffiliateID' => array (
		'com' => '',
		'uk' => '',
		'de' => '',
		'fr' => '',
		'jp' => '',
		'ca' => '',
		'cn' => '',
		'in' => '',
		'it' => '',
		'es' => '',
		'mx' => '',
		'br' => '',
	),
	'main_aff_id' => 'com',
	'amazon_requests_rate' => '1',
	'fix_issue_request_amazon' => 'YES',
	'fix_issue_sync' => array (
		'trash_tries' => '3',
		'restore_status' => 'publish',
	),
	'reset_products_stats' => 'YES',
	'options_prefix_change' => 'use_new',
	'unblock_cron' => 'YES',
	'productinpost_additional_images' => 'yes',
	'productinpost_extra_css' => '',
	'product_countries' => 'yes',
	'product_countries_main_position' => 'before_add_to_cart',
	'product_countries_maincart' => 'yes',
	'product_countries_countryflags' => 'no',
	'asof_font_size' => '0.6',
	'delete_attachments_at_delete_post' => 'no',
	'cross_selling' => 'yes',
	'cross_selling_nbproducts' => '3',
	'cross_selling_choose_variation' => 'first',
	'debug_ip' => '',
	'cache_remote_images' => 'file',
));
$woozonelite_default_settings = array_replace_recursive($woozonelite_default_settings, array(
	//'product_buy_text' => 'Checkout with Amazon',
	//'spin_at_import' => 'yes',
	//'cross_selling' => 'no',
));


//:::::::::::::::::::::::::::::::::::::::::::::
//:: DEFAULT VALUES FOR: UNCHECKED CHECKBOXES, UNCHECKED RADIO BUTTONS, EMPTY SELECTS
//ARRAY FIRST LEVEL KEY IS THE STEP
$woozonelite_settings_empty_selection = array(
	'questions'		=> array(
		'site_info'						=> '', //radio
		'site_install'					=> '', //radio
		'site_purpose'					=> '', //radio
	),
	'settings'		=> array(
		'onsite_cart'					=> 'no', //checkbox
		'90day_cookie'					=> 'no', //checkbox
		'show_review_tab'				=> 'no', //checkbox
		'cross_selling'					=> 'no', //checkbox
		'product_countries'				=> 'no', //checkbox
		'frontend_show_coupon_text'		=> 'no', //checkbox
		'checkout_email'				=> 'no', //checkbox
		'remote_amazon_images'			=> 'no', //checkbox
		'frontend_show_free_shipping'	=> 'no', //checkbox
	),
	'prices_setup'	=> array(
		'import_price_zero_products'	=> 'no', //checkbox
		'item_attribute'				=> 'no', //checkbox
	),
	'fine_tuning'	=> array(
		'spin_at_import'				=> 'no', //checkbox
	),
	'customization' => array(),
	'amazon_config' => array(),
);


//:::::::::::::::::::::::::::::::::::::::::::::
//:: DEFAULT SETTINGS PER QUESTION ANSWER
$woozonelite_settings_per_questions_def = array_replace_recursive(array(), array(
	'disable_amazon_checkout' 		=> 'no',
	'onsite_cart'					=> 'yes',
	'90day_cookie'					=> 'yes',
	'show_review_tab'				=> 'yes',
	'cross_selling'					=> 'yes',
	'product_countries'				=> 'yes',
	'frontend_show_coupon_text'		=> 'yes',
	'checkout_email'				=> 'yes',
	'remote_amazon_images'			=> 'yes',
	'frontend_show_free_shipping'	=> 'yes',
	'price_setup' 					=> 'only_amazon',
	'merchant_setup' 				=> 'only_amazon',
	'default_import' 				=> 'publish',
	'import_price_zero_products'	=> 'no',
	'item_attribute'				=> 'yes',
	'number_of_images' 				=> 'all',
	'product_variation' 			=> 'yes_all',
	'spin_at_import'				=> 'yes',
));

$woozonelite_settings_per_questions = array();
//:: A
$woozonelite_settings_per_questions[0] = array_replace_recursive($woozonelite_settings_per_questions_def, array(
	'onsite_cart'					=> 'no',
	'checkout_email'				=> 'no',
	'number_of_images' 				=> '10',
	'product_variation' 			=> 'yes_10',
	'spin_at_import'				=> 'no',
));
//:: B - default
$woozonelite_settings_per_questions[1] = array_replace_recursive($woozonelite_settings_per_questions_def, array(
	'price_setup' 					=> 'amazon_or_sellers',
	'merchant_setup' 				=> 'amazon_or_sellers',
));
//:: C
$woozonelite_settings_per_questions[2] = array_replace_recursive($woozonelite_settings_per_questions_def, array(
	'disable_amazon_checkout' 		=> 'yes',
	'onsite_cart'					=> 'no',
	'90day_cookie'					=> 'no',
	'cross_selling'					=> 'no',
	'product_countries'				=> 'no',
	'frontend_show_coupon_text'		=> 'no',
	'checkout_email'				=> 'no',
	'frontend_show_free_shipping'	=> 'no',
	'price_setup' 					=> 'only_amazon',
	'merchant_setup' 				=> 'amazon_or_sellers',
));
?>