<?php
/**
 * Config file, return as json_encode
 * http://www.aa-team.com
 * =======================
 *
 * @author		Andrei Dinca, AA-Team
 * @version		1.0
 */
echo json_encode(
	array(
		'assets_download' => array(
			'version' => '1.0',
			'menu' => array(
				'order' => 4,
				'show_in_menu' => false,
				'title' => 'Remote images',
				'icon' => 'assets_dwl'
			),
			
			'help' => array(
				'type' => 'remote',
				'url' => 'http://docs.aa-team.com/woocommerce-amazon-affiliates/documentation/assets-download/'
			),
			'description' => "Remote images from Amazon CDN",
			'module_init' => 'init.php',
			'load_in' => array(
				'backend' => array(
					'admin.php?page=WooZoneLite_assets_download',
					'admin.php?page=WooZoneLite_asin_grabber',
					'admin-ajax.php'
				),
				'frontend' => false
			),
			'javascript' => array(
				'admin',
				'hashchange',
				'tipsy'
			),
			'css' => array(
				'admin',
				'tipsy'
			)
		)
	)
);