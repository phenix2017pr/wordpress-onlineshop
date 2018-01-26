<?php 
if (class_exists('WooZoneLiteSpinner') != true) {
    class WooZoneLiteSpinner
    {
    	/*
        * Some required plugin information
        */
        const VERSION = '1.0';

        /*
        * Store some helpers config
        */
		public $the_plugin = null;

		static protected $_instance;
		
		// setup the defaults settings
		private $settings = array(
		 	/* default is 0, all strings */
			'replacement_number' 	=> 10,
			
			/* languages with treasures files */
			 // fixed de = german now, not dutch (2015, 23 november)
			'available_language' 	=> array( 'en', 'uk', 'de', 'fr', 'ca', 'it', 'es', 'mx', 'br' ),
			'same_language'			=> array(
				'uk'		=> 'en',
				'ca'		=> 'en',
				'mx'		=> 'es',
				'br'		=> 'po',
			),
			'language' 				=> 'en',
		);

		
		public $wp_filesystem = null;
		
		public $content = null;
		private $has_content = false;
		private $synonyms_str = null;


		/*
        * Required __construct() function that initalizes the AA-Team Framework
        */
        public function __construct()
        {
        	// load WP_Filesystem 
			include_once ABSPATH . 'wp-admin/includes/file.php';
		   	WP_Filesystem();
			global $wp_filesystem;
			$this->wp_filesystem = $wp_filesystem;
        }
		
		/**
	    * Setup synonyms language - Setter method
	    *
	    * @return current object
	    */
		public function set_syn_language( $new_lang='' )
		{
			if( ($new_lang != $this->settings['language']) && in_array( $new_lang, $this->settings['available_language'] ) ) {
				$this->settings['language'] = $new_lang;
				if ( isset($this->settings['same_language']["$new_lang"]) ) {
					$this->settings['language'] = $this->settings['same_language']["$new_lang"];
				}
			}
			if ( empty($this->settings['language']) )
				$this->settings['language'] = 'en';
			return $this;
		}


		/**
	    * Setup replacements number - Setter method
	    *
	    * @return current object
	    */
		public function set_replacements_number( $nr=0 )
		{
			$this->settings['replacement_number'] = (int) $nr;
			return $this;
		}
		
		/**
	    * Load content - Setter method
	    *
	    * @return current object
	    */
		public function load_content( $content )
		{
			if( trim($content) != "" ){
				$this->has_content = true;
				$this->content = $content;
			}
			
			return $this;
		}
		
		/**
	    * Spin content
	    *
	    * @return array (new | old content)
	    */
		public function spin_content()
		{
			$return = array(
				'old_content' => $this->content
			);

			if( $this->has_content === true ){
				$this->load_synonyms();

				$doc = WooZoneLitephpQuery::newDocument( $this->content );
				$ignores_founds = array();

				// replace A html tags
				foreach ($doc->find("a") as $elm) {
					$html_elm = trim(WooZoneLitepq($elm)->htmlOuter());
					$html_elm = str_replace('"', "'", $html_elm);
					 
					$this->content = str_replace( 
						$html_elm, 
						md5($html_elm), 
						$this->content 
					);
					
					$ignores_founds[ md5($html_elm) ] = $html_elm;
				}
				
				// replace the shortcodes
				preg_match_all("/\[.*?\]/s", $this->content, $shortcode_matches );
				if( count($shortcode_matches[0]) > 0 ){
					 
					foreach ( $shortcode_matches[0] as $shortcode ) {
						$this->content = str_replace( 
							$shortcode, 
							md5($shortcode), 
							$this->content 
						);
						
						$ignores_founds[ md5($shortcode) ] = $shortcode;
					}
				}
				
				// replace the html tags
				preg_match_all("/<[^<>]+>/is", $this->content, $shortcode_matches );
				if( count($shortcode_matches[0]) > 0 ){
					 
					foreach ( $shortcode_matches[0] as $shortcode ) {
						$this->content = str_replace( 
							$shortcode, 
							md5($shortcode), 
							$this->content 
						);
						
						$ignores_founds[ md5($shortcode) ] = $shortcode;
					}
				}
 
				// replace the numeric values!
				@preg_match_all("/(?<=\s|\.|,)+\d+(?=\s|\.|,)+/im", $this->content, $numeric_matches );
				if( count($numeric_matches[0]) > 0 ){

					$x = 0;
					foreach ( $numeric_matches[0] as $numeric ) {
						$__key = '<<xx'.$x.'xx>>';
						$this->content = preg_replace("/(?<=\s|\.|,)+(\d+)(?=\s|\.|,)+/im", "$__key", $this->content, 1);
  
						$ignores_founds[ "$__key" ] = $numeric;
						$x++;
					}
				}
  
				// explode the synonyms from string 
				$lines = explode("\n", $this->synonyms_str);
				$synonyms_founds = array();
				if( count($lines) > 0 ){
					foreach ($lines as $line) {
						
						if( $this->settings['replacement_number'] > 0 ){
							if( count($synonyms_founds) >= $this->settings['replacement_number'] ){
								break;
							}
						}
						
						$line_synonyms = @array_map("trim", explode("|", $line)); 
						if( count($line_synonyms) > 0 ){
							foreach ($line_synonyms as $_word ) {
								$word = str_replace( '/', '\/', $_word );
								
								if( trim($word) == "" )
									continue;
								
								if( preg_match( '/\b'. $word .'\b/u', $this->content ) ) {
									$synonyms_founds[ md5($word) ]= $this->restruct( $word, $line_synonyms );
									
									$this->content = preg_replace( '/\b' . ( $word ) . '\b/u', md5($word), $this->content );
								} 
								
								if( preg_match( '/\b'. ucfirst( $word ) .'\b/u', $this->content ) ) {
									$synonyms_founds[ md5($word) ]= $this->restruct( ucfirst( $word ), $line_synonyms, true );
									$this->content = preg_replace( '/\b'. ucfirst( $word ) .'\b/u', md5($word), $this->content );
								}
							}
						}
					}

					// roll-back the phrase
					if( count($synonyms_founds) > 0 ){
						foreach ( $synonyms_founds as $found_key => $found_val ){
							$this->content = str_replace( $found_key, '{' . ( $found_val ) . '}', $this->content );
						}
					}
  
					if( count($ignores_founds) > 0 ){
						foreach ($ignores_founds as $found_key => $found_val ) {
							$this->content = str_replace( $found_key, $found_val, $this->content );
						} 
					}
				}
				
				$return['spinned_content'] = $this->content;
				
				$finded_replacements = array();
				if( count($synonyms_founds) > 0 ){
						foreach ( $synonyms_founds as $found_key => $found_val ){
							$original = explode("|", $found_val);
							if( isset($original[0]) ){
								$finded_replacements[] = $original[0]; 
							}
						}
					}
				
				$return['finded_replacements'] = $finded_replacements;
			}

			return $return; 
		}
		
		public function reorder_synonyms( $rule='random' )
		{
			preg_match_all( '/{(.*)}/sU', $this->content, $matches );
			
			$reorder_content = $this->content;
			if( count($matches) > 0 ){
			    foreach ( $matches[0] as $k => $v )
			    {
			        $string = $matches[1][$k];
			        $new = explode( '|', $string );
					
					if( count($new) > 0 ){
						$original_word = $new[0];
						unset($new[0]);
					}
					
					if( $rule == 'random' ){
						shuffle( $new );
					}
					
					// add the original word at the end
					$new[] = $original_word;
					
					$reorder_content = str_replace( $string, implode( "|", $new ), $reorder_content );
			    }
			} 
			 
			return $reorder_content;
		}
		
		public function get_fresh_content( $content='' )
		{
			preg_match_all( '/{(.*)}/sU', $content, $matches );
			if( count($matches) > 0 ){
			    foreach ( $matches[0] as $k => $v )
			    {
			        $string = $matches[1][$k];
			        $new = explode( '|', $string );
					$new = $new[0];
					
			        $content = str_replace( $string, $new, $content);
					$content = str_replace( "{" . ( $new ) . "}", $new, $content );
			    }
			}
	
			return $content;
		}

		private function restruct( $word='', $line_synonyms, $uppercase=false )
		{
			if( $uppercase == true && count($line_synonyms) > 0 ){
				$_line_synonyms = $line_synonyms;
				$line_synonyms = array();
				foreach ($_line_synonyms as $_each_word ) {
					$line_synonyms[] = ucfirst( $_each_word );
				}
			}
			$restruct = array( $word );
			$restruct = array_merge( $restruct, $line_synonyms );
			$restruct = array_unique( $restruct );
			return implode( '|', $restruct );
		}
		
		/*
		 * Load synonyms 
		 */ 
		private function load_synonyms()
		{
			$this->synonyms_str = $this->wp_filesystem->get_contents( dirname(__FILE__) . '/treasures/treasures_' . ( $this->settings['language'] ) . '.dat' );
			
			// if get contents with wp_filesystem fails (on some servers), try to get treasure file with php native function
			if( !$this->synonyms_str ) {  
				$this->synonyms_str = file_get_contents( dirname(__FILE__) . '/treasures/treasures_' . ( $this->settings['language'] ) . '.dat' );
			}
		}
		
		/**
	    * Singleton pattern
	    *
	    * @return WooZoneLiteSpinner Singleton instance
	    */
	    static public function getInstance()
	    {
	        if (!self::$_instance) {
	            self::$_instance = new self;
	        }

	        return self::$_instance;
	    }
    }
}