<?php
/*

* Define class Modules Manager List

* Make sure you skip down to the end of this file, as there are a few

* lines of code that are very important.

*/
!defined('ABSPATH') and exit;
if (class_exists('WooZoneLiteAAModulesManger') != true) {
	class WooZoneLiteAAModulesManger
	{
		/*
		* Some required plugin information
		*/
		const VERSION = '1.0';

		/*
		* Store some helpers config
		*
		*/
		public $cfg = array();

		/*
		* Store some helpers config
		*/
		public $the_plugin = null;

		private $module_folder = '';
		private $module = '';

		private $settings = array();

		static protected $_instance;
		
		/**
	    * Singleton pattern
	    *
	    * @return Singleton instance
	    */
		static public function getInstance()
		{
			if (!self::$_instance) {
				self::$_instance = new self;
			}

			return self::$_instance;
		}

		/*
		* Required __construct() function that initalizes the AA-Team Framework
		*/
		public function __construct() //public function __construct($cfg)
		{
			global $WooZoneLite;

			$this->the_plugin = $WooZoneLite;
			$this->module_folder = $this->the_plugin->cfg['paths']['plugin_dir_url'] . 'modules/modules_manager/';
			$this->module = $this->the_plugin->cfg['modules']['modules_manager'];

			$this->settings = $this->the_plugin->getAllSettings( 'array', 'modules_manager' );
			
			$this->cfg = $this->the_plugin->cfg; //$this->cfg = $cfg;
		}
		
		public function printListInterface()
		{
			$html   = array();
			
			$html[] = '
			<!-- Main loading box -->
			<div id="WooZoneLite-main-loading">
				<div id="WooZoneLite-loading-overlay"></div>
				<div id="WooZoneLite-loading-box">
					<div class="WooZoneLite-loading-text">' . __('Loading', 'WooZoneLite') . '</div>
					<div class="WooZoneLite-meter WooZoneLite-animate" style="width:86%; margin: 34px 0px 0px 7%;"><span style="width:100%"></span></div>
				</div>
			</div>
			';

			$html[] = '<script type="text/javascript" src="' . $this->module_folder . 'app.modules_manager.js" ></script>';

			$html[] = '<table class="WooZoneLite-table" id="' . ($this->cfg['default']['alias']) . '-module-manager" style="border-collapse: collapse;border-spacing: 0;">';
			$html[] = '<thead>
						<tr>
							<th width="10"><input type="checkbox" id="WooZoneLite-item-check-all" checked></th>
							<th width="10">' . __('Icon', 'WooZoneLite') . '</th>
							<th width="10">' . __('Version', 'WooZoneLite') . '</th>
							<th width="350" align="left">' . __('Name', 'WooZoneLite') . '</th>
							<th align="left">' . __('About', 'WooZoneLite') . '</th>
						</tr>
					</thead>';
			$html[] = '<tbody>';
			$cc     = 0;
			foreach ($this->cfg['modules'] as $key => $value) {
				$module = $key;
				/*if ( !in_array($module, $this->cfg['core-modules'])
					&& !$this->the_plugin->capabilities_user_has_module($module)
				) {
					continue 1;
				}*/
				
				$icon = array(
					    'advanced_search'		=> 		'WooZoneLite-icon-search',
					    'amazon'				=> 		'WooZoneLite-icon-amazon',
					    'amazon_debug' 			=> 		'WooZoneLite-icon-debug',
					    'asin_grabber'			=> 		'WooZoneLite-icon-asin_grabber',
					    'assets_download'		=> 		'WooZoneLite-icon-assets_dwl',
					    'auto_import'			=> 		'WooZoneLite-icon-auto_import',
					    'content_spinner'		=> 		'WooZoneLite-icon-content_spinner',
					    'cronjobs'				=> 		'WooZoneLite-icon-cronjobs',
					    'csv_products_import'	=> 		'WooZoneLite-icon-csv_import',
					    'insane_import'			=> 		'WooZoneLite-icon-insane_import',
					    'modules_manager'		=> 		'WooZoneLite-icon-modules',
					    'price_select'			=> 		'fa fa-money',
					    'product_in_post'		=> 		'fa fa-file-powerpoint-o',
					    'remote_support'		=> 		'WooZoneLite-icon-support',
					    'report'				=> 		'WooZoneLite-icon-woozonelite_report',
					    'server_status'			=> 		'WooZoneLite-icon-server_status',
					    'setup_backup'			=> 		'WooZoneLite-icon-setup_backup',
					    'stats_prod'			=> 		'WooZoneLite-icon-products_statistics',
					    'synchronization'		=> 		'WooZoneLite-icon-sync',
					    'woocustom'				=> 		'fa fa-pencil',
					    'dashboard'				=> 		'WooZoneLite-icon-dashboard',
					    'wizard'				=> 		'WooZoneLite-icon-magic-wand'
				);


				/* if (is_file($value["folder_path"] . $value[$key]['menu']['icon'])) {
					$icon = $value["folder_uri"] . $value[$key]['menu']['icon'];
				} */


				$html[] = '<tr class="' . ($cc % 2 ? 'odd' : 'even') . '">
                	<td align="center">';
				// activate / deactivate plugin button
				if ($value['status'] == true) {
					if (!in_array($key, $this->cfg['core-modules'])) {
						$html[] = '<input type="checkbox" class="WooZoneLite-item-checkbox" name="WooZoneLite-item-checkbox-' . ( $key ) . '" checked>';
					} else {
						$html[] = ""; // core module
					}
				} else {
					$html[] = '<input type="checkbox" class="WooZoneLite-item-checkbox" name="WooZoneLite-item-checkbox-' . ( $key ) . '">';
				}
				$html[] = '</td>
					<td align="center">' . (($icon[$key]) != "" ? '<i class="' . ($icon[$key]) . '">' . '</i>' : '') . '</td>
					<td align="center">' . ($value[$key]['version']) . '</td>
					<td>';
				// activate / deactivate plugin button
				if ($value['status'] == true) {
					if (!in_array($key, $this->cfg['core-modules'])) {
						$html[] = '<a href="#deactivate" class="deactivate" rel="' . ($key) . '">Deactivate</a>';
					} else {
						$html[] = "<span>" . __("Core Modules, can't be deactivated!", 'WooZoneLite') . "</span>";
					}
				} else {
					$html[] = '<a href="#activate" class="activate" rel="' . ($key) . '">' . __('Activate', 'WooZoneLite') . '</a>';
				}
				$html[] = "&nbsp; | &nbsp;" . $value[$key]['menu']['title'];
				$html[] = '</td>
					<td>' . (isset($value[$key]['description']) ? $value[$key]['description'] : '') . '</td>
				</tr>';
				$cc++;
			}
			$html[] = '</tbody>';
			$html[] = '</table>';

			$html[] = '<div class="WooZoneLite-list-table-left-col" style="padding-top: 5px; padding-bottom: 5px;">';
			$html[] = 	'<input type="button" value="' . __('Activate selected modules', 'WooZoneLite') . '" id="WooZoneLite-activate-selected" class="WooZoneLite-form-button WooZoneLite-form-button-info">';
			$html[] = 	'<input type="button" value="' . __('Deactivate selected modules', 'WooZoneLite') . '" id="WooZoneLite-deactivate-selected" class="WooZoneLite-form-button WooZoneLite-form-button-danger">';
			$html[] = '</div>';

			return implode("\n", $html);
		}
	}
}
// Initalize the your WooZoneLiteAAModulesManger
//$WooZoneLiteAAModulesManger = new WooZoneLiteAAModulesManger($this->cfg, $module);
//$WooZoneLiteAAModulesManger = new WooZoneLiteAAModulesManger($this->cfg);