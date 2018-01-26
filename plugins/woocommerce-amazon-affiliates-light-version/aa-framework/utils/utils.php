<?php
/*
* Define class WooZoneLite_Utils
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
!defined('ABSPATH') and exit;
if (class_exists('WooZoneLite_Utils') != true) {
    class WooZoneLite_Utils
    {
        /*
        * Some required plugin information
        */
        const VERSION = '1.0';

        static protected $_instance;

        /*
        * Store some helpers config
        */
        public $the_plugin = null;
        
    
        /*
        * Required __construct() function that initalizes the AA-Team Framework
        */
        public function __construct( $parent )
        {
            $this->the_plugin = $parent;
        }
        
        /**
        * Singleton pattern
        *
        * @return Singleton instance
        */
        static public function getInstance( $parent )
        {
            if (!self::$_instance) {
                self::$_instance = new self($parent);
            }
            
            return self::$_instance;
        }
        

        /**
         * Cache
         */
        //use cache to limits search accesses!
        public function needNewCache($filename, $cache_life) {
        
            // cache file needs refresh!
            if (($statCache = $this->isCacheRefresh($filename, $cache_life))===true || $statCache===0) {
                return true;
            }
            return false;
        }
        
        // verify cache refresh is necessary!
        public function isCacheRefresh($filename, $cache_life) {
            // cache file exists!
            if ($this->verifyFileExists($filename)) {
                $verify_time = time(); // in seconds
                $file_time = filemtime($filename); // in seconds
                $mins_diff = ($verify_time - $file_time) / 60; // in minutes
                if($mins_diff > $cache_life){
                    // new cache is necessary!
                    return true;
                }
                // cache is empty! => new cache is necessary!
                if (filesize($filename)<=0) return 0;
    
                // NO new cache!
                return false;
            }
            // cache file NOT exists! => new cache is necessary!
            return 0;
        }
    
        // write content to local cached file
        public function writeCacheFile($filename, $content, $use_lock=false) {
            $folder = dirname($filename);
            if ( empty($folder) || $folder == '.' || $folder == '/' ) return false;
  
            // cache folder!
            if ( !$this->makedir($folder) ) return false;
            if ( !is_writable($folder) ) return false;

            $has_wrote = false;
            if ( $use_lock ) {

                $fp = @fopen($filename, "wb");
                if ( @flock($fp, LOCK_EX, $wouldblock) ) { // do an exclusive lock
                    $has_wrote = @fwrite($fp, $content);
                    @flock($fp, LOCK_UN, $wouldblock); // release the lock
                }
                @fclose( $fp );
            } else {

                $wp_filesystem = $this->the_plugin->wp_filesystem;
                $has_wrote = $wp_filesystem->put_contents( $filename, $content );
                if ( !$has_wrote ) {
                    $has_wrote = file_put_contents($filename, $content);
                }
            }
            return $has_wrote;
        }
    
        // cache file
        public function getCacheFile($filename) {
            if ($this->verifyFileExists($filename)) {
                
                $wp_filesystem = $this->the_plugin->wp_filesystem;
                $has_wrote = $wp_filesystem->get_contents( $filename );
                if ( !$has_wrote ) {
                    $has_wrote = file_get_contents($filename);
                }
                $content = $has_wrote;
                return $content;
            }
            return false;
        }
        
        // delete cache
        public function deleteCache($filename) {
            if ($this->verifyFileExists($filename)) {
                return unlink($filename);
            }
            return false;
        }
    
        // verify if file exists!
        public function verifyFileExists($file, $type='file') {
            clearstatcache();
            if ($type=='file') {
                if (!file_exists($file) || !is_file($file) || !is_readable($file)) {
                    return false;
                }
                return true;
            } else if ($type=='folder') {
                if (!is_dir($file) || !is_readable($file)) {
                    return false;
                }
                return true;
            }
            // invalid type
            return 0;
        }
    
        // make a folder!
        public function makedir($fullpath) {
            clearstatcache();
            if(file_exists($fullpath) && is_dir($fullpath) && is_readable($fullpath)) {
                return true;
            }else{
                $stat1 = @mkdir($fullpath, 0777, true); // recursive
                $stat2 = @chmod($fullpath, 0777);
                if (!empty($stat1) && !empty($stat2))
                    return true;
            }
            return false;
        }
        
        // get file name/ dot indicate if a .dot will be put in front of image extension, default is not
        public function fileName($fullname)
        {
            $return = substr($fullname, 0, strrpos($fullname, "."));
            return $return;
        }
    
        // get file extension
        public function fileExtension($fullname, $dot=false)
        {
            $return = "";;
            if( $dot == true ) $return .= ".";
            $return .= substr(strrchr($fullname, "."), 1);
            return $return;
        }
    
        public function append_contents( $filename, $contents, $mode = '0777' ) {
            $folder = dirname($filename);
            if ( empty($folder) || $folder == '.' || $folder == '/' ) return false;
  
            // cache folder!
            if ( !$this->makedir($folder) ) return false;
            if ( !is_writable($folder) ) return false;

            if ( !($fp = @fopen($filename, 'ab')) ) {
                return false;
            }
            $stat1 = @fwrite($fp, $contents);
            @fclose($fp);
            $stat2 = @chmod($filename, $mode);
            if (!empty($stat1) && !empty($stat2))
                return true;
            return false;
        }
        
        public function put_contents_gzip( $filename, $contents ) {
            if ( !function_exists('gzcompress') ) return false;
                
            //$gzip = @gzopen($filename, "w9");
            //if ( $gzip ){
            //    gzwrite($gzip, $contents);
            //    gzclose($gzip);
            //}
            
            $gzip = @fopen( $filename, 'w' );
            if ( $gzip ) {
                //$contents = @gzcompress($contents, 9); //zlib (http deflate)
                $contents = @gzencode($contents, 9); //gzip
                //$contents = @gzdeflate($contents, 1); //raw deflate encoding
                @fwrite($gzip, $contents);
                @fclose($gzip);
            }
    
            return true;
        }

        public function get_folder_files_recursive($path) {
        	if ( !$this->verifyFileExists($path, 'folder') ) return 0;

            $size = 0;
            $ignore = array('.', '..', 'cgi-bin', '.DS_Store');
            $files = scandir($path);
  
            foreach ($files as $t) {
                if (in_array($t, $ignore)) continue;
                if (is_dir(rtrim($path, '/') . '/' . $t)) {
                    $size += $this->get_folder_files_recursive(rtrim($path, '/') . '/' . $t);
                } else {
                    $size++;
                }   
            }
            return $size;
        }
        
        public function createFile($filename, $content='') {
            $has_wrote = false;
            if ( $fp = @fopen($filename,'wb') ) {
                $has_wrote = @fwrite($fp, $content);
                @fclose($fp);
            }
            return $has_wrote;
        }

		public function filesize($path) {
			$size = filesize($path);
			$units = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
			$power = $size > 0 ? floor(log($size, 1024)) : 0;
			return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
		}


		// Replace last occurance of a String
		public function str_replace_last( $search , $replace , $str ) {
		    if ( ( $pos = strrpos( $str , $search ) ) !== false ) {
		        $search_length  = strlen( $search );
		        $str = substr_replace( $str, $replace, $pos, $search_length );
		    }
		    return $str;
		}
		
        /**
         * Pretty-prints the difference in two times.
         *
         * @param time $older_date
         * @param time $newer_date
         * @return string The pretty time_since value
         * @original link http://binarybonsai.com/code/timesince.txt
         */
        public function time_since( $older_date, $newer_date ) {
            return $this->interval( $newer_date - $older_date );
        }
        public function interval( $since ) {
            // array of time period chunks
            $chunks = array(
                array(60 * 60 * 24 * 365 , _n_noop('%s year', '%s years', $this->the_plugin->localizationName)),
                array(60 * 60 * 24 * 30 , _n_noop('%s month', '%s months', $this->the_plugin->localizationName)),
                array(60 * 60 * 24 * 7, _n_noop('%s week', '%s weeks', $this->the_plugin->localizationName)),
                array(60 * 60 * 24 , _n_noop('%s day', '%s days', $this->the_plugin->localizationName)),
                array(60 * 60 , _n_noop('%s hour', '%s hours', $this->the_plugin->localizationName)),
                array(60 , _n_noop('%s minute', '%s minutes', $this->the_plugin->localizationName)),
                array( 1 , _n_noop('%s second', '%s seconds', $this->the_plugin->localizationName)),
            );
    
    
            if( $since <= 0 ) {
                return __('now', $this->the_plugin->localizationName);
            }
    
            // we only want to output two chunks of time here, eg:
            // x years, xx months
            // x days, xx hours
            // so there's only two bits of calculation below:
    
            // step one: the first chunk
            for ($i = 0, $j = count($chunks); $i < $j; $i++)
                {
                $seconds = $chunks[$i][0];
                $name = $chunks[$i][1];
    
                // finding the biggest chunk (if the chunk fits, break)
                if (($count = floor($since / $seconds)) != 0)
                    {
                    break;
                    }
                }
    
            // set output var
            $output = sprintf(_n($name[0], $name[1], $count, $this->the_plugin->localizationName), $count);
    
            // step two: the second chunk
            if ($i + 1 < $j)
                {
                $seconds2 = $chunks[$i + 1][0];
                $name2 = $chunks[$i + 1][1];
    
                if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0)
                    {
                    // add to output var
                    $output .= ' '.sprintf(_n($name2[0], $name2[1], $count2, $this->the_plugin->localizationName), $count2);
                    }
                }
    
            return $output;
        }
	


		/**
		 * List available image sizes with width and height following
		 */		
		/**
		 * Get size information for all currently-registered image sizes.
		 *
		 * @global $_wp_additional_image_sizes
		 * @uses   get_intermediate_image_sizes()
		 * @return array $sizes Data for all currently-registered image sizes.
		 */
		public function get_image_sizes() {
			global $_wp_additional_image_sizes;
		
			$sizes = array();
		
			foreach ( get_intermediate_image_sizes() as $_size ) {
				if ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) ) {
					$sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
					$sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
					$sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
				} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
					$sizes[ $_size ] = array(
						'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
						'height' => $_wp_additional_image_sizes[ $_size ]['height'],
						'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
					);
				}
			}
		
			return $sizes;
		}
		
		/**
		 * Get size information for a specific image size.
		 *
		 * @uses   get_image_sizes()
		 * @param  string $size The image size for which to retrieve data.
		 * @return bool|array $size Size data about an image size or false if the size doesn't exist.
		 */
		public function get_image_size( $size ) {
			$sizes = get_image_sizes();
		
			if ( isset( $sizes[ $size ] ) ) {
				return $sizes[ $size ];
			}
		
			return false;
		}
		
		/**
		 * Get the width of a specific image size.
		 *
		 * @uses   get_image_size()
		 * @param  string $size The image size for which to retrieve data.
		 * @return bool|string $size Width of an image size or false if the size doesn't exist.
		 */
		public function get_image_width( $size ) {
			if ( ! $size = get_image_size( $size ) ) {
				return false;
			}
		
			if ( isset( $size['width'] ) ) {
				return $size['width'];
			}
		
			return false;
		}
		
		/**
		 * Get the height of a specific image size.
		 *
		 * @uses   get_image_size()
		 * @param  string $size The image size for which to retrieve data.
		 * @return bool|string $size Height of an image size or false if the size doesn't exist.
		 */
		public function get_image_height( $size ) {
			if ( ! $size = get_image_size( $size ) ) {
				return false;
			}
		
			if ( isset( $size['height'] ) ) {
				return $size['height'];
			}
		
			return false;
		}
	}
}