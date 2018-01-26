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
		'server_status' => array(
			'version' => '1.0',
			'menu' => array(
				'order' => 4,
				'show_in_menu' => false,
				'title' => 'Server status',
				'icon' => 'server_status'
			),
			'in_dashboard' => array(
				'icon' 	=> 'server_status',
				'url'	=> admin_url("admin.php?page=WooZoneLite_server_status")
			),
			'help' => array(
				'type' => 'remote',
				'url' => 'http://docs.aa-team.com/woocommerce-amazon-affiliates/documentation/server-status/'
			),
			'description' => 'Using the server status module you can check if your install is correct, if you have the right server configuration and test product import.',
			'module_init' => 'init.php',
			'load_in' => array(
				'backend' => array(
					'admin.php?page=WooZoneLite_server_status',
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