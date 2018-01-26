<?php
/**
 * AA-Team - http://www.aa-team.com
 * ===============================+
 *
 * @package		WooZoneLiteAdminMenu
 * @author		Andrei Dinca
 * @version		1.0
 */
! defined( 'ABSPATH' ) and exit;

if(class_exists('WooZoneLiteAdminMenu') != true) {
	class WooZoneLiteAdminMenu {
		
		/*
        * Some required plugin information
        */
        const VERSION = '1.0';

        /*
        * Store some helpers config
        */
		public $the_plugin = null;
		private $the_menu = array();
		private $current_menu = '';
		private $ln = '';
		
		private $menu_depedencies = array();

		static protected $_instance;

        /*
        * Required __construct() function that initalizes the AA-Team Framework
        */
        public function __construct()
        {
        	global $WooZoneLite;
        	$this->the_plugin = $WooZoneLite;
			$this->ln = $this->the_plugin->localizationName;
			
			// update the menu tree
			$this->the_menu_tree();
			
			return $this;
        }

		/**
	    * Singleton pattern
	    *
	    * @return WooZoneLiteDashboard Singleton instance
	    */
	    static public function getInstance()
	    {
	        if (!self::$_instance) {
	            self::$_instance = new self;
	        }

	        return self::$_instance;
	    }
		
		private function the_menu_tree()
		{
			if ( isset($this->the_plugin->cfg['modules']['depedencies']['folder_uri'])
				&& !empty($this->the_plugin->cfg['modules']['depedencies']['folder_uri']) ) {
				$this->menu_depedencies['depedencies'] = array( 
					'title' => __( 'Plugin depedencies', $this->ln ),
					'url' => admin_url("admin.php?page=WooZoneLite"),
					'folder_uri' => $this->the_plugin->cfg['paths']['freamwork_dir_url'],
					'menu_icon' => 'dashboard'
				);
                
                $this->clean_menu();
				return true;
			}

			$this->the_menu['dashboard'] = array( 
				'title' => __( 'Dashboard', $this->ln ),
				'url' => admin_url("admin.php?page=WooZoneLite#!/dashboard"),
				'folder_uri' => $this->the_plugin->cfg['paths']['freamwork_dir_url'],
				'menu_icon' => 'dashboard'
			);
			
			$this->the_menu['configuration'] = array( 
				'title' => __( 'Configuration', $this->ln ),
				'url' => "#!/",
				'folder_uri' => $this->the_plugin->cfg['paths']['freamwork_dir_url'],
				'menu_icon' => 'amazon',
				'submenu' => array(
					'amazon' => array(
						'title' => __( 'Amazon config', $this->ln ),
						'url' => admin_url("admin.php?page=WooZoneLite#!/amazon"),
						'folder_uri' => $this->the_plugin->cfg['modules']['amazon']['folder_uri'],
						'menu_icon' => 'amazon'
					),
				)
			);
			
			$this->the_menu['import'] = array( 
				'title' => __( 'Import Products', $this->ln ),
				'url' => "#!/",
				'folder_uri' => $this->the_plugin->cfg['paths']['freamwork_dir_url'],
				'submenu' => array(
                    
                    'insane_import' => array(
                        'title' => __( 'Insane Import Mode', $this->ln ),
                        'url' => admin_url('admin.php?page=' . $this->the_plugin->alias . "_insane_import"),
                        'folder_uri' => $this->the_plugin->cfg['modules']['insane_import']['folder_uri'],
                        'menu_icon' => 'insane_import',
                        'submenu' => array(
                            'report_Settings' => array(
                                'title' => __( 'Insane Import Settings', $this->ln ),
                                'url' => admin_url('admin.php?page=' . $this->the_plugin->alias . '#!/insane_import'),
                                'menu_icon' => 'sub_menu'
                            ),
                        )
                    ),
                   
				)
			);
			
			$this->the_menu['info'] = array( 
				'title' => __( 'Plugin Status', $this->ln ),
				'url' => "#!/",
				'folder_uri' => $this->the_plugin->cfg['paths']['freamwork_dir_url'],
				'menu_icon' => 'images/pluginstatus.png',
				'submenu' => array(
				
                    'server_status' => array(
                        'title' => __( 'Server Status', $this->ln ),
                        'url' => admin_url("admin.php?page=WooZoneLite_server_status"),
                        'folder_uri' => $this->the_plugin->cfg['modules']['server_status']['folder_uri'],
                        'menu_icon' => 'server_status'
                    ),
				)
			);
			
			$this->the_menu['general'] = array( 
				'title' => __( 'Plugin Settings', $this->ln ),
				'url' => "#!/",
				'folder_uri' => $this->the_plugin->cfg['paths']['freamwork_dir_url'],
				'menu_icon' => '',
				'submenu' => array(
					'modules_manager' => array(
						'title' => __( 'Modules Manager', $this->ln ),
						'url' => admin_url("admin.php?page=WooZoneLite#!/modules_manager"),
						'folder_uri' => $this->the_plugin->cfg['modules']['modules_manager']['folder_uri'],
						'menu_icon' => 'modules'
					),
					
					'setup_backup' => array(
						'title' => __( 'Setup / Backup', $this->ln ),
						'url' => admin_url("admin.php?page=WooZoneLite#!/setup_backup"),
						'folder_uri' => $this->the_plugin->cfg['modules']['setup_backup']['folder_uri'],
						'menu_icon' => 'setup_backup'
					),			
				)
			);
            
            $this->clean_menu();
		}

        public function clean_menu() {
            foreach ($this->the_menu as $key => $value) {
                if( isset($value['submenu']) ){
                    foreach ($value['submenu'] as $kk2 => $vv2) {
                        $kk2orig = $kk2;
                        // fix to support same module multiple times in menu
                        $kk2 = substr( $kk2, 0, (($t = strpos($kk2, '--'))!==false ? $t : strlen($kk2)) );
  
                        if( ($kk2 != 'synchronization_log')
                            && !in_array( $kk2, array_keys($this->the_plugin->cfg['activate_modules'])) ) {
                            unset($this->the_menu["$key"]['submenu']["$kk2orig"]);
                        }
                    }
                }
            }

            foreach ($this->the_menu as $k=>$v) { // menu
                if ( isset($v['submenu']) && empty($v['submenu']) ) {
                    unset($this->the_menu["$k"]);
                }
            }
        }
		
		public function show_menu( $pluginPage='' )
		{
			$plugin_data = WooZoneLite_get_plugin_data();
  			
			$html = array();

			$html[] = '<aside class="' . ( WooZoneLite()->alias ) . '-sidebar">';
			//$html[] = 	'<a href="' . ( admin_url( 'admin.php?page=WooZoneLite' ) ) . '" class="' . ( WooZoneLite()->alias ) . '-title">' . ( WooZoneLite()->pluginName ) . ' <span><i>V</i> ' . ( $plugin_data['version'] ) . '</span></a>';
			$html[] = 	'<a href="' . ( admin_url( 'admin.php?page=WooZoneLite' ) ) . '" class="' . ( WooZoneLite()->alias ) . '-title"><img src="' . (  $this->the_plugin->cfg['paths']['freamwork_dir_url'] . 'images/logo.png' ) . '" /> <span><i>V</i> ' . ( $plugin_data['version'] ) . '</span></a>';
			$html[] = '<div class="' . ( WooZoneLite()->alias ) . '-responsive-menu hide">Menu <i class="fa fa-bars" aria-hidden="true"></i></div>';	
			$html[] = 	'<nav class="' . ( WooZoneLite()->alias ) . '-nav">';
					
			if ( $pluginPage == 'depedencies' ) {
				$menu = $this->menu_depedencies;
				$this->current_menu = array(
					0 => 'depedencies',
					1 => 'depedencies'
				);
			} else {
				$menu = $this->the_menu;
			}

			foreach ($menu as $key => $value) {
				if( $key == 'import' ) {
					//var_dump('<pre>',$value ,'</pre>'); die; 
				} 
				$html[] = '<ul>';
				if( $key != "dashboard" ){
					$html[] = '<li class="' . ( WooZoneLite()->alias ) . '-nav-title">' . ( $value['title'] ) . '</li>';
				}
			
				$html[] = '<li id="' . ( WooZoneLite()->alias ) . '-nav-' . ( $key ) . '" class="' . ( WooZoneLite()->alias ) . '-section-' . ( $key ) . '">';
				
				if( $value['url'] != "#!/" ){

					$html[] = 	'<a href="' . ( $value['url'] ) . '" class="' . ( isset($this->current_menu[0]) && ( $key == $this->current_menu[0] ) ? 'active' : '' ) . '">';
					if( isset($value['menu_icon']) ){
						$html[] = 	'<i class="' . ( WooZoneLite()->alias ) . '-icon-' . ( $value['menu_icon'] ) . '"></i>';
					}

					$html[] = $value['title'] . '</a>';
				}

				if( isset($value['submenu']) ){
					//$html[] = 	'<ul class="' . ( WooZoneLite()->alias ) . '-sub-menu">';
					foreach ($value['submenu'] as $kk2 => $vv2) {

						if( ($kk2 != 'synchronization_log') && isset($this->the_plugin->cfg['activate_modules']) && is_array($this->the_plugin->cfg['activate_modules']) && !in_array( $kk2, array_keys($this->the_plugin->cfg['activate_modules'])) ) continue;

						$html[] = '<li class="' . ( WooZoneLite()->alias ) . '-section-' . ( $kk2 ) . '" id="WooZoneLite-nav-' . ( $kk2 ) . '">';
						
						$html[] = 	'<a href="' . ( $vv2['url'] ) . '" class="' . ( isset($this->current_menu[1]) && $kk2 == $this->current_menu[1] ? 'active' : '' ) . '">';
						if( isset($vv2['menu_icon']) ){
							$html[] = 	'<i class="' . ( WooZoneLite()->alias ) . '-icon-' . ( $vv2['menu_icon'] ) . '"></i>';
						}
						$html[] = $vv2['title'] . '</a>'; 
						
						if( isset($vv2['submenu']) ){
							$html[] = 	'<ul class="' . ( WooZoneLite()->alias ) . '-sub-sub-menu">';
							foreach ($vv2['submenu'] as $kk3 => $vv3) {
								$html[] = '<li id="' . ( WooZoneLite()->alias ) . '-sub-sub-nav-' . ( $kk3 ) . '">';
								$html[] = 	'<a href="' . ( $vv3['url'] ) . '">';
								if( isset($vv3['menu_icon']) ){
									$html[] = 	'<i class="' . ( WooZoneLite()->alias ) . '-icon-' . ( $vv3['menu_icon'] ) . '"></i>';
								}
								$html[] = 	$vv3['title'] . '</a>';
								$html[] = '</li>';
							}
							$html[] = 	'</ul>';
						}
						$html[] = '</li>';
					}
					//$html[] = 	'</ul>';
				}
				$html[] = '</li>';
				$html[] = '</ul>';
			}

			$html[] = 	'</nav>';
    		$html[] = '</aside>';



			echo implode("\n", $html);
		}

		public function make_active( $section='' )
		{
			$this->current_menu = explode("|", $section);
			return $this;
		}
	}
}