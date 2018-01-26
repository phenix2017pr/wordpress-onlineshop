<?php
/**
 * Config file, return as json_encode
 * http://www.aa-team.com
 * =======================
 *
 * @author		Andrei Dinca, AA-Team
 * @version		1.0
 */
global $WooZoneLite;
echo json_encode(
	array(
		'insane_import' => array(
			'version' => '1.0',
			'menu' => array(
				'order' => 4,
				'show_in_menu' => false,
				'title' => 'Insane Import Mode',
				'icon' => 'insane_import'
			),
			'in_dashboard' => array(
				'icon' 	=> 'insane_import',
				'url'	=> admin_url("admin.php?page=WooZoneLite_insane_import")
			),
			'help' => array(
				'type' => 'remote',
				'url' => 'http://docs.aa-team.com/woocommerce-amazon-affiliates/documentation/amazon-asin-grabber/'
			),
			'description' => "With this module you can import hundred of amazon products.This is a Lite Version of WZone, so there are some restrictions. If you wish to take advantage of WZone full features, please purchase it from <a href=\"https://codecanyon.net/item/woocommerce-amazon-affiliates-wordpress-plugin/3057503?ref=AA-Team\">Codecanyon!</a>",
			'module_init' => 'init.php',
			'load_in' => array(
				'backend' => array(
					'admin.php?page=WooZoneLite_assets_download',
					'admin.php?page=WooZoneLite_insane_import',
					'admin-ajax.php'
				),
				'frontend' => false
			),
			'javascript' => array(
				'admin',
				'download_asset',
				'hashchange',
				'tipsy'
			),
			'css' => array(
				'admin',
				'tipsy'
			),
            'errors' => array(
                1 => __('
                    You configured Insane Import Mode incorrectly. See 
                    ' . ( is_object($WooZoneLite) ? $WooZoneLite->convert_to_button ( array(
                        'color' => 'white_blue WooZoneLite-show-docs-shortcut',
                        'url' => 'javascript: void(0)',
                        'title' => 'here'
                    ) ) : '' ) . ' for more details on fixing it. <br />
                    Setup the Amazon config mandatory settings ( Access Key ID, Secret Access Key, Main Affiliate ID ) 
                    ' . ( is_object($WooZoneLite) ? $WooZoneLite->convert_to_button ( array(
                        'color' => 'white_blue',
                        'url' => admin_url( 'admin.php?page=WooZoneLite#!/amazon' ),
                        'title' => 'here'
                    ) ) : '' ) . '
                    ', $WooZoneLite->localizationName),
                2 => __('
                    You don\'t have WooCommerce installed/activated! Please activate it:
                    ' . ( is_object($WooZoneLite) ? $WooZoneLite->convert_to_button ( array(
                        'color' => 'white_blue',
                        'url' => admin_url('plugin-install.php?tab=search&s=woocommerce&plugin-search-input=Search+Plugins'),
                        'title' => 'NOW'
                    ) ) : '' ) . '
                    ', $WooZoneLite->localizationName),
                //You don\'t have neither the SOAP library or cURL library installed! Please activate it!
                3 => __('
                    You don\'t have the SOAP library installed! Please activate it!
                    ', $WooZoneLite->localizationName),
                4 => __('
                    You don\'t have the cURL library installed! Please activate it!
                    ', $WooZoneLite->localizationName)
            )
		)
	)
);