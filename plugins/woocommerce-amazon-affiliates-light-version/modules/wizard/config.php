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
		'wizard' => array(
			'version' => '1.0',
			'menu' => array(
				'order' => 3,
				'title' => 'WooZoneLite Wizard',
				'icon' => 'magic-wand'
			),
			'in_dashboard' => array(
				'icon' 	=> 'magic-wand',
				'url'	=> admin_url("admin.php?page=WooZoneLite_wizard")
			),
			'description' => "Welcome to WZone Setup Wizard",
			'help' => array(
				'type' => 'remote',
				'url' => 'http://docs.aa-team.com/woocommerce-amazon-affiliates/documentation/'
			),
			'module_init' => 'init.php',
			'load_in' => array(
				'backend' => array(
				    'admin.php?page=WooZoneLite_wizard',
					//'admin-ajax.php'
				),
				'frontend' => false
			),
			'javascript' => array(
				'admin',
				//'hashchange',
				//'tipsy'
			),
			'css' => array(
				'admin',
				//'tipsy'
			)
		)
	)
 );