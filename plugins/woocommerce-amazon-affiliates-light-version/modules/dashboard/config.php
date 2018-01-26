<?php
/**
* Config file, return as json_encode
* http://www.aa-team.com
* =======================
*
* @author		Andrei Dinca, AA-Team
* @version		1.0
*/
echo json_encode(array(
    'dashboard' => array(
        'version' => '1.0',
        'menu' => array(
            'order' => 1,
            'title' => 'Dashboard'
            ,'icon' => 'dashboard'
        ),
        'description' => "Dashboard. This is a Lite Version of WZone, so there are some restrictions. If you wish to take advantage of WZone full features, please purchase it from <a href=\"https://codecanyon.net/item/woocommerce-amazon-affiliates-wordpress-plugin/3057503?ref=AA-Team\">Codecanyon!</a>",
        'help' => array(
			'type' => 'remote',
			'url' => 'http://docs.aa-team.com/products/woocommerce-amazon-affiliates/'
		),
        'module_init' => 'init.php',
			'load_in' => array(
				'backend' => array(
					'admin-ajax.php'
				),
				'frontend' => false
			),
			'javascript' => array(
				'admin',
				'hashchange',
				'counter',
				'tipsy'
			),
			'css' => array(
				'admin',
				'tipsy'
			)
    )
));