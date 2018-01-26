<?php
/*
 * Define class Cache Amazon Images from CDN
 */
!defined('ABSPATH') and exit;

if (class_exists('WooZoneLiteCacheit') != true) {
    class WooZoneLiteCacheit // main class
    {
        const VERSION = '1.0';

		static protected $_instance;

        public $the_plugin 		= null;
		public $amzHelper 	= null;
		public $is_admin		= null;
		public $amz_settings = array();
		public $alias;
		public $localizationName;

		// cached content		
		public $cached = array(
			//'nonpersistent'		=> array(), // local class variable - available till next page refresh
			'session'				=> array(), // session - available till browser is closed
			'wpoption'				=> array(), // saved in wp_options
			'file'						=> array(), // saved on hdd
		);
		public $full		= array( // full value, not just our current cache
			'session'				=> array(),
			'wpoption'				=> array(),
			'file'						=> array(),
		);

 		// cache settings
		public $settings = array();
		public $cache_folder = array( // only when you use "file"
			'path'		=> '', // full path for the folder
			'url'			=> '', // url to the folder
			'relpath'		=> '', // relative path for the folder
			'filename'	=> '', // filename only (with extension too)
			'filepath'	=> '', // full filepath ( path + filename )
		);
		public $logstatus = array();


        /*
        * Required __construct() function that initalizes the AA-Team Framework
        */
		public function __construct( $parent, $pms=array() )
        {
            $this->the_plugin = $parent;
			$this->amzHelper = $this->the_plugin->amzHelper;
			$this->is_admin = $this->the_plugin->is_admin;
			$this->amz_settings = $this->the_plugin->amz_settings;
			$this->alias = $this->the_plugin->alias;
			$this->localizationName = $this->the_plugin->localizationName;

			// init this cache!
			$this->init_cache( $pms );
        }
        
        /**
        * Singleton pattern
        *
        * @return Singleton instance
        */
        static public function getInstance( $parent, $pms )
        {
            if (!self::$_instance) {
                self::$_instance = new self($parent, $pms);
            }
            return self::$_instance;
        }
        static public function getInstanceMultiple( $parent, $pms )
        {
        	static $_instances = array();
			$calledClass = get_called_class();

			if ( ! isset($_instances[$calledClass]) ) {
				$_instances[$calledClass] = new $calledClass( $parent, $pms );
			}
			//var_dump('<pre>',array_keys($_instances),'</pre>');
			return $_instances[$calledClass];
        }


		/**
		 * PUBLIC
		 */

		/**
		 * init cache params
		 */
		public function init_cache( $pms=array() ) {
        	$def = array(
				// if you want to load the cache here or you'll do it yourself
        		'do_load'					=> true,
				// which cache levels (array order<=> their priority order) you want to use?
        		'levels_used'				=> array('session', 'wpoption'),
				// cache main key by which it's identified - first & seconds keys are mandatory
				// same first key of this array => same option for wpoption type | same file for file type
				// second, third ... key of this array => multi-levels arrays inside cached option | file
        		'cache_keymain'		=> array('WooZoneLiteCached'),
				// relative to upload dir (used only if you use "file" level)
				'cache_folder'			=> 'woozonelite-cached',
			);
			foreach ($def as $key => $val) {
				if ( ! isset($pms["$key"]) ) {
					$pms["$key"] = $def["$key"];
				}
			}
			extract( $pms );
			$this->settings = $pms;

			// load cache
			if ( $do_load ) {
				$this->load_cache( $pms );
			}
		}
		 
		/**
		 * load cache here from where it's saved
		 */
		public function load_cache( $pms=array() ) {
			$pms = array_replace_recursive(array(
			), $pms);
			extract( $pms );
  
			foreach ($this->settings['levels_used'] as $level) {
				$key = "load_cache_$level";
				if ( method_exists($this, $key) ) {
					$this->$key();

					if ( isset($this->logstatus["file"]) && ! empty($this->logstatus["file"]) ) {
						//var_dump('<pre>', $this->logstatus, '</pre>');
						//echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
					}
				}
			}
		}

		/**
		 * debug cache
		 */
		public function debug_cache( $pms=array() ) {
			$pms = array_replace_recursive(array(
			), $pms);
			extract( $pms );

			foreach ($this->cached as $keymain => $cached) {
				var_dump('<pre>', get_called_class(), $keymain, array_keys($cached), '</pre>');
			}
		}

		/**
		 * Return cache
		 */
		public function return_cache( $pms=array() ) {
			$pms = array_replace_recursive(array(
			), $pms);
			extract( $pms );

			$cached = array();
			foreach ($this->settings['levels_used'] as $level) {
				$key = "load_cache_$level";
				if ( method_exists($this, $key) && isset($this->cached["$level"]) ) {
					$cached["$level"] = $this->cached["$level"];
				}
			}
			return $cached;
		}

		/**
		 * save cache
		 */
		public function save_cache( $pms=array() ) {
			$pms = array_replace_recursive(array(
			), $pms);
			extract( $pms );

			foreach ($this->settings['levels_used'] as $level) {
				$key = "save_cache_$level";
				if ( method_exists($this, $key) ) {
					$this->$key();

					if ( isset($this->logstatus["file"]) && ! empty($this->logstatus["file"]) ) {
						//var_dump('<pre>', $this->logstatus, '</pre>');
						//echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
					}
				}
			}
		}
		
		/**
		 * empty cache - fully delete it
		 */
		public function empty_cache( $pms=array() ) {
			$pms = array_replace_recursive(array(
			), $pms);
			extract( $pms );

			$save = array();

			foreach ($this->settings['levels_used'] as $level) {
				$this->cached["$level"] = $save;
			}

			$this->save_cache( $pms );
		}

		/**
		 * add / update row to cache
		 * if not exists, row will be added, else it will be overwritten
		 */
		public function add_row( $key, $content, $pms=array() ) {
			$pms = array_replace_recursive(array(
				'overwrite'			=> false
			), $pms);
			extract( $pms );

			foreach ($this->settings['levels_used'] as $level) {
				if ( ! isset($this->cached["$level"]["$key"]) ) {
					$this->cached["$level"]["$key"] = $content;
				}
				else {
					if ( $overwrite || ! is_array($this->cached["$level"]["$key"]) || ! is_array($content) ) {
						$this->cached["$level"]["$key"] = $content;
					}
					else {
						$this->cached["$level"]["$key"] = array_replace_recursive($this->cached["$level"]["$key"], $content);
					}
				}
			} // end foreach
		}

		/**
		 * get row from cache
		 */
		public function get_row( $key, $pms=array() ) {
			$pms = array_replace_recursive(array(
			), $pms);
			extract( $pms );

			foreach ($this->settings['levels_used'] as $level) {
				if ( isset($this->cached["$level"]["$key"]) ) {
					return $this->cached["$level"]["$key"];
				}
			}
			return array();
		}
		
		/**
		 * remove row from cache
		 */
		public function del_row( $key, $pms=array() ) {
			$pms = array_replace_recursive(array(
			), $pms);
			extract( $pms );

			foreach ($this->settings['levels_used'] as $level) {
				if ( isset($this->cached["$level"]["$key"]) ) {
					unset( $this->cached["$level"]["$key"] );
				}
			}
		}


		/**
		 * nonpersistent - made only for compatibility
		 */
		protected function load_cache_nonpersistent() {
			return array();			
		}

		protected function save_cache_nonpersistent() {
			return true;
		}

		protected function getfull_cache_nonpersistent( $keymain ) {
			return array();
		}


		/**
		 * session
		 */
		protected function load_cache_session() {
			if ( ! is_array($this->settings['cache_keymain']) || ! isset($this->settings['cache_keymain'][0]) ) {
				$this->logstatus["session"] = __('cache key main: is invalid.', $this->localizationName);
				return false;
			}
			$keymain = $this->settings['cache_keymain'][0];

			// get cache
			$level = $this->getfull_cache_session( $keymain );

			if ( empty($level) ) {
				$this->logstatus["session"] = __('cache: is empty.', $this->localizationName);
				return array();
			}

			$keys = $this->settings['cache_keymain'];
			foreach ($keys as $keymain) {
				$level = isset($level["$keymain"]) ? $level["$keymain"] : array();
				if ( empty($level) ) {
					break 1;
				}
			}

			if ( ! empty($level) ) {
				$level = maybe_unserialize( $level );
			}
			$this->cached['session'] = $level;
			return $level;
		}

		protected function save_cache_session() {
			if ( ! is_array($this->settings['cache_keymain']) || ! isset($this->settings['cache_keymain'][0]) ) {
				return false;
			}
			$keymain = $this->settings['cache_keymain'][0];

			$level = $this->cached['session'];
			$level = maybe_serialize( $level );

			$keys = $this->settings['cache_keymain'];
			$new = $this->build_level_for_save( $level, array_values($keys) );

			// get cache
			$full = $this->getfull_cache_session( $keymain );

			$tosave = array_replace_recursive($full, $new);
			//$tosave = maybe_serialize( $tosave ); // no need to do this for session

			$_SESSION = $tosave;
			return true;
		}
		
		protected function getfull_cache_session( $keymain ) {
			$level = isset($_SESSION) ? $_SESSION : array();
			//$level = maybe_unserialize( $level ); // no need to do this for session
			$this->full['session'] = $level;
			return $level;
		}


		/**
		 * wpoption
		 */
		protected function load_cache_wpoption() {
			if ( ! is_array($this->settings['cache_keymain']) || ! isset($this->settings['cache_keymain'][0]) ) {
				$this->logstatus["wpoption"] = __('cache key main: is invalid.', $this->localizationName);
				return array();
			}
			$keymain = $this->settings['cache_keymain'][0];

			// get cache
			$level = $this->getfull_cache_wpoption( $keymain );

			if ( empty($level) ) {
				$this->logstatus["wpoption"] = __('cache: is empty.', $this->localizationName);
				return array();
			}

			$keys = $this->settings['cache_keymain'];
			unset($keys[0]);
			if ( count($keys) >= 1 ) {
				foreach ($keys as $keymain) {
					$level = isset($level["$keymain"]) ? $level["$keymain"] : array();
					if ( empty($level) ) {
						break 1;
					}
				}
			}

			if ( ! empty($level) ) {
				$level = maybe_unserialize( $level );
			}
			$this->cached['wpoption'] = $level;
			return $level;
		}

		protected function save_cache_wpoption() {
			if ( ! is_array($this->settings['cache_keymain']) || ! isset($this->settings['cache_keymain'][0]) ) {
				return false;
			}
			$keymain = $this->settings['cache_keymain'][0];

			$level = $this->cached['wpoption'];
			$level = maybe_serialize( $level );

			$keys = $this->settings['cache_keymain'];
			unset($keys[0]);
			$new = $this->build_level_for_save( $level, array_values($keys) );

			// get cache
			$full = $this->getfull_cache_wpoption( $keymain );

			$tosave = array_replace_recursive($full, $new);
			//$tosave = maybe_serialize( $tosave ); // update_option deals with this

			$writeStat = update_option($keymain, $tosave);
			return $writeStat;
		}
		
		protected function getfull_cache_wpoption( $keymain ) {
			$level = get_option($keymain, array());
			//$level = maybe_unserialize( $level ); // get_option deals with this
			$level = is_array($level) ? $level : array();
			$this->full['wpoption'] = $level;
			return $level;
		}


		/**
		 * file
		 */
		protected function load_cache_file() {
			if ( empty($this->settings['cache_folder']) ) {
				$this->logstatus["file"] = __('create cache folder: invalid folder path.', $this->localizationName);
				return array();
			}
			if ( ! is_array($this->settings['cache_keymain']) || ! isset($this->settings['cache_keymain'][0]) ) {
				$this->logstatus["file"] = __('cache key main: is invalid.', $this->localizationName);
				return array();
			}
			$keymain = $this->settings['cache_keymain'][0];

			// create folder
			$cache_folder = $this->settings['cache_folder'];
			$create_stat = $this->build_cache_folder( $cache_folder );
			if ( ! $create_stat ) {
				$this->logstatus["file"] = __('create cache folder: could not be created or is not writable.', $this->localizationName);
				return array();
			}

			// get cache
			$filename = $this->filter_filename( $keymain . '.txt' );
			$this->cache_folder['filename'] = $filename;
			$this->cache_folder['filepath'] = $this->cache_folder['path'] . $filename;

			$level = $this->getfull_cache_file( $keymain );

			if ( empty($level) ) {
				$this->logstatus["file"] = __('cache: is empty.', $this->localizationName);
				return array();
			}

			$keys = $this->settings['cache_keymain'];
			unset($keys[0]);
			if ( count($keys) >= 1 ) {
				foreach ($keys as $keymain) {
					$level = isset($level["$keymain"]) ? $level["$keymain"] : array();
					if ( empty($level) ) {
						break 1;
					}
				}
			}

			if ( ! empty($level) ) {
				$level = maybe_unserialize( $level );
			}
			$this->cached['file'] = $level;
			return $level;
		}

		protected function save_cache_file() {
			if ( ! is_array($this->settings['cache_keymain']) || ! isset($this->settings['cache_keymain'][0]) ) {
				return false;
			}
			$keymain = $this->settings['cache_keymain'][0];

			$level = $this->cached['file'];
			$level = maybe_serialize( $level );

			$keys = $this->settings['cache_keymain'];
			unset($keys[0]);
			$new = $this->build_level_for_save( $level, array_values($keys) );
			
			// get cache
			$full = $this->getfull_cache_file( $keymain );

			$tosave = array_replace_recursive($full, $new);
			$tosave = maybe_serialize( $tosave );

			$writeStat = $this->u()->writeCacheFile( $this->cache_folder['filepath'], $tosave );
			return $writeStat;
		}
		
		protected function getfull_cache_file( $keymain ) {
			$level = $this->u()->getCacheFile( $this->cache_folder['filepath'] );
			$level = maybe_unserialize( $level );
			$level = is_array($level) ? $level : array();
			$this->full['file'] = $level;
			return $level;
		}


		/**
		 * UTILS
		 */
		protected function u() {
			return $this->the_plugin->u;
		}

		// relative path to the folder: path can have multiple sublevels like /folder1/folder2/folder3/
        protected function build_cache_folder( $folder_path='' ) {

			// make sure upload dirs exist and set file path and uri
			$upload_dir = wp_upload_dir();
			if ( ! $this->u()->verifyFileExists($upload_dir['basedir'], 'folder') ) {
				wp_mkdir_p( $upload_dir['basedir'] );   
			}

			$cache_folder = '/' . $this->trailingslash($folder_path) . '/';
			$this->cache_folder = array_replace_recursive($this->cache_folder, array(
				'relpath'		=> '/wp-content/uploads' . $cache_folder,
				'path'		=> $upload_dir['basedir'] . $cache_folder,
				'url'			=> $upload_dir['baseurl'] . $cache_folder,
			));

			if ( ! $this->u()->verifyFileExists($this->cache_folder['path'], 'folder') ) {
				wp_mkdir_p( $this->cache_folder['path'] );
			}

			if ( $this->u()->verifyFileExists($this->cache_folder['path'], 'folder')
				&& is_writable($this->cache_folder['path'])
			) {
				return true;
			}
			return false;
		}

		// remove trailing slash if one exists: at the begining or end
		protected function trailingslash( $str ) {
			$str = preg_replace('/(^\/)|(\/$)/iu', '', $str);
			return $str;
		}
		
		protected function filter_filename( $str ) {
			$str = strtolower($str);
			$str = sanitize_file_name($str);
			return $str;
		}
		
		protected function build_level_for_save( $level, $keys=array() ) {
			$new = $level;
			$len = count($keys);
			for ($c = $len-1; $c >= 0; $c--) {
				$current_key = $keys["$c"];
				$new = array("$current_key" => $new);
			}
			return $new;
		}
	} // end main class
}


// cache images /url
if (class_exists('WooZoneLiteCacheImagesUrl') != true) {
    class WooZoneLiteCacheImagesUrl extends WooZoneLiteCacheit
    {
        /*
        * Required __construct() function that initalizes the AA-Team Framework
        */
        public function __construct( $parent, $pms=array() )
        {
			parent::__construct( $parent, $pms );
        }
        
        /**
        * Singleton pattern
        *
        * @return Singleton instance
        */
        static public function getInstance( $parent, $pms )
        {
            if (!self::$_instance) {
                self::$_instance = new self($parent, $pms);
            }
            return self::$_instance;
        }
	}
}


// cache images /sources
if (class_exists('WooZoneLiteCacheImagesSources') != true) {
    class WooZoneLiteCacheImagesSources extends WooZoneLiteCacheit
    {
        /*
        * Required __construct() function that initalizes the AA-Team Framework
        */
        public function __construct( $parent, $pms=array() )
        {
			parent::__construct( $parent, $pms );
        }
        
        /**
        * Singleton pattern
        *
        * @return Singleton instance
        */
        static public function getInstance( $parent, $pms )
        {
            if (!self::$_instance) {
                self::$_instance = new self($parent, $pms);
            }
            return self::$_instance;
        }
	}
}


// cache products are amazon valid
if (class_exists('WooZoneLiteCacheAmzValid') != true) {
    class WooZoneLiteCacheAmzValid extends WooZoneLiteCacheit
    {
        /*
        * Required __construct() function that initalizes the AA-Team Framework
        */
        public function __construct( $parent, $pms=array() )
        {
			parent::__construct( $parent, $pms );
        }
        
        /**
        * Singleton pattern
        *
        * @return Singleton instance
        */
        static public function getInstance( $parent, $pms )
        {
            if (!self::$_instance) {
                self::$_instance = new self($parent, $pms);
            }
            return self::$_instance;
        }
	}
}