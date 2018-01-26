<?php
/**
 * Config file, return as json_encode
 * http://www.aa-team.com
 * ======================
 *
 * @author		Andrei Dinca, AA-Team
 * @version		1.0
 */
 echo json_encode(
	array(
		'modules_manager' => array(
			'version' => '1.0',
			'menu' => array(
				'order' => 30,
				'title' => __('Modules manager', 'WooZoneLite')
				,'icon' => 'modules'
			),
			'in_dashboard' => array(
				'icon' 	=> 'modules',
				'url'	=> admin_url("admin.php?page=WooZoneLite#!/modules_manager")
			),
			'description' => __("Using this module you can activate / deactivate plugin modules.", 'WooZoneLite'),
      	  	'help' => array(
				'type' => 'remote',
				'url' => 'http://docs.aa-team.com/woocommerce-amazon-affiliates/documentation/modules-manager/'
			),
			'load_in' => array(
				'backend' => array(
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
				'admin'
			)
		)
	)
 );