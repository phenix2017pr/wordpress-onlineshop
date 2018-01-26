<?php
/**
 * Dummy module return as json_encode
 * http://www.aa-team.com
 * =======================
 *
 * @author		Andrei Dinca, AA-Team
 * @version		1.0
 */

global $WooZoneLite;

echo json_encode(
	array(
		$tryed_module['db_alias'] => array(

			/* define the form_messages box */
			'module_box' => array(
				'title' 	=> __('Modules Manager', 'WooZoneLite'),
				'icon' 		=> '{plugin_folder_uri}images/menu_icon.png',
				'size' 		=> 'grid_4', // grid_1|grid_2|grid_3|grid_4
				'header' 	=> true, // true|false
				'toggler' 	=> false, // true|false
				'buttons' 	=> false, // true|false
				'style' 	=> 'panel', // panel|panel-widget

				// create the box elements array
				'elements'	=> array(
					array(
						'type' 		=> 'app',
						'path' 		=> '{plugin_folder_path}panel.php',
					)
				)
			)
		)
	)
);