<?php
/**
 * AA-Team - http://www.aa-team.com
 * ================================
 *
 * @package		WooZoneLiteAjaxListTable
 * @author		Andrei Dinca
 * @version		1.0
 */
! defined( 'ABSPATH' ) and exit;

if(class_exists('WooZoneLiteAjaxListTable') != true) {
	class WooZoneLiteAjaxListTable {

		/*
        * Some required plugin information
        */
        const VERSION = '1.0';
	
		/*
        * Singleton pattern
        */
		static protected $_instance;

		/*
        * Store some helpers
        */
		public $the_plugin = null;

		/*
        * Store some default options
        */
		public $default_options = array(
			'id' 					=> '', /* string, uniq list ID. Use for SESSION filtering / sorting actions */
			'debug_query' 			=> true, /* default is false */
			'show_header' 			=> true, /* boolean, true or flase */
			'show_nonamz_products' 	=> false, /* boolean, true or false */
			'list_post_types' 		=> 'all', /* array('post', 'pages' ... etc) or 'all' */
			'items_per_page' 		=> 15, /* number. How many items per page */
			'post_statuses' 		=> 'all',
			'search_box' 			=> true, /* boolean, true or flase */
			'show_statuses_filter' 	=> true, /* boolean, true or flase */
			'show_pagination' 		=> true, /* boolean, true or flase */
			'show_category_filter' 	=> true, /* boolean, true or flase */
			'columns' 				=> array(),
			'custom_table' 			=> '',
			'requestFrom'			=> 'init', /* values: init | ajax */
			
			'custom_table_force_action' 	=> false,
			'deleted_field' 				=> false,
			'force_publish_field' 			=> false,
			'show_header_buttons' 			=> false,
			'params'						=> null,
		);
		private $items;
		private $items_nr;
        private $items_assets_nr = array('total' => 0, 'done' => 0);
		private $args;

		public $opt = array();
		public $moduleparams = array();
		private $filter_fields = array();

        /*
        * Required __construct() function that initalizes the AA-Team Framework
        */
        public function __construct( $parent )
        {
        	$this->the_plugin = $parent;
			add_action('wp_ajax_WooZoneLiteAjaxList', array( $this, 'request' ));
			add_action('wp_ajax_WooZoneLiteAjaxList_actions', array( $this, 'ajax_request' ), 10, 2);
        }

		/**
	    * Singleton pattern
	    *
	    * @return class Singleton instance
	    */
	    static public function getInstance( $parent )
	    {
	        if (!self::$_instance) {
	            self::$_instance = new self($parent);
	        }

	        return self::$_instance;
	    }

		/**
	    * Setup
	    *
	    * @return class
	    */
		public function setup( $options=array() )
		{
			global $WooZoneLite;
			$this->opt = array_merge( $this->default_options, $options );
			
			$this->opt["custom_table"] = trim($this->opt["custom_table"]);
			//if ( $this->opt["custom_table"] != "") {
			//	$this->opt = array_merge( $this->opt, array(
			//		'orderby'		=> 'id',
			//		'order'			=> 'DESC',
			//	));
			//}

			if ( isset($options['moduleparams']) ) {
				$this->moduleparams = $options['moduleparams'];
				// clean so we don't have to send entire object in session var
				foreach ($options['moduleparams'] as $key => $val) {
					$options['moduleparams']["$key"] = 'init';
				}
			}
			foreach ($this->moduleparams as $key => $val) {
				if ( 'init' != $val && is_object($val) ) continue 1;
				if ( 'auto_import' == $key ) {
					// Initialize the WooZoneLiteAutoImport class
					require_once( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '/modules/auto_import/init.php' );
					$WooZoneLiteAutoImport = WooZoneLiteAutoImport::getInstance();
					$this->moduleparams["$key"] = $WooZoneLiteAutoImport;
				}
			}

			//unset($_SESSION['WooZoneLiteListTable']); // debug

			// check if set, if not, reset
			if ( isset($options['requestFrom']) && $options['requestFrom'] == 'ajax' ) ;
			else {

				$keepvar = isset($_SESSION['WooZoneLiteListTable']['keepvar']) ? $_SESSION['WooZoneLiteListTable']['keepvar'] : '';
				$sess = isset($_SESSION['WooZoneLiteListTable'][$this->opt['id']]['params']) ? $_SESSION['WooZoneLiteListTable'][$this->opt['id']]['params'] : array();

				$options['params']['posts_per_page'] = isset($sess['posts_per_page']) ? $sess['posts_per_page'] : $this->opt['items_per_page'];
				if ( isset($keepvar) && isset($keepvar['paged']) ) {
					$options['params']['paged'] = isset($sess['paged']) ? $sess['paged'] : 1;
					unset( $keepvar['paged'] );
					$_SESSION['WooZoneLiteListTable']['keepvar'] = $keepvar;
				}

			}
			$_SESSION['WooZoneLiteListTable'][$this->opt['id']] = $options;

			return $this;
		}

		/**
	    * Singleton pattern
	    *
	    * @return class Singleton instance
	    */
		public function request()
		{
			$request = array(
				'sub_action' 	=> isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : '',
				'ajax_id' 		=> isset($_REQUEST['ajax_id']) ? $_REQUEST['ajax_id'] : '',
				'params' 		=> isset($_REQUEST['params']) ? $_REQUEST['params'] : '',
			);
  
			if( $request['sub_action'] == 'post_per_page' ){
				$new_post_per_page = $request['params']['post_per_page'];

				if( $new_post_per_page == 'all' ){
					$_SESSION['WooZoneLiteListTable'][$request['ajax_id']]['params']['posts_per_page'] = '-1';
				}
				elseif( (int)$new_post_per_page == 0 ){
					$_SESSION['WooZoneLiteListTable'][$request['ajax_id']]['params']['posts_per_page'] = $this->opt['items_per_page'];
				}
				else{
					$_SESSION['WooZoneLiteListTable'][$request['ajax_id']]['params']['posts_per_page'] = $new_post_per_page;
				}

				// reset the paged as well
				$_SESSION['WooZoneLiteListTable'][$request['ajax_id']]['params']['paged'] = 1;
			}
  
			if( $request['sub_action'] == 'paged' ){
				$new_paged = $request['params']['paged'];
				if( $new_paged < 1 ){
					$new_paged = 1;
				}

				$_SESSION['WooZoneLiteListTable'][$request['ajax_id']]['params']['paged'] = $new_paged;
			}

			if( $request['sub_action'] == 'post_type' ){
				$new_post_type = $request['params']['post_type'];
				if( $new_post_type == "" ){
					$new_post_type = "";
				}

				$_SESSION['WooZoneLiteListTable'][$request['ajax_id']]['params']['post_type'] = $new_post_type;

				// reset the paged as well
				$_SESSION['WooZoneLiteListTable'][$request['ajax_id']]['params']['paged'] = 1;
			}

			if( $request['sub_action'] == 'post_parent' ){
				$new_post_parent = $request['params']['post_parent'];
				if( $new_post_parent == "" ){
					$new_post_parent = "";
				}

				$_SESSION['WooZoneLiteListTable'][$request['ajax_id']]['params']['post_parent'] = $new_post_parent;

				// reset the paged as well
				$_SESSION['WooZoneLiteListTable'][$request['ajax_id']]['params']['paged'] = 1;
			}

			if( $request['sub_action'] == 'post_status' ){
				$new_post_status = $request['params']['post_status'];
				if( $new_post_status == "all" ){
					$new_post_status = "";
				}

				$_SESSION['WooZoneLiteListTable'][$request['ajax_id']]['params']['post_status'] = $new_post_status;

				// reset the paged as well
				$_SESSION['WooZoneLiteListTable'][$request['ajax_id']]['params']['paged'] = 1;
			}
			
			if( $request['sub_action'] == 'general_field' ){
				$filter_name = isset($request['params']['filter_name']) ? $request['params']['filter_name'] : '';
				$filter_val = isset($request['params']['filter_val']) ? $request['params']['filter_val'] : '';
				if( $filter_val == "all" ){
					$filter_val = "";
				}

				$_SESSION['WooZoneLiteListTable'][$request['ajax_id']]['params']["$filter_name"] = $filter_val;

				// reset the paged as well
				$_SESSION['WooZoneLiteListTable'][$request['ajax_id']]['params']['paged'] = 1;
			}

			if( $request['sub_action'] == 'search' ){
				$search_text = $request['params']['search_text'];
				
				$_SESSION['WooZoneLiteListTable'][$request['ajax_id']]['params']['search_text'] = $search_text;

				// reset the paged as well
				$_SESSION['WooZoneLiteListTable'][$request['ajax_id']]['params']['paged'] = 1;
			}
  
			// create return html
			ob_start();
			
			$_SESSION['WooZoneLiteListTable'][$request['ajax_id']]['requestFrom'] = 'ajax';

			$this->setup( $_SESSION['WooZoneLiteListTable'][$request['ajax_id']] );
			$this->print_html();
			$html = ob_get_contents();
			ob_clean();

			$return = array(
				'status' 	=> 'valid',
				'html'		=> $html
				//,'sess'		=> $_SESSION['pspListTable'][$request['ajax_id']]['params']
			);
			
			die( json_encode( array_map('utf8_encode', $return) ) );
		}

		/**
	    * Helper function
	    *
	    * @return object
	    */
		public function get_items()
		{
			global $wpdb;

			$ses = isset($_SESSION['WooZoneLiteListTable'][$this->opt['id']]['params']) ? $_SESSION['WooZoneLiteListTable'][$this->opt['id']]['params'] : array();
			//var_dump('<pre>',$ses,'</pre>'); die;

			$this->args = array(
				'posts_per_page'  	=> ( isset($ses['posts_per_page']) ? $ses['posts_per_page'] : $this->opt['items_per_page'] ),
				'paged'				=> ( isset($ses['paged']) ? $ses['paged'] : 1 ),
				'category'        	=> ( isset($ses['category']) ? $ses['category'] : '' ),
				'orderby'         	=> 'post_date',
				'order'          	=> 'DESC',
				'post_type'       	=> ( isset($ses['post_type']) && trim($ses['post_type']) != "all" ? $ses['post_type'] : array_keys($this->get_list_postTypes()) ),
				'post_status'     	=> ( isset($ses['post_status']) ? $ses['post_status'] : '' ),
				'suppress_filters' 	=> true
			);
			
			if ( isset($ses['post_parent']) && trim($ses['post_parent']) != "all" ) {
				$this->args = array_merge($this->args, array(
					'post_parent'       	=> $ses['post_parent']
				));
			}

			// if custom table, make request in the custom table not in wp_posts
			$this->opt["custom_table"] = trim($this->opt["custom_table"]);
			if( $this->opt["custom_table"] != ""){
				$pages = array();

				//---------------
				// Query Start
			    // select all pages and post from DB
			    $myQuery = "SELECT SQL_CALC_FOUND_ROWS a.* FROM " . $wpdb->prefix . ( $this->opt["custom_table"] ) . " as a WHERE 2=2 ";

				if( $this->opt["custom_table"] == 'amz_products' ) {
			    	$myQuery = "SELECT SQL_CALC_FOUND_ROWS a.* FROM " . $wpdb->prefix  . ( $this->opt["custom_table"] ) . " as a LEFT JOIN " . $wpdb->prefix  . ( 'posts' ) . " as b ON a.post_id = b.ID WHERE a.type='post' and a.status='new' AND !isnull(b.ID) ";
				}
				
				// search fields
				$search_where = $this->search_posts_where();
				//$search_where = str_replace('AND ', '', $search_where);
				$myQuery .= $search_where;
				
				// dropdown filter fields
				$filter_where = '';
				$filter_fields = isset($this->opt["filter_fields"]) && !empty($this->opt["filter_fields"])
					? $this->opt["filter_fields"] : array();
				foreach ($filter_fields as $field => $vals) {
					$this->filter_fields["$field"] = array();
					$field_val = isset($ses["$field"]) && trim($ses["$field"]) != "" ? $ses["$field"] : '';
					if ( $field_val != '' ) {
						$filter_where .= " AND $field = '" . esc_sql($field_val) . "' ";
					}
				}
				$myQuery .= $filter_where;
				
				$myQuery .= ' AND 1=1 ';

				// limit query
			    $__limitClause = $this->args['posts_per_page']>0 ? " 1=1 limit " . (($this->args['paged'] - 1) * $this->args['posts_per_page']) . ", " . $this->args['posts_per_page'] : '1=1 ';
				$result_query = str_replace("1=1 ", $__limitClause, $myQuery);

				// order by
				$orderby = isset($this->opt["orderby"]) ? $this->opt["orderby"] : '';
				$order = isset($this->opt["order"]) ? $this->opt["order"] : 'ASC';
				if( !empty($orderby) ) {
					if ( $this->args['posts_per_page']>0 ) {
						$result_query = str_replace('1=1 limit', "1=1 ORDER BY a.$orderby $order limit", $result_query);
					}
					else {
						$result_query = str_replace('1=1', "1=1 ORDER BY a.$orderby $order", $result_query);	
					}
				}

				//publish field
			    if (isset($this->opt["force_publish_field"]) && $this->opt["force_publish_field"]) {
			    	$myQuery = str_replace("1=1 ", " 1=1 and a.publish='Y' ", $myQuery);
			    	$result_query = str_replace("1=1 ", " 1=1 and a.publish='Y' ", $result_query);
			    }

			    //deleted field
			    if (isset($this->opt["deleted_field"]) && $this->opt["deleted_field"]) {
			    	$myQuery = str_replace("1=1 ", " 1=1 and a.deleted=0 ", $myQuery);
			    	$result_query = str_replace("1=1 ", " 1=1 and a.deleted=0 ", $result_query);
			    }

			    $myQuery .= ";"; $result_query .= ";";
				
				// dropdown filter fields
				//		when option <display> = links
				foreach ($filter_fields as $field => $vals) {
					$display = isset($vals['display']) && ('links' == $vals['display']) ? 'links' : 'default';

					if ( 'links' == $display ) {
						$sql_ff = $myQuery;

						$sql_ff = str_replace(" AND $field = '" . esc_sql($field_val) . "' ", "", $sql_ff);

						$sql_ff = str_replace('SQL_CALC_FOUND_ROWS', '', $sql_ff);
	                	$sql_ff = str_replace("a.*", "a.$field, count(a.id) as __nb", $sql_ff);
						$sql_ff = str_replace(";", " GROUP BY a.$field ORDER BY a.$field ASC", $sql_ff);
						$this->filter_fields["$field"]['count'] = $wpdb->get_results( $sql_ff, OBJECT_K );
					}
				}
				//var_dump('<pre>', $this->filter_fields, '</pre>'); die('debug...'); 
					
			    // Query End
			    //---------------

			    if( $this->opt["custom_table"] == 'amz_queue' ) {
					$__asins = array();
			    }
				else if( $this->opt["custom_table"] == 'amz_search' ) {
					$search_ids = array();
			    }

			    $query = $wpdb->get_results( $result_query, ARRAY_A);

			    foreach ($query as $key => $myrow){
			    	if( $this->opt["custom_table"] == 'amz_products' ) {
				    	$pages[$myrow['post_id']] = $myrow;
			    	}
					
					else if( $this->opt["custom_table"] == 'amz_queue' ) {
				    	$pages[$myrow['id']] = $myrow;

						$pages[$myrow['id']]['status_msg'] = !empty($myrow['status_msg'])
							? str_replace( '—', '&#8212;', @unserialize(  $myrow['status_msg'] ) ) : '';
						$pages[$myrow['id']]['product_id'] = 0;

						$__asins[] = $myrow['asin'];
					}
					
					else if( $this->opt["custom_table"] == 'amz_search' ) {
						$pages[$myrow['id']] = $myrow;

						$pages[$myrow['id']]['status_msg'] = !empty($myrow['status_msg'])
							? str_replace( '—', '&#8212;', @unserialize(  $myrow['status_msg'] ) ) : '';
						$pages[$myrow['id']]['params'] = !empty($myrow['params'])
							? unserialize( $myrow['params'] ) : array();
						$pages[$myrow['id']]['product_id'] = 0;

						$search_ids[] = $myrow['id'];
					}
			    } // end foreach
			    
				//$this->items_nr = $wpdb->get_var( str_replace("a.*", "count(a.id) as nbRow", $myQuery) );
				$this->items_nr = $wpdb->get_var( "SELECT FOUND_ROWS();" );
				
				if( $this->opt["custom_table"] == 'amz_queue' ) {
					$__asins = array_unique( array_filter( $__asins ) );
					if ( !empty($__asins) ) {
						$__asins_ = implode(',', array_map(array($this->the_plugin, 'prepareForInList'), $__asins));

						$sql_asin2id = "select pm.meta_value as asin, p.ID as id from " . $wpdb->prefix.'posts' . " as p left join " . $wpdb->prefix.'postmeta' . " as pm on p.ID = pm.post_id where 1=1 and !isnull(p.ID) and pm.meta_key = '_amzASIN' and pm.meta_value != '' and pm.meta_value in ($__asins_);";
						$res_asin2id = $wpdb->get_results( $sql_asin2id, OBJECT_K );
						//var_dump('<pre>', $res_asin2id, '</pre>'); die('debug...');  
						if ( !empty($res_asin2id) ) {
							foreach ($pages as $k => $v) {
								$asin = $v['asin'];
								if ( isset($res_asin2id["$asin"]) ) {
									$pages["$k"]['product_id'] = $res_asin2id["$asin"]->id;	
								}
							}
						}
					}
				}
				else if( $this->opt["custom_table"] == 'amz_search' ) {
					$search_ids = array_unique( array_filter( $search_ids ) );
					if ( !empty($search_ids) ) {
						foreach ($search_ids as &$value) { $value = 'search#'.$value; }
						$search_ids_ = implode(',', array_map(array($this->the_plugin, 'prepareForInList'), $search_ids));

						$sql_search2queue = "select a.from_op, a.status, count(a.id) as nb from " . $wpdb->prefix.'amz_queue' . " as a where 1=1 and a.from_op in ($search_ids_) group by a.from_op, a.status;";
						$res_search2queue = $wpdb->get_results( $sql_search2queue, ARRAY_A );
						//var_dump('<pre>', $res_search2queue, '</pre>'); die('debug...');  
						if ( !empty($res_search2queue) ) {
							foreach ($res_search2queue as $k => $v) {
								$search_id = str_replace('search#', '', $v['from_op']);
								if ( isset($pages["$search_id"]) ) {
									$queue_status = $v['status'];
									$queue_nb = $v['nb'];
									if ( !isset($pages["$search_id"]['queue']) ) {
										$pages["$search_id"]['queue'] = array();
									}
									$pages["$search_id"]['queue']["$queue_status"] = $queue_nb;
								}
							}
						}
					}
				}

				//var_dump('<pre>',$pages,'</pre>');

				$this->items = $pages;

				if( $this->opt["custom_table"] == 'amz_products' ) {
					$qnb_items = $myQuery;
					$qnb_items = str_replace('SQL_CALC_FOUND_ROWS', '', $qnb_items);
					$qnb_items = str_replace("a.*", "count(a.post_id) as nbRow", $qnb_items);
					$this->items_nr = $wpdb->get_var( $qnb_items );
					//$this->items_nr = $wpdb->get_var( "SELECT FOUND_ROWS();" );

					$qnb_assets = $myQuery;
					$qnb_assets = str_replace('SQL_CALC_FOUND_ROWS', '', $qnb_assets);
	                $qnb_assets = str_replace("a.*", "sum(a.nb_assets) as total, sum(a.nb_assets_done) as done", $qnb_assets);
	                $qnb_assets = str_replace("type='post'", "type in ('post', 'variation')", $qnb_assets);
	                $nb_assets = $wpdb->get_row( $qnb_assets, ARRAY_A  );
	                
	                $this->items_assets_nr['total'] += $nb_assets['total'];
	                $this->items_assets_nr['done'] += $nb_assets['done'];
				}

				$dbg_query = $result_query;
			}
			else{

				// remove empty array
				$this->args = array_filter($this->args);
				
				//hook retrieve posts where clause
				add_filter( 'posts_where' , array( &$this, 'search_posts_where' ) );
				
				$args = array_merge($this->args, array(
					'suppress_filters' => false,
					//'no_found_rows'		=> true,
				));

				//$this->items = get_posts( $args );

				// get all post count
				//$nb_args = $args;
				//$nb_args['posts_per_page'] = '-1';
				//$nb_args['fields'] = 'ids';
				//$this->items_nr = (int) count( get_posts( $nb_args ) );
				
				$wpquery = new WP_Query( $args );
				$this->items = $wpquery->posts;
				$this->items_nr = (int) $wpquery->found_posts;

				if ( $this->opt['debug_query'] == true ) {
					//$query = new WP_Query( $args );
					$dbg_query = $wpquery->request;
				}
			}
			
			if ( $this->opt['debug_query'] == true ) {
				$dbg_query = preg_replace('/[\n\r\t]*/imu', '', $dbg_query);
				echo '<script>';
				echo 	'console.log("query rows// ' . $this->items_nr . '");';
				echo 	'console.log("query// ' . $dbg_query . '");';
				echo '</script>';
			}

			return $this;
		}
		
		public function search_posts_where( $where='' ) {

			if( is_admin() ) {
				$ses = $_SESSION['WooZoneLiteListTable'][$this->opt['id']]['params'];

				//search text
				$search_text = isset($ses['search_text']) ? $ses['search_text'] : '';
				$search_text = trim( $search_text );
				$esc_search_text = esc_sql($search_text);
					
				if ( isset( $search_text ) && $search_text!='' ) {
					//if ( $search_text!='' && $this->the_plugin->utf8->strlen($search_text)<200 )
					if ( $search_text!='' && strlen($search_text)<200 ) {
						if ( $this->opt["custom_table"] != '' ) {
							$search_fields = $this->opt["search_box"]['fields'];
							$__where = array();
							foreach( $search_fields as $v) {
								$__where[] = "a.$v regexp '" . $esc_search_text . "'";
							}
							$__where = implode(' OR ', $__where);
							if (count($search_fields) > 1 ) {
								$where .= " AND ( $__where ) ";
							}
							else {
								$where .= " AND $__where ";
							}
						}
						else {
							$where .= " AND ( post_title regexp '" . $esc_search_text . "' OR post_content regexp '" . $esc_search_text . "' ) ";
						}
					}
				}
			}
			return $where;
		}

		private function getAvailablePostStatus()
		{
			$ses = isset($_SESSION['WooZoneLiteListTable'][$this->opt['id']]['params']) ? $_SESSION['WooZoneLiteListTable'][$this->opt['id']]['params'] : array();

			$post_type = isset($ses['post_type']) && trim($ses['post_type']) != "" ? $ses['post_type'] : '';
			$post_type = trim( $post_type );
			$qClause = '';
			if ( $post_type!='' && $post_type!='all' )
				$qClause .= " AND post_type = '" . ( esc_sql($post_type) ) . "' ";
			else
				$qClause .= " AND post_type IN ( " . implode( ',', array_map( array($this->the_plugin, 'prepareForInList'), array_keys($this->get_list_postTypes()) ) ) . " ) ";
			
			$post_parent = isset($ses['post_parent']) && trim($ses['post_parent']) != "" ? $ses['post_parent'] : '';
			$post_parent = trim( $post_parent );
			//$qClause = ' AND post_parent > 0 ';
			if ( $post_parent!='' && $post_parent!='all' )
				$qClause .= " AND post_parent = '" . ( esc_sql($post_parent) ) . "' ";

			$sql = "SELECT count(id) as nbRow, post_status, post_type FROM " . ( $this->the_plugin->db->prefix ) . "posts WHERE 1 = 1 ".$qClause." group by post_status";
			$sql = preg_replace('~[\r\n]+~', "", $sql);

			return $this->the_plugin->db->get_results( $sql, ARRAY_A );
		}

		private function get_list_postTypes()
		{
			// overwrite wrong post-type value
			if( !isset($this->opt['list_post_types']) ) $this->opt['list_post_types'] = 'all';
			
			// custom array case
			if( is_array($this->opt['list_post_types']) && count($this->opt['list_post_types']) > 0 ) {
				//return $this->opt['list_post_types'];
				$__ = array();
				foreach ($this->opt['list_post_types'] as $key => $value) {
					$__[$value] = get_post_type_object( $value );
				} 
				return $__;
			}

			// all case
			//return get_post_types(array('show_ui' => TRUE, 'show_in_nav_menus' => TRUE), 'objects');
			$_builtin = get_post_types(array('show_ui' => TRUE, 'show_in_nav_menus' => TRUE, '_builtin' => TRUE), 'objects');
			if ( !is_array($_builtin) || count($_builtin)<0 )
				$_builtin = array();

			$_notBuiltin = get_post_types(array('show_ui' => TRUE, 'show_in_nav_menus' => TRUE, '_builtin' => FALSE), 'objects');
			if ( !is_array($_notBuiltin) || count($_notBuiltin)<0 )
				$_notBuiltin = array();
				
			$exclude = array();
			$ret = array_merge($_builtin, $_notBuiltin);
			if (!empty($exclude)) foreach ( $exclude as $exc) if ( isset($ret["$exc"]) ) unset($ret["$exc"]);
  
			return $ret;
		}

		private function get_list_parentProducts()
		{
			$ses = isset($_SESSION['WooZoneLiteListTable'][$this->opt['id']]['params']) ? $_SESSION['WooZoneLiteListTable'][$this->opt['id']]['params'] : array();
			
			$qClause = '';
			$qClause .= " AND a.post_status IN ('publish') ";

			$post_parent = isset($ses['post_parent']) && trim($ses['post_parent']) != "" ? $ses['post_parent'] : '';
			$post_parent = trim( $post_parent );
			$qClause .= ' AND a.post_parent > 0 ';
			//if ( $post_parent!='' && $post_parent!='all' )
			//	$qClause .= " AND a.post_parent = '" . ( esc_sql($post_parent) ) . "' ";
			
			$qClause .= " AND a.post_type IN ( " . implode( ',', array_map( array($this->the_plugin, 'prepareForInList'), array_keys($this->get_list_postTypes()) ) ) . " ) ";

			$table_posts = $this->the_plugin->db->prefix . "posts";
			//$sql = "SELECT count(id) as nbRow, post_parent FROM " . ( $this->the_plugin->db->prefix ) . "posts WHERE 1 = 1 ".$qClause." group by post_parent;";
			//$qClause = "AND a.post_status IN ('publish')  AND a.post_parent > 0  AND a.post_type IN ( 'product','product_variation' )";
			$sql = "SELECT COUNT(a.id) AS nbRow, a.post_parent as _ID, b.post_title as _title
 FROM $table_posts AS a RIGHT JOIN $table_posts AS b ON a.post_parent = b.ID
 WHERE 1=1
 AND ( !ISNULL(b.ID) AND b.ID > 0 AND b.post_status IN ('publish') )
 ".$qClause."
 GROUP BY a.post_parent
 ORDER BY _title ASC
;";
			$sql = preg_replace('~[\r\n]+~', "", $sql);
    
			$ret = array();
			$res = $this->the_plugin->db->get_results( $sql, ARRAY_A );
			if ( !empty($res) ) {
				foreach ( $res as $key => $val ) {
					$_id = $val['_ID'];
					$ret["$_id"] = $val;
					$ret["$_id"]['_title'] = $ret["$_id"]['_title'] . ' (' . $ret["$_id"]['nbRow'] . ')';
				}
  
				/*$args = array(
					'post_type' 	=> 'product',
					'post__in' 		=> (array) array_keys($ret)
				);
				$parentPosts = get_posts( $args );
  
				foreach ( $parentPosts as $key2 => $val2 ) {
					$_id = $val2->ID;
					$ret["$_id"]['_title'] = $val2->post_title . ' (' . $ret["$_id"]['nbRow'] . ')';
				}*/
			}
			return $ret;
		}

		private function get_pagination()
		{
			$html = array();

			$ses = isset($_SESSION['WooZoneLiteListTable'][$this->opt['id']]['params']) ? $_SESSION['WooZoneLiteListTable'][$this->opt['id']]['params'] : array();

			$posts_per_page = ( isset($ses['posts_per_page']) ? $ses['posts_per_page'] : $this->opt['items_per_page'] );
			$paged = ( isset($ses['paged']) ? $ses['paged'] : 1 );
			$total_pages = ceil( $this->items_nr / $posts_per_page );
			
			if( $this->opt['show_pagination'] ){
				$html[] = 	'<div class="WooZoneLite-list-table-right-col '. $this->opt["custom_table"] .' pagination">';

				$html[] = 		'<div class="WooZoneLite-list-table-pagination tablenav">';

				$html[] = 			'<div class="tablenav-pages">';
				$html[] = 				'<span class="displaying-num">' . ( $this->items_nr ) . ' items</span>';
				if( $total_pages > 1 ){
					$html[] = 				'<span class="pagination-links"><a class="first-page ' . ( $paged <= 1 ? 'disabled' : '' ) . ' WooZoneLite-jump-page" title="Go to the first page" href="#paged=1">&laquo;</a>';
					$html[] = 				'<a class="prev-page ' . ( $paged <= 1 ? 'disabled' : '' ) . ' WooZoneLite-jump-page" title="Go to the previous page" href="#paged=' . ( $paged > 2 ? ($paged - 1) : '' ) . '">&lsaquo;</a>';
					$html[] = 				'<span class="paging-input"><input class="current-page" title="Current page" type="text" name="paged" value="' . ( $paged ) . '" size="2" style="width: 45px;"> of <span class="total-pages">' . ( ceil( $this->items_nr / $this->args['posts_per_page'] ) ) . '</span></span>';
					$html[] = 				'<a class="next-page ' . ( $paged >= ($total_pages - 1) ? 'disabled' : '' ) . ' WooZoneLite-jump-page" title="Go to the next page" href="#paged=' . ( $paged >= ($total_pages - 1) ? $total_pages : $paged + 1 ) . '">&rsaquo;</a>';
					$html[] = 				'<a class="last-page ' . ( $paged >=  ($total_pages - 1) ? 'disabled' : '' ) . ' WooZoneLite-jump-page" title="Go to the last page" href="#paged=' . ( $total_pages ) . '">&raquo;</a></span>';
				}
				$html[] = 			'</div>';
				$html[] = 		'</div>';
				
				$html[] = 		'<div class="WooZoneLite-box-show-per-pages">';
				$html[] = 			'<select name="WooZoneLite-post-per-page" id="WooZoneLite-post-per-page" class="WooZoneLite-post-per-page">';


                $_range = array_merge( array(), range(5, 50, 5), range(100, 500, 100), range(1000, 5000, 1000) );
				foreach( $_range as $nr => $val ){
					$html[] = 			'<option val="' . ( $val ) . '" ' . ( $posts_per_page == $val ? 'selected' : '' ). '>' . ( $val ) . '</option>';
				}

				$html[] = 				'<option value="all">';
				$html[] =				__('Show All', $this->the_plugin->localizationName);
				$html[] = 				'</option>';
				$html[] =			'</select>';
				$html[] = 			'<label for="WooZoneLite-post-per-page" style="width:62px">' . __('per pages', $this->the_plugin->localizationName) . '</label>';
				$html[] = 		'</div>';

				$html[] = 	'</div>';
			}

			return implode("\n", $html);
		}

		public function print_header()
		{
			$nb_cols = 0;
			$html = array();
			$ses = isset($_SESSION['WooZoneLiteListTable'][$this->opt['id']]['params']) ? $_SESSION['WooZoneLiteListTable'][$this->opt['id']]['params'] : array();

			$post_type = isset($ses['post_type']) && trim($ses['post_type']) != "" ? $ses['post_type'] : '';
			$post_parent = isset($ses['post_parent']) && trim($ses['post_parent']) != "" ? $ses['post_parent'] : '';

			$html[] = '<div id="WooZoneLite-list-table-header">';

			if( $this->opt["custom_table"] == ""){
				$list_postTypes = $this->get_list_postTypes();

				$html[] = '<div class="WooZoneLite-list-table-left-col">';
				$html[] = 		'<select name="WooZoneLite-filter-post_type" class="WooZoneLite-filter-post_type">';
				if( count($list_postTypes) >= 2 ){
					$html[] = 		'<option value="all" >';
					$html[] =			__('Show All', $this->the_plugin->localizationName);
					$html[] = 		'</option>';	
				}

	            foreach ( $list_postTypes as $name => $postType ){
					$html[] = 		'<option ' . ( $name == $post_type ? 'selected' : '' ) . ' value="' . ( $this->the_plugin->escape($name) ) . '">';
					$html[] = 			( is_object($postType) ? ucfirst($this->the_plugin->escape($name)) : ucfirst($name) );
					$html[] = 		'</option>';
	            }
				$html[] = 		'</select>';

				if( isset($this->opt['show_parent_products']) && $this->opt['show_parent_products'] ){
					$list_parentProducts = $this->get_list_parentProducts();
					
					$html[] = 	'<select name="WooZoneLite-filter-post_parent" class="WooZoneLite-filter-post_parent">';
					$html[] = 		'<option value="all" >';
					$html[] =		__('Show All', $this->the_plugin->localizationName);
					$html[] = 		'</option>';

		            foreach ( $list_parentProducts as $id => $postParent ){
						$html[] = 		'<option ' . ( $id == $post_parent ? 'selected' : '' ) . ' value="' . ( $id ) . '">';
						$html[] = 			( $postParent['_title'] );
						$html[] = 		'</option>';
		            }

					$html[] =	'</select>';
				}
				
				if( $this->opt['show_statuses_filter'] ){
					$html[] = $this->post_statuses_filter();
				}
				$html[] = 		'</div>';
				$nb_cols++;

				if( $this->opt['search_box'] ){
					$html[] = 	'<div class="WooZoneLite-list-table-right-col">';
					$html[] = 		'<div class="WooZoneLite-list-table-search-box">';
					$html[] = 			'<input type="text" name="s" value="" >';
					$html[] = 			'<input type="button" name="" class="button" value="Search Posts">';
					$html[] = 		'</div>';
					$html[] = 	'</div>';
					$nb_cols++;
				}

				if( $this->opt['show_category_filter']  && 3==4 ){
					$html[] = '<div class="WooZoneLite-list-table-left-col" >';
					$html[] = 	'<select name="WooZoneLite-filter-post_type" class="WooZoneLite-filter-post_type">';
					$html[] = 		'<option value="all" >';
					$html[] =		__('Show All', $this->the_plugin->localizationName);
					$html[] = 		'</option>';
					$html[] =	'</select>';
					$html[] = '</div>';
					$nb_cols++;
				}
			}else{
				if ( $this->opt["custom_table"] == 'amz_products' ) {
					$html[] = '<div class="WooZoneLite-list-table-left-col '. $this->opt["custom_table"] .'">'
					    . '<span>Number of products: ' . $this->items_nr . '</span>'
					    //. '<span style="margin-left: 20px;">Number of assets (total: ' . $this->items_assets_nr['total'] . ' | done: ' . $this->items_assets_nr['done'] . ')</span>'
					    . '<span style="margin-left: 20px;">Number of assets: ' . $this->items_assets_nr['total'] . '</span>'
					    . ( $this->the_plugin->is_remote_images ? '<a href="' . admin_url("admin.php?page=WooZoneLite#!/amazon") . '" style="margin-left: 20px; display: inline-block; color: red; font-weight: bold;">Remote amazon images option is active.</a>' : '' )
					. '</div>';
					$nb_cols++;
				} else {

					// dropdown filter fields
					$filter_fields = isset($this->opt["filter_fields"]) && !empty($this->opt["filter_fields"])
						? $this->opt["filter_fields"] : array();
					
					$html[] = '<div class="WooZoneLite-list-table-left-col '. $this->opt["custom_table"] .'">';
					foreach ($filter_fields as $field => $vals) {
						
						$field_val = isset($ses["$field"]) && trim($ses["$field"]) != "" ? $ses["$field"] : '';
						$include_all = isset($vals['include_all']) ? $vals['include_all'] : false;

						// drowdown options list
						$options = isset($vals['options']) ? $vals['options'] : array();
						if ( isset($vals['options_from_db']) && $vals['options_from_db'] ) {
							$_options = $this->get_filter_from_db( $field );
							$options = array_merge($options, $_options);
						}
						
						if ( $include_all ) { // && count($options) > 1
							$options = array_merge(array(), array(
								'all' 		=> __('Show All', $this->the_plugin->localizationName),
							), $options);
						}
						
						$display = isset($vals['display']) && ('links' == $vals['display']) ? 'links' : 'default';
						if ( 'links' == $display ) {

							$_options = array();

							$html[] = 	'<ul class="subsubsub WooZoneLite-filter-general_field" data-filter_field="'.$field.'">';

							$totals = 0;
							foreach ($options as $opt_key => $opt_text) {
								$_options["$opt_key"] = array('text' => $opt_text, 'nb' => 0);

								if ( 'all' == $opt_key ) continue 1;

								if ( isset($this->filter_fields["$field"], $this->filter_fields["$field"]["count"],
									$this->filter_fields["$field"]["count"]["$opt_key"]) ) {
									$_options["$opt_key"]['nb'] = (int) $this->filter_fields["$field"]["count"]["$opt_key"]->__nb;
								}
								$totals += $_options["$opt_key"]['nb'];
							}
							$_options["all"]['nb'] = (int) $totals;
				
							$cc = 0;
							foreach ($_options as $opt_key => $opt_vals) {
								$cc++;
								
								if ( ('all' == $opt_key) && !$include_all ) continue 1;

								$html[] = 	'<li class="ocs_post_status">';
								$html[] = 		'<a href="#'.$field.'=' . ( $opt_key ) . '" class="' . ( ( (string) $opt_key === (string) $field_val ) || ( 'all' == $opt_key && empty($field_val) ) ? 'current' : '' ) . '" data-filter_val="' . ( $opt_key ) . '">';
								$html[] = 			$this->the_plugin->escape($opt_vals['text']) . ' <span class="count">(' . ( $opt_vals['nb'] ) . ')</span>';
								$html[] = 		'</a>' . ( count($_options) > ($cc) ? ' |' : '');
								$html[] = 	'</li>';
							}

							$html[] = 	'</ul>';

						}
						else {

							// dropdown html
							$html[] = 		'<select name="WooZoneLite-filter-'.$field.'" class="WooZoneLite-filter-general_field" data-filter_field="'.$field.'">';
							if ( isset($vals['title']) ) {
								$html[] =		'<option value="" disabled="disabled">';
								$html[] =			$vals['title'];
								$html[] = 		'</option>';
							}
							//if ( $include_all && count($options) > 1 ) {
							//	$html[] = 		'<option value="all" >';
							//	$html[] =			__('Show All', $this->the_plugin->localizationName);
							//	$html[] = 		'</option>';
							//}
				            foreach ( $options as $opt_key => $opt_text ){
								$html[] = 		'<option ' . ( (string) $opt_key === (string) $field_val ? 'selected' : '' ) . ' value="' . ( $this->the_plugin->escape($opt_key) ) . '">';
								$html[] = 			$this->the_plugin->escape($opt_text);
								$html[] = 		'</option>';
				            }
							$html[] = 		'</select>';

						}
					}
					$html[] = '</div>';
					$nb_cols++;

					//$html[] = '<div class="WooZoneLite-list-table-left-col">'
					//    . '<span>Number of rows: ' . $this->items_nr . '</span>'
					//. '</div>';
					
					// search box
					$search_box = isset($this->opt['search_box']) && !empty($this->opt['search_box'])
						? $this->opt['search_box'] : false;
					if( !empty($search_box) ){
						$search_text = isset($ses['search_text']) ? $ses['search_text'] : '';

						$search_title = isset($search_box['title'])
							? $search_box['title'] : __('Search', $this->the_plugin->localizationName);
							
						$search_fields = isset($search_box['fields']) ? implode(',', $search_box['fields']) : '';

						$html[] = 	'<div class="WooZoneLite-list-table-right-col '. $this->opt["custom_table"] .'">';
						$html[] = 		'<div class="WooZoneLite-list-table-search-box">';
						$html[] = 			'<input type="text" name="WooZoneLite-search-text" id="WooZoneLite-search-text" value="'.($search_text).'" class="'.($search_text!='' ? 'search-highlight' : '').'" />';
						$html[] = 			'<input type="button" name="WooZoneLite-search-btn" id="WooZoneLite-search-btn" class="WooZoneLite-form-button-small WooZoneLite-form-button-primary" value="' . $search_title . '" />';
						$html[] = 		'</div>';
						$html[] = 	'</div>';
						$nb_cols++;
					}
				}
			}

			// buttons
			if ( $this->opt["show_header_buttons"] ) {
				if( isset($this->opt['mass_actions']) && ($this->opt['mass_actions'] === false) ){
					$html[] = '<div class="WooZoneLite-list-table-left-col '. $this->opt["custom_table"] .'" style="padding-top: 5px;">&nbsp;</div>';
				}elseif( isset($this->opt['mass_actions']) && is_array($this->opt['mass_actions']) && ! empty($this->opt['mass_actions']) ){
					$html[] = '<div class="WooZoneLite-list-table-left-col '. $this->opt["custom_table"] .'" style="padding-top: 5px;">&nbsp;';

					foreach ($this->opt['mass_actions'] as $key => $value){
						$html[] = 	'<input type="button" value="' . ( $value['value'] ) . '" id="WooZoneLite-' . ( $value['action'] ) . '" class="WooZoneLite-' . ( $value['action'] ) . ' WooZoneLite-button ' . ( $value['color'] ) . '">';
					}
					$html[] = '</div>';
				}else {
					$html[] = '<div class="WooZoneLite-list-table-left-col" style="padding-top: 5px;">&nbsp;';
					$html[] = '</div>';
				}

				$nb_cols++;
			}
			else{
				$html[] = '<div class="WooZoneLite-list-table-left-col" style="padding-top: 5px;">&nbsp;</div>';
				$nb_cols++;
			}

			// show top pagination
			if ( !($nb_cols%2) ) {
				$html[] = '<div style="padding-top: 5px;" class="WooZoneLite-list-table-left-col">&nbsp;</div>';
			}
			$html[] = $this->get_pagination();

			$html[] = '</div>';

            echo implode("\n", $html);

			return $this;
		}

		public function print_main_table( $items=array() )
		{
			$html = array();

			if( $this->opt['id'] == 'WooZoneLiteSyncMonitor' ) {
				$last_updated_product = (int)get_option( 'WooZoneLite_last_updated_product', true);
				if( $last_updated_product > 0 ){
					$last_sync_date = get_post_meta($last_updated_product, '_last_sync_date', true);
					
					$html[] = 	'<div class="WooZoneLite-last-updated-product WooZoneLite-message WooZoneLite-info">';
					$html[] =		__('The last product synchronized was:', $this->the_plugin->localizationName);
					$html[] =		'<strong>' . $last_updated_product . '</strong>. ';
					$html[] =		__('This was synchronized at:', $this->the_plugin->localizationName);
					$html[] =		'<i>' . ( $last_sync_date ) . '</i>';
					$html[] = 	'</div>';
				}
			}
 
			$html[] = '<div id="WooZoneLite-list-table-posts">';	
			$html[] = 	'<table class="WooZoneLite-table" id="' . ( $this->opt["id"] ) . '" style="border: none;border-bottom: 1px solid #f2f2f2;">';
			$html[] = 		'<thead>';
			$html[] = 			'<tr>';
			foreach ($this->opt['columns'] as $key => $value){
				if( $value['th'] == 'checkbox' ){
					$html[] = '<th class="checkbox-column" width="20"><input type="checkbox" id="WooZoneLite-item-check-all" checked></th>';
				}
				else{
					$html[] = '<th' . ( isset($value['width']) && (int)$value['width'] > 0 ? ' width="' . ( $value['width'] ) . '"' : '' ) . '' . ( isset($value['align']) && $value['align'] != "" ? ' align="' . ( $value['align'] ) . '"' : '' ) . '>' . ( $value['th'] ) . '</th>';
				}
			}

			$html[] = 			'</tr>';
			$html[] = 		'</thead>';

			$html[] = 		'<tbody>';
			
			if( $this->opt["custom_table"] == "amz_products" && count($this->items) == 0 ){
				$html[] = '<td colspan="' . ( count($this->opt['columns']) ) . '" style="text-align:left">
					<div class="WooZoneLite-message WooZoneLite-success">Good news, all products assets has been downloaded successfully!</div>
				</td>';
			}
			 
			foreach ($this->items as $post){
				$post_id = 0;
				$is_post = true;
				if ( isset($post->ID) ) $post_id = $post->ID;
				else if ( is_array($post) && isset($post['post_id']) ) $post_id = $post['post_id'];
				if ( is_array($post) && isset($post['id']) ) {
					$post_id = $post['id'];
					$is_post = false;
				}
  
				if ( $post_id > 0 ) {
					$item_data = array(
						//'score' 	=> get_post_meta( $post_id, 'WooZoneLite_score', true ) // this is from psp
					);
                }
				
				if ( $is_post ) {
					$prod_asin = get_post_meta($post_id, '_amzASIN', true);
				}
				else {
					$prod_asin = isset($post['asin']) ? $post['asin'] : 'xyz';
				}
				$verify_cond = !empty($prod_asin) ? true : false;

				// fix - check if product have ASIN and then display it in the price fix ajax table
				if ( $verify_cond ){
				    
				$html[] = 			'<tr data-itemid="' . ( $post_id ) . '">';
				foreach ($this->opt['columns'] as $key => $value){

					$html[] = '<td class="WooZoneLite-' . str_replace('%', '', $value['td']) . '-td" style="'
						. ( isset($value['align']) && $value['align'] != "" ? 'text-align:' . ( $value['align'] ) . ';' : '' ) . ''
						. ( isset($value['valign']) && $value['valign'] != "" ? 'vertical-align:' . ( $value['valign'] ) . ';' : '' ) . ''
						. ( isset($value['css']) && count($value['css']) > 0 ? $this->print_css_as_style($value['css']) : '' ) . '">';

					if( $value['td'] == 'checkbox' ){
						$html[] = '<input type="checkbox" class="WooZoneLite-item-checkbox" name="WooZoneLite-item-checkbox-' . ( $post_id ) . '" checked>';
					}
					elseif( $value['td'] == '%ID%' ){
						$html[] = ( $post_id );
					}
					elseif( $value['td'] == '%parent_id%' ){
						$html[] = ( $post->post_parent );
					}
					elseif( $value['td'] == '%title%' ){
						$html[] = '<input type="hidden" id="WooZoneLite-item-title-' . ( $post_id ) . '" value="' . ( str_replace('"', "'", $post->post_title) ) . '" />';
						$html[] = '<a href="' . ( sprintf( admin_url('post.php?post=%s&action=edit'), $post_id)) . '">';
						$html[] = 	( $post->post_title . ( $post->post_status != 'publish' ? ' <span class="item-state">- ' . ucfirst($post->post_status) : '</span>') );
						$html[] = '</a>';
					}
					elseif( $value['td'] == '%button%' ){
						$value['option']['color'] = isset($value['option']['color']) ? $value['option']['color'] : 'gray';
						$html[] = 	'<input type="button" value="' . ( $value['option']['value'] ) . '" class="WooZoneLite-button ' . ( $value['option']['color'] ) . ' WooZoneLite-' . ( $value['option']['action'] ) . '">';
					}
					elseif( $value['td'] == '%button_publish%' ){
						$color = isset($value['option']['color']) ? $value['option']['color'] : 'gray';
						$color_change = isset($value['option']['color_change']) ? $value['option']['color_change'] : 'gray';

						$html[] = 	'<input type="button" value="' . ( $post['publish']=='Y' ? $value['option']['value'] : $value['option']['value_change'] ) . '" class="WooZoneLite-button ' . ( $post['publish']=='Y' ? $color : $color_change ) . ' WooZoneLite-' . ( $value['option']['action'] ) . '">';
					}
					elseif( $value['td'] == '%date%' ){
						$html[] = '<i>' . ( $post->post_date ) . '</i>';
					}
					elseif( $value['td'] == '%thumb%' ){
						
						$html[] = get_the_post_thumbnail( $post_id, array(50, 50) );
					}
					elseif( $value['td'] == '%date%' ){
						$html[] = '<i>' . ( $post->post_date ) . '</i>';
					}
					elseif( $value['td'] == '%hits%' ){
						$hits = (int) get_post_meta($post_id, '_amzaff_hits', true);
						$html[] = '<i class="WooZoneLite-prod-stats-number hits">' . ( $hits ) . '</i>';
					}
					elseif( $value['td'] == '%added_to_cart%' ){
						$addtocart = (int) get_post_meta($post_id, '_amzaff_addtocart', true);
						$html[] = '<i class="WooZoneLite-prod-stats-number add-to-cart">' . ( $addtocart ) . '</i>';
					}
					elseif( $value['td'] == '%redirected_to_amazon%' ){
						$amzaff_woo_product_tabs = (int) get_post_meta($post_id, '_amzaff_redirect_to_amazon', true);
						$html[] = '<i class="WooZoneLite-prod-stats-number redirect-to-amazon">' . ( $amzaff_woo_product_tabs ) . '</i>';
					}
					elseif( $value['td'] == '%bad_url%' ){
						$html[] = '<i>' . ( $post['url'] ) . '</i>';
					}
					elseif( $value['td'] == '%asin%' ){
						//$asin = get_post_meta($post_id, '_amzASIN', true);
						$asin = $prod_asin;
						$html[] = '<strong>' . ( $asin ) . '</strong>';
					}
					elseif( $value['td'] == '%last_sync_date%' ){
						$last_sync_date = get_post_meta($post_id, '_last_sync_date', true);
						$html[] = '<i class="WooZoneLite-data-last_sync_date">' . ( $last_sync_date ) . '</i>';
					}
					elseif( $value['td'] == '%price%' ){
						$html[] = '<div class="WooZoneLite-data-price">';
						
						$localID = $post_id;
						
						$product_meta['product'] = array();
						$product_meta['product']['price_update_date'] = get_post_meta($localID, "_price_update_date", true);
						$product_meta['product']['sales_price'] = get_post_meta($localID, "_sale_price", true);
						$product_meta['product']['regular_price'] = get_post_meta($localID, "_regular_price", true);
						$product_meta['product']['price'] = get_post_meta($localID, "_price", true);
						
						if ( empty($product_meta['product']['sales_price']) && empty($product_meta['product']['regular_price']) ) {
							$product_meta['product']['variation_price'] = array('min' => get_post_meta($localID, "_min_variation_price", true), 'max' => get_post_meta($localID, "_max_variation_price", true));
						}

						if ( empty($product_meta['product']['sales_price']) && empty($product_meta['product']['regular_price']) ) {
							
							$html[] = 	'From price: ' . (isset($product_meta['product']['variation_price']['min']) && (float)$product_meta['product']['variation_price']['min'] > 0 ? '<strong id="_regular_price-' . ( isset($product_meta['product']['asin']) ? $product_meta['product']['asin'] : '0' ) . '">' . ( woocommerce_price( $product_meta['product']['variation_price']['min'] ) ) . '</strong>' : '&#8211;');
							$html[] = 	'<br />';
							$html[] = 	'To price: ' . (isset($product_meta['product']['variation_price']['max']) && (float)$product_meta['product']['variation_price']['max'] > 0 ? '<strong id="_sales_price-' . ( isset($product_meta['product']['asin']) ? $product_meta['product']['asin'] : '0' ) . '">' . ( woocommerce_price( $product_meta['product']['variation_price']['max'] ) ) . '</strong>' : '&#8211;');
						} else {
							
							$html[] = 	'Regular price: ' . (isset($product_meta['product']['regular_price']) && (float)$product_meta['product']['regular_price'] > 0 ? '<strong id="_regular_price-' . ( isset($product_meta['product']['asin']) ? $product_meta['product']['asin'] : '0' ) . '">' . ( woocommerce_price( $product_meta['product']['regular_price'] ) ) . '</strong>' : '&#8211;');
							$html[] = 	'<br />';
							$html[] = 	'Sales price (offer): ' . (isset($product_meta['product']['sales_price']) && (float)$product_meta['product']['sales_price'] > 0 ? '<strong id="_sales_price-' . ( isset($product_meta['product']['asin']) ? $product_meta['product']['asin'] : '0' ) . '">' . ( woocommerce_price( $product_meta['product']['sales_price'] ) ) . '</strong>' : '&#8211;');
						}
						
						// &#8211; = unicode EN DASH
						$html[] = '</div>';
					}
					elseif( $value['td'] == '%last_date%' ){
						$html[] = '<i>' . ( $post['data'] ) . '</i>';
					}
					elseif( $value['td'] == '%preview%' ){
						//$asin = get_post_meta($post_id, '_amzASIN', true);
						$asin = $prod_asin;
						$html[] = "<div class='WooZoneLite-product-preview'>";
						$html[] = 	get_the_post_thumbnail( $post_id, array(150, 150) );
						$html[] = 	"<div class='WooZoneLite-product-label'><strong>" . ( $post->post_title ) . "</strong></div>";
						$html[] = 	"<div class='WooZoneLite-product-label'>ASIN: <strong>" . ( $asin ) . "</strong></div>";
						$html[] = 	"<div class='WooZoneLite-product-label'>";
						$html[] = 		'<a href="' . ( get_permalink( $post_id ) ) . '" class="WooZoneLite-form-button-small WooZoneLite-form-button-info">' . __('View product', $this->the_plugin->localizationName) . '</a>';
						$html[] = 		'<a href="' . ( admin_url( 'post.php?post=' . ( $post_id ) . '&action=edit' ) ) . '" class="WooZoneLite-form-button-small WooZoneLite-form-button-success">' . __('Edit product', $this->the_plugin->localizationName) . '</a>';
						$html[] = 	"</div>";
						$html[] = "</div>";
					}
					
					elseif( $value['td'] == '%spinn_content%' ){
						
						// first check if you have the original content saved into DB
						$post_content = get_post_meta( $post_id, 'WooZoneLite_old_content', true );
						
						// if not, retrive from DB
						if( $post_content == false ){
							$live_post = get_post( $post_id, ARRAY_A );
							$post_content = $live_post['post_content'];
						}
						
						$post_content = htmlentities( wpautop( $post_content ) );
						
						$finded_replacements = get_post_meta( $post_id, 'WooZoneLite_finded_replacements', true );
						if( $finded_replacements && count($finded_replacements) > 0 ){
							
							foreach ($finded_replacements as $word) {
								$post_content = str_replace($word, "<span class='WooZoneLite-word-" . ( sanitize_title($word) ) . "'>" . ( $word ) . "</span>", $post_content);
							}
						}
						$reorder_content = get_post_meta( $post_id, 'WooZoneLite_reorder_content', true );
						
						$html[] = "<div class='WooZoneLite-spinn-container'>";
						$html[] = "<table class='WooZoneLite-spinn-content'>";
						$html[] = 	"<tr>";
						$html[] = 		"<td width='49%' class='WooZoneLite-spinn-border-right'>";
						$html[] = 			"<h2>" . ( __('Fresh (spin) Content', $this->the_plugin->localizationName) ) . "</h2>";
						$html[] = 		"</td>";
						$html[] = 		"<td>";
						$html[] =			"<h2>" . ( __('Old (original) Content', $this->the_plugin->localizationName) ) . "</h2>";
						$html[] = 		"</td>";
						$html[] = 	"</tr>";
						$html[] = 	"<tr>";

						$html[] = "<td class='WooZoneLite-product-preview-mobile'>";
						//$asin = get_post_meta($post_id, '_amzASIN', true);
						$asin = $prod_asin;
						$html[] = "<div class='WooZoneLite-product-preview'>";
						$html[] = 	get_the_post_thumbnail( $post_id, array(150, 150) );
						$html[] = 	"<div class='WooZoneLite-product-label'><strong>" . ( $post->post_title ) . "</strong></div>";
						$html[] = 	"<div class='WooZoneLite-product-label'>ASIN: <strong>" . ( $asin ) . "</strong></div>";
						$html[] = 	"<div class='WooZoneLite-product-label'>";
						$html[] = 		'<a href="' . ( get_permalink( $post_id ) ) . '" class="WooZoneLite-form-button-small WooZoneLite-form-button-info">' . __('View product', $this->the_plugin->localizationName) . '</a>';
						$html[] = 		'<a href="' . ( admin_url( 'post.php?post=' . ( $post_id ) . '&action=edit' ) ) . '" class="WooZoneLite-form-button-small WooZoneLite-form-button-success">' . __('Edit product', $this->the_plugin->localizationName) . '</a>';
						$html[] = 	"</div>";
						$html[] = "</div>";
						$html[] = "</td>";

						$html[] = 		"<td width='49%' class='WooZoneLite-spinn-border-right'>";
						$html[] = 		"<div class='WooZoneLite-spin-editor-container'>";
						$html[] = 			"<div id='WooZoneLite-spin-editor-" . ( $post_id ) . "' class='WooZoneLite-spin-content-editor WooZoneLite-spinner-container'>";
						$html[] = 			htmlentities( wpautop( $reorder_content ), ENT_QUOTES, "UTF-8" );
						$html[] = 			"</div>";
						
						if( trim($reorder_content) != "" ){
							$html[] = 			"<script>WooZoneLiteContentSpinner.spin_order_interface( jQuery('#WooZoneLite-spin-editor-" . ( $post_id ) . "') );</script>";
						}
						
						$html[] = 			"<div class='WooZoneLite-spin-replacement-box'>";
						$html[] = 				"<a href='#' class='close'>&times;</a>";
						$html[] = 				"<div class='WooZoneLite-spin-box-suggest'>
													<ul class='WooZoneLite-spin-box-suggest-select'></ul>
												</div>
												
												<div class='WooZoneLite-spin-box-suggest-options'>
													<a href='#prev' class='WooZoneLite-form-button WooZoneLite-form-button-info WooZoneLite-skip-to-prev'> < prev spin word </a>
													<a href='#next' class='WooZoneLite-form-button WooZoneLite-form-button-info WooZoneLite-skip-to-next'> next spin word > </a>
												</div>
						";
						$html[] = 			"</div>";
						
						$html[] = 			"<div class='WooZoneLite-spin-options'>";
						$html[] = 				'<a href="#" class="WooZoneLite-form-button WooZoneLite-form-button-info WooZoneLite-spin-content-btn" data-prodid="' . ( $post_id ) . '">' . __('SPIN Content now!', $this->the_plugin->localizationName) . '</a>';
						$html[] = 				'
							<select class="WooZoneLite-spin-replacements" name="WooZoneLite-spin-replacements">
								<option value="10">10 replacements</option>
								<option value="30">30 replacements</option>
								<option value="60">60 replacements</option>
								<option value="80">80 replacements</option>
								<option value="100">100 replacements</option>
								<option value="0">All possible replacements</option>
							</select>
						';
						$html[] = 			"</div>";
						$html[] = 		"</div>";
						$html[] = 		"</td>";
						$html[] = 		"<td>";
						$html[] = 			"<div class='WooZoneLite-spin-content-editor WooZoneLite-spin-original-content'>";
						$html[] = 			$post_content;
						$html[] = 			"</div>";
						$html[] = 			"<div class='WooZoneLite-spin-options'>";
						$html[] = 				'<a href="#" class="WooZoneLite-form-button WooZoneLite-form-button-info WooZoneLite-save-content-btn" data-prodid="' . ( $post_id ) . '">' . __('SAVE Content', $this->the_plugin->localizationName) . '</a><a href="#" class="WooZoneLite-form-button WooZoneLite-form-button-info WooZoneLite-rollback-content-btn" data-prodid="' . ( $post_id ) . '" style="margin-left: 5px;">' . __('Rollback Content', $this->the_plugin->localizationName) . '</a>';
						$html[] =			"</div>";
						$html[] = 		"</td>";
						$html[] = 	"</tr>";
						$html[] = "</table>";
						$html[] = "</div>";
					}
 
					if( $this->opt["custom_table"] == "amz_products"){
						if( $value['td'] == '%post_id%' ){
							$html[] = '<span class="WooZoneLite-post_id">' . ( $post['post_id'] ) . '</span>';
						}
						elseif( $value['td'] == '%del_asset%' ) {
							$html[] = '<input type="checkbox" name="delete_asset" value="' . ( $post['post_id'] ) . '">';
						}
						elseif( $value['td'] == '%post_assets%' ){
							
							$in_ids = array();
							$in_ids[] = $post['post_id']; // add curent post into in array
							
							$nb_assets = array('total' => 0, 'done' => 0);
							$nb_assets['total'] = $post['nb_assets'];
							$nb_assets['done'] = $post['nb_assets_done'];
							
							// get variations 
							$variations = $this->the_plugin->db->get_results( "SELECT * FROM " . $this->the_plugin->db->prefix  . ( $this->opt["custom_table"] ) . " WHERE 1=1 AND post_parent='" . ( $post['post_id'] ) . "' AND type='variation'", ARRAY_A);
							if( $variations && count( $variations ) > 0 ){
								foreach ($variations as $_the_post ) {
									$in_ids[] = $_the_post['post_id'];
									$nb_assets['total'] += (int) $_the_post['nb_assets'];
									$nb_assets['done'] += (int) $_the_post['nb_assets_done'];
								}
							}

                            //$this->items_assets_nr['total'] += $nb_assets['total'];
                            //$this->items_assets_nr['done'] += $nb_assets['done'];
							
							// get the assets 
							$assets = $this->the_plugin->db->get_results( "SELECT * FROM " . $this->the_plugin->db->prefix . "amz_assets WHERE 1=1 AND post_id IN (" . ( implode(",", $in_ids) ) . ")", ARRAY_A);
							//var_dump('<pre>',$assets, $this->the_plugin->db,'</pre>'); die;  
 
							$html[] = '<table class="WooZoneLite-table assets-download-list" data-itemid="' . ($post_id) . '">';
							$html[] = 	'<tr>';
							$html[] = 		'<td width="540" style="vertical-align: top;height: 180px;">';
							$html[] = 			'<div class="WooZoneLite-post-title">';
							$html[] = 				'<h3 title="' . ( $post['title'] ) . '">' . ( $post['title'] ) . '</h3>';
							$html[] = 				'<table class="WooZoneLite-post-info">';
							$html[] = 					'<tr>';
							$html[] = 						'<td>' . __('Number of variation:', $this->the_plugin->localizationName) . '</td>';
							$html[] = 						'<td>' . count( $variations ) . '</td>';
							$html[] = 					'</tr>';
							$html[] = 					'<tr>';
							$html[] = 						'<td>' . __('Assets:', $this->the_plugin->localizationName) . '</td>';
							$html[] = 						'<td>' . $nb_assets['total'] . ' (' . __('new', $this->the_plugin->localizationName) . ') | ' . $nb_assets['done'] . ' (' . __('done', $this->the_plugin->localizationName) . ')</td>';
							$html[] = 					'</tr>';
							/*
							$html[] = 					'<tr>';
							$html[] = 						'<td>Product status:</td>';
							$html[] = 						'<td>' . ( get_post_field( 'post_status', $post['post_id'] ) ) . '</td>';
							$html[] = 					'</tr>';
							*/
							$html[] = 					'<tr>';
							$html[] = 						'<td colspan="2">';
							$html[] = 							'<a href="#" class="WooZoneLite-form-button-small WooZoneLite-form-button-success WooZoneLite-download-assets-btn" data-prodid="' . ( $post['post_id'] ) . '">' . __('Download assets NOW!', $this->the_plugin->localizationName) . '</a>';
							$html[] = 							'<a href="' . ( admin_url('post.php?post=' . ( $post['post_id'] ) . '&action=edit') ) . '" class="WooZoneLite-form-button-small WooZoneLite-form-button-info">' . __('Edit product', $this->the_plugin->localizationName) . '</a>';
							$html[] = 							'<a href="' . ( get_permalink( $post['post_id']) ) . '" class="WooZoneLite-form-button-small WooZoneLite-form-button-info">' . __('View product', $this->the_plugin->localizationName) . '</a>';
							$html[] = 						'</td>';
							$html[] = 					'</tr>';
							$html[] = 				'</table>';
							$html[] = 			'</div>';
							$html[] = 		'</td>';
							$html[] = 		'<td>';
							
							
							// the post assets
							$html[] = 			'<div class="WooZoneLite-post-asset">';
							$html[] = 				'<div class="WooZoneLite-post-asset-left">';
							// loop the assets
							if( $assets && count($assets) > 0 ){
								foreach ($assets as $asset) {
									
									if( $post['post_id'] == $asset['post_id'] ){  
										$html[] = 	'<div class="WooZoneLite-post-asset-preview">';
										$html[] = 		'<img src="' . ( $asset['thumb'] ) . '">';
										$html[] = 	'</div>';
									}
								}
							}
							
							$html[] = 				'</div>';
							$html[] = 			'</div>';
							
							
							// the variatios assets
							if( $variations && count( $variations ) > 0 ){
								
								$html[] = 	'<a href="#" class="WooZoneLite-show-variations">Show <em>(' . ( count( $variations ) ). ')</em> variations</a>';
								$html[] = 	'<div class="WooZoneLite-variations-list">';
								
								$html[] = 			'<div class="WooZoneLite-post-asset">';
								$html[] = 				'<h4><strong>' . __('Variations:', $this->the_plugin->localizationName) . '</strong></h4>';
								$html[] = 					'<div class="WooZoneLite-post-asset-left">';
								foreach ($variations as $variation) {
								
									// loop the assets
									if( $assets && count($assets) > 0 ){
										foreach ($assets as $asset) {
											
											if( $variation['post_id'] == $asset['post_id'] ){  
												$html[] = 	'<div class="WooZoneLite-post-asset-preview">';
												$html[] = 		'<img src="' . ( $asset['thumb'] ) . '">';
												$html[] = 	'</div>';
											}
										}
									}
									
								}
								$html[] = 				'</div>';
								$html[] = 			'</div>';
								
								$html[] = 	'</div>';
							}
							
							$html[] = 		'</td>';

							$html[] = 	'</tr>';
							$html[] = '</table>';  
						}
						
					}

					else if( $this->opt["custom_table"] == "amz_queue"){
						if( $value['td'] == '%nb_tries%' ){
							$html[] = '<span class="WooZoneLite-edit-inline">' . $post['nb_tries'] . '</span>';
							$html[] = '<div class="WooZoneLite-edit-inline-replace" data-table="amz_queue" data-field_name="nb_tries"><input type="text" name="WooZoneLite-edit-inline[nb_tries]" value="' . $post['nb_tries'] . '" /></div>';
						}
						elseif( $value['td'] == '%created_date%' ){
							$created_date = $this->the_plugin->last_update_date('true', strtotime($post['created_date']));
							$html[] = '<i>' . ( $created_date ) . '</i>';
						}
						elseif( $value['td'] == '%imported_date%' ){
							if ( !empty($post['imported_date']) && '0000-00-00 00:00:00' == $post['imported_date'] ) {
								$post['imported_date'] = '';
							}
							$imported_date = '';
							if ( !empty($post['imported_date']) ) {
								$imported_date = $this->the_plugin->last_update_date('true', strtotime($post['imported_date']));
								$html[] = '<i>' . ( $imported_date ) . '</i>';
							}
						}
						elseif( $value['td'] == '%from_op%' ) {
							$html[] = $post['from_op'];
						}
						elseif( $value['td'] == '%status%' ) {
							$status_values = array(
								'new'		=> __('New', $this->the_plugin->localizationName),
								'done'		=> __('Done (success)', $this->the_plugin->localizationName),
								'error'		=> __('Error', $this->the_plugin->localizationName),
								'already'	=> __('Already imported', $this->the_plugin->localizationName),
							);
							$status = $post['status'];
							$status_html = isset($status_values["$status"]) ? $status_values["$status"] : '';
							
							$status_msg = isset($post['status_msg']) && !empty($post['status_msg'])
								? $post['status_msg'] : $status_html;
							
							//$status_html = '<a title="' . $status_msg . '" class="WooZoneLite-tipsy WooZoneLite-column-status">' . $status_html . '</a>';
							$status_html = '<a title="' . $status_msg . '" class="WooZoneLite-tipsy WooZoneLite-column-status">' . $status_html . '</a>';
							$html[] = $status_html;
						}
						elseif( $value['td'] == '%product_links%' ) {
							if ( isset($post['product_id']) && !empty($post['product_id']) ) {
								$html[] = '<a href="' . ( get_permalink( $post['product_id'] ) ) . '" class="WooZoneLite-button gray" target="_blank">' . __('View', $this->the_plugin->localizationName) . '</a>';
								$html[] = '<a href="' . ( admin_url( 'post.php?post=' . ( $post['product_id'] ) . '&action=edit' ) ) . '" class="WooZoneLite-button blue" target="_blank">' . __('Edit', $this->the_plugin->localizationName) . '</a>';
							}
						}
					}

					else if( $this->opt["custom_table"] == "amz_search"){
						if( $value['td'] == '%nb_tries%' ){
							$html[] = '<span class="WooZoneLite-edit-inline">' . $post['nb_tries'] . '</span>';
							$html[] = '<div class="WooZoneLite-edit-inline-replace" data-table="amz_search" data-field_name="nb_tries"><input type="text" name="WooZoneLite-edit-inline[nb_tries]" value="' . $post['nb_tries'] . '" /></div>';
						}
						elseif( $value['td'] == '%search_title%' ){
							// edit inline
							$html[] = '<i class="WooZoneLite-edit-inline">' . $post['search_title'] . '</i>';
							$html[] = '<div class="WooZoneLite-edit-inline-replace" data-table="amz_search" data-field_name="search_title"><input type="text" name="WooZoneLite-edit-inline[search_title]" value="' . $post['search_title'] . '" /></div>';
						}
						elseif( $value['td'] == '%created_date%' ){
							$created_date = $this->the_plugin->last_update_date('true', strtotime($post['created_date']));
							$html[] = '<i>' . ( $created_date ) . '</i>';
						}
						elseif( $value['td'] == '%status%' ) {
							$status_values = array(
								'new'		=> __('New', $this->the_plugin->localizationName),
								'done'		=> __('Done (success)', $this->the_plugin->localizationName),
								'error'		=> __('Error', $this->the_plugin->localizationName),
							);
							$status = $post['status'];
							$status_html = isset($status_values["$status"]) ? $status_values["$status"] : '';
							
							$status_msg = isset($post['status_msg']) && !empty($post['status_msg'])
								? $post['status_msg'] : $status_html;
							
							//$status_html = '<a title="' . $status_msg . '" class="WooZoneLite-tipsy WooZoneLite-column-status">' . $status_html . '</a>';
							$status_html = '<a title="' . $status_msg . '" class="WooZoneLite-tipsy WooZoneLite-column-status">' . $status_html . '</a>';
							$html[] = $status_html;
						}
						elseif( $value['td'] == '%params_box%' ){

							$theHelper = $this->the_plugin->amzHelper;
							$recurrency = $this->moduleparams['auto_import']->recurrency;
							$countries = $this->moduleparams['auto_import']->countries;
							$main_aff_ids = $this->moduleparams['auto_import']->main_aff_ids;

							$status_html = __('View all', $this->the_plugin->localizationName);
							$status_msg = $this->moduleparams['auto_import']->show_search_params( $post['params'] );

							$fields = array(
								//'provider'		=> array(
								//	'title'			=> __('Provider', $this->the_plugin->localizationName),
								//	'value'			=> $post['provider'],
								//	'options'		=> array(),
								//),
								'recurrency'	=> array(
									'title'			=> __('Recurrency', $this->the_plugin->localizationName),
									'value'			=> $post['recurrency'],
									'options'		=> $recurrency,
								),
								'country'		=> array(
									'title'			=> __('Country', $this->the_plugin->localizationName),
									'value'			=> $post['country'],
									'options'		=> $countries,
								),
								'main_aff_id'	=> array(
									'title'			=> __('Main Aff Id', $this->the_plugin->localizationName),
									'value'			=> $post['params']['extra_params']['main_aff_id'],
									'options'		=> $main_aff_ids,
								),
								'category'		=> array(
									'title'			=> __('Category', $this->the_plugin->localizationName),
									'value'			=> '',
								),
								'keyword'		=> array(
									'title'			=> __('Keyword', $this->the_plugin->localizationName),
									'value'			=> '',
								),
								'BrowseNode'	=> array(
									'title'			=> __('Node', $this->the_plugin->localizationName),
									'value'			=> '',
								),
								'nbpages'		=> array(
									'title'			=> __('Nb Pages', $this->the_plugin->localizationName),
									'value'			=> '',
								),
								'to_category'	=> array(
									'title'			=> __('Import in', $this->the_plugin->localizationName),
									'value'			=> '',
								),
								'view_all'		=> array(
									'title'			=> '<a title="' . $status_msg . '" class="WooZoneLite-tipsy WooZoneLite-column-status">' . $status_html . '</a>',
									'value'			=> '',
								),
							);
							$__ptmp = array(
								//'main_aff_id'	 => 'extra_params',
								'category'		 => 'params',
								'keyword'		 => 'params',
								'BrowseNode'	 => 'params',
								'nbpages'		 => 'params',
								'to_category'	 => 'import_params',
							);
							foreach ($__ptmp as $param_key => $param_group) {
								if ( isset(
									$post['params'],
									$post['params']["$param_group"],
									$post['params']["$param_group"]["$param_key"]
								) ) {
									if ( isset($post['params']["$param_group"]["$param_key"]) ) {
										$fields["$param_key"]['value'] = $post['params']["$param_group"]["$param_key"];
									}
									if ( isset($post['params']["$param_group"]["_$param_key"]) ) {
										$fields["$param_key"]['value'] = $post['params']["$param_group"]["_$param_key"];
									}
								}
							}
							foreach ($fields as $field_key => $field_info) {
								$__ftmp = isset($field_info['value']) ? $field_info['value'] : '';
								$__ftmp2 = isset($field_info['options']) ? $field_info['options'] : array();

								if ( !empty($__ftmp) && isset($__ftmp2["$__ftmp"]) ) {
									$fields["$field_key"]['value'] = $__ftmp2["$__ftmp"];
								}
								if ( ('view_all' != $field_key) && empty($fields["$field_key"]['value']) ) {
									unset($fields["$field_key"]);
								}
							}

							$html[] = '<div class="WooZoneLite-ai-div2table has-padding">';

							foreach ($fields as $field_key => $field_info) {
								$field_css = '"';
								if ( 'recurrency' == $field_key ) {
									$field_css = ' WooZoneLite-edit-inline"';
								}

								$html[] = 	'<div class="WooZoneLite-ai-div2table-tr">';
								$html[] = 		'<div class="WooZoneLite-ai-div2table-td WooZoneLite-ai-left">';
								$html[] =			isset($field_info['title']) ? $field_info['title'] : '';
								$html[] = 		'</div>';
								if ( isset($field_info['value']) && !empty($field_info['value'])
									&& 'view_all' != $field_key ) {
									$html[] = 	'<div class="WooZoneLite-ai-div2table-td">&nbsp;:&nbsp;</div>';
								}
								$html[] = 		'<div class="WooZoneLite-ai-div2table-td' . $field_css . '>';
								$html[] =			isset($field_info['value']) ? $field_info['value'] : '';
								$html[] = 		'</div>';
								if ( 'recurrency' == $field_key ) {
									$html[] =	'<div class="WooZoneLite-edit-inline-replace" data-table="amz_search" data-field_name="recurrency"><select name="WooZoneLite-edit-inline[recurrency]">';
									foreach ($recurrency as $__kk => $__vv) {
										$__selected = ((string)$post['recurrency'] == (string)$__kk ? ' selected="selected"' : '');
										$html[] = '<option value="' . $__kk . '"' . $__selected . '>' . $__vv . '</option>';
									}
									$html[] =	'</select></div>';
								}
								$html[] = 	'</div>';
							}

							$html[] = '</div>';
						} // end params_box
						elseif( $value['td'] == '%info_set2%' ){

							//$status_html = __('View all', $this->the_plugin->localizationName);
							$fields = array(
								'started_at'	=> array(
									'title'			=> __('Started at', $this->the_plugin->localizationName),
									'value'			=> $post['started_at'],
								),
								'ended_at'		=> array(
									'title'			=> __('Ended at', $this->the_plugin->localizationName),
									'value'			=> $post['ended_at'],
								),
								'run_date'		=> array(
									'title'			=> __('Next run', $this->the_plugin->localizationName),
									'value'			=> $post['run_date'],
								),
							);
							foreach ($fields as $field_key => $field_info) {
								if ( !empty($field_info['value']) && '0000-00-00 00:00:00' == $field_info['value'] ) {
									$fields["$field_key"]['value'] = '';
								}
								if ( !empty($fields["$field_key"]['value']) ) {
									$fields["$field_key"]['value'] = $this->the_plugin->last_update_date('true', strtotime($fields["$field_key"]['value']));
								}
							}

							$html[] = '<div class="WooZoneLite-ai-div2table has-padding">';

							foreach ($fields as $field_key => $field_info) {
								$html[] = 	'<div class="WooZoneLite-ai-div2table-tr">';
								$html[] = 		'<div class="WooZoneLite-ai-div2table-td WooZoneLite-ai-left">';
								$html[] =			isset($field_info['title']) ? $field_info['title'] : '';
								$html[] = 		'</div>';
								if ( isset($field_info['value']) && !empty($field_info['value'])
									&& 'view_all' != $field_key ) {
									$html[] = 	'<div class="WooZoneLite-ai-div2table-td">&nbsp;:&nbsp;</div>';
								}
								$html[] = 		'<div class="WooZoneLite-ai-div2table-td">';
								$html[] =			isset($field_info['value']) ? '<i>' . $field_info['value'] . '</i>' : '';
								$html[] = 		'</div>';
								$html[] = 	'</div>';
							}

							$html[] = '</div>';
						} // end info_set2
						elseif( $value['td'] == '%info_set1%' ){

							$queue_statuses = array(
								'new'		=> __('New', $this->the_plugin->localizationName), //New
								'done'		=> __('Done', $this->the_plugin->localizationName), //Done successfully
								'error'		=> __('Error', $this->the_plugin->localizationName), //Error
								'already'	=> __('Already', $this->the_plugin->localizationName), //Already imported
							);
							$fields = array();
							foreach ($queue_statuses as $field_key => $field_info) {
								$fields["$field_key"] = array(
									'title'		=> $field_info,
									'value'		=> isset($post['queue']["$field_key"])
										? (int)$post['queue']["$field_key"] : 0,
									'options'	=> array(),
								);
							}
							foreach ($fields as $field_key => $field_info) {
								$__ftmp = isset($field_info['value']) ? $field_info['value'] : '';
								$__ftmp2 = isset($field_info['options']) ? $field_info['options'] : array();

								if ( !empty($__ftmp) && isset($__ftmp2["$__ftmp"]) ) {
									$fields["$field_key"]['value'] = $__ftmp2["$__ftmp"];
								}
							}
							foreach ($fields as $field_key => $field_info) {
								if ( empty($field_info['value']) ) {
									unset($fields["$field_key"]);
								}
							}
							
							$html[] = '<div class="WooZoneLite-ai-div2table">';

							foreach ($fields as $field_key => $field_info) {
								$field_css = 'done' == $field_key ? 'success' : $field_key;
								$html[] = 	'<div class="WooZoneLite-ai-div2table-tr WooZoneLite-ai-'.$field_css.'">';
								$html[] = 		'<div class="WooZoneLite-ai-div2table-td WooZoneLite-ai-left">';
								$html[] =			isset($field_info['title']) ? $field_info['title'] : '';
								$html[] = 		'</div>';
								if ( isset($field_info['value']) && !empty($field_info['value'])
									&& 'view_all' != $field_key ) {
									$html[] = 	'<div class="WooZoneLite-ai-div2table-td">&nbsp;:&nbsp;</div>';
								}
								$html[] = 		'<div class="WooZoneLite-ai-div2table-td">';
								$html[] =			isset($field_info['value']) ? '<strong>'.$field_info['value'].'</strong>' : '';
								$html[] = 		'</div>';
								$html[] = 	'</div>';
							}
							
							$html[] = '</div>';
						} // end info_set1
					}

					$html[] = '</td>';
				}

				$html[] = 			'</tr>';
				}
			}

			$html[] = 		'</tbody>';

			$html[] = 	'';

			$html[] = 	'</table>';

			if( $this->opt["custom_table"] == ""){

				if( isset($this->opt['mass_actions']) && ($this->opt['mass_actions'] === false) ){
					$html[] = '<div class="WooZoneLite-list-table-left-col" style="padding-top: 5px;">&nbsp;</div>';
				}elseif( isset($this->opt['mass_actions']) && is_array($this->opt['mass_actions']) && ! empty($this->opt['mass_actions']) ){
					$html[] = '<div class="WooZoneLite-list-table-left-col '. $this->opt["custom_table"] .'" style="padding-top: 5px;">&nbsp;';

					foreach ($this->opt['mass_actions'] as $key => $value){
						$html[] = 	'<input type="button" value="' . ( $value['value'] ) . '" id="WooZoneLite-' . ( $value['action'] ) . '" class="WooZoneLite-' . ( $value['action'] ) . ' WooZoneLite-button ' . ( $value['color'] ) . '">';
					}
					$html[] = '</div>';
				}else{
					$html[] = '<div class="WooZoneLite-list-table-left-col '. $this->opt["custom_table"] .'" style="padding-top: 5px;">&nbsp;';
					$html[] = '</div>';
				}
			}
			else{
				$html[] = '<div class="WooZoneLite-list-table-left-col '. $this->opt["custom_table"] .'" style="margin-bottom: 6px;">&nbsp;';
				if( $this->opt["custom_table"] == "amz_products"){
					$html[] = '<a class="WooZoneLite-form-button WooZoneLite-form-button-success WooZoneLite-download-all-assets-btn" href="#">Download ALL products assets NOW!</a>';
					$html[] = '<a class="WooZoneLite-form-button WooZoneLite-form-button-danger WooZoneLite-delete-all-assets-btn" href="#">Delete selected products assets</a>';
				}
				else {
					if( isset($this->opt['mass_actions']) && is_array($this->opt['mass_actions']) && ! empty($this->opt['mass_actions']) ){
						foreach ($this->opt['mass_actions'] as $key => $value){
							$html[] = 	'<input type="button" value="' . ( $value['value'] ) . '" id="WooZoneLite-' . ( $value['action'] ) . '" class="WooZoneLite-' . ( $value['action'] ) . ' WooZoneLite-button ' . ( $value['color'] ) . '">';
						}
					}
				}
				$html[] = '</div>';
			}

			$html[] = $this->get_pagination();

			$html[] = '</div>';

            echo implode("\n", $html);

			return $this;
		}

		public function post_statuses_filter()
		{
			$html = array();

			$availablePostStatus = $this->getAvailablePostStatus();

			$ses = isset($_SESSION['WooZoneLiteListTable'][$this->opt['id']]['params']) ? $_SESSION['WooZoneLiteListTable'][$this->opt['id']]['params'] : array();

			$curr_post_status = isset($ses['post_status']) && trim($ses['post_status']) != "" ? $ses['post_status'] : 'all';

			if( $this->opt['post_statuses'] == 'all' ){
				$postStatuses = array(
				    'all'   	=> __('All', $this->the_plugin->localizationName),
				    'publish'   => __('Published', $this->the_plugin->localizationName),
				    'future'    => __('Scheduled', $this->the_plugin->localizationName),
				    'private'   => __('Private', $this->the_plugin->localizationName),
				    'pending'   => __('Pending Review', $this->the_plugin->localizationName),
				    'draft'     => __('Draft', $this->the_plugin->localizationName),
				);
			}
			else{
				$postStatuses = $this->opt['post_statuses'];
				//die('invalid value of <i>post_statuses</i>. Only implemented value is: <i>all</i>!');
			}

			$html[] = 		'<ul class="subsubsub WooZoneLite-post_status-list">';
			$cc = 0;
			// add into _postStatus array only if have equivalent into query results
			$_postStatus = array();
			$totals = 0;
			foreach ($availablePostStatus as $key => $value){
				if( in_array($value['post_status'], array_keys($postStatuses))){
					$_postStatus[$value['post_status']] = $value['nbRow'];
					$totals = $totals + $value['nbRow'];
				}
			}

			foreach ($postStatuses as $key => $value){
				$cc++;

				if( $key == 'all' || in_array($key, array_keys($_postStatus)) ){
					$html[] = 		'<li class="ocs_post_status">';
					$html[] = 			'<a href="#post_status=' . ( $key ) . '" class="' . ( $curr_post_status == $key ? 'current' : '' ) . '" data-post_status="' . ( $key ) . '">';
					$html[] = 				$value . ' <span class="count">(' . ( ( $key == 'all' ? $totals : $_postStatus[$key] ) ) . ')</span>';
					$html[] = 			'</a>' . ( count($_postStatus) > ($cc) ? ' |' : '');
					$html[] = 		'</li>';
				}
			}

			$html[] = 		'</ul>';

			return implode("\n", $html);
		}

		public function print_html()
		{
			$html = array();

			$this->get_items();
			$items = $this->items;
  
			$html[] = '<input type="hidden" class="WooZoneLite-ajax-list-table-id" value="' . ( $this->opt['id'] ) . '" />';

            ob_start();
            // main table
            $this->print_main_table( $items );
            $main_table = ob_get_clean();
            
			// header
			if( $this->opt['show_header'] === true ) $this->print_header();

            echo $main_table;

			echo implode("\n", $html);
   
			return $this;
		}

		private function print_css_as_style( $css=array() )
		{
			$style_css = array();
			if( isset($css) && count($css) > 0 ){
				foreach ($css as $key => $value) {
					$style_css[] = $key . ": " . $value;
				}
			}

			return ( count($style_css) > 0 ? implode(";", $style_css) : '' );
		}

	
		/**
		 * Update february 2016
		 */
		private function get_filter_from_db( $field='' ) {
			if (empty($field)) return array();
			
			global $wpdb;
			
			$table = $wpdb->prefix  . $this->opt["custom_table"];
			$sql = "SELECT a.$field as __field FROM " . $table . " as a WHERE 1=1 GROUP BY a.$field ORDER BY a.$field ASC;";
		    $res = $wpdb->get_results( $sql, ARRAY_A);
		    
			$rows = array();
		    foreach ($res as $key => $vals){
		    	$id = $vals['__field'];
				$rows["$id"] = ucfirst( $id );
			}
			return $rows;
		}
	
		public function ajax_request( $retType='die', $pms=array() ) {
            $request = array(
                'action'             => isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : '',
                'ajax_id'            => isset($_REQUEST['ajax_id']) ? $_REQUEST['ajax_id'] : '',
            );
            extract($request);
			//var_dump('<pre>', $request, '</pre>'); die('debug...');

            $ret = array(
                'status'        => 'invalid',
                'html'          => '',
            );
			
			if ( in_array($action, array('publish', 'delete', 'bulk_delete')) ) {
				// maintain box html
				$_SESSION['WooZoneLiteListTable'][$request['ajax_id']]['requestFrom'] = 'ajax';
				$this->setup( $_SESSION['WooZoneLiteListTable'][$request['ajax_id']] );
			}

			$opStatus = array();
            if ( 'publish' == $action ) {
            	$opStatus = $this->__action_publish();
            }
			else if ( 'delete' == $action ) {
            	$opStatus = $this->__action_delete();
            }
			else if ( 'bulk_delete' == $action ) {
            	$opStatus = $this->__action_bulk_delete();
            }
			else if ( 'edit_inline' == $action ) {
            	$opStatus = $this->__action_edit_inline();
            }
			$ret = array_merge($ret, $opStatus);
			
			if ( in_array($action, array('publish', 'delete', 'bulk_delete')) ) {
				// create box return html
				ob_start();
				
				$_SESSION['WooZoneLiteListTable'][$request['ajax_id']]['requestFrom'] = 'ajax';
	
				$this->setup( $_SESSION['WooZoneLiteListTable'][$request['ajax_id']] );
				$this->print_html();
				$html = ob_get_contents();
				ob_clean();
				
				$ret['html'] = $html;
				$ret = array_map('utf8_encode', $ret);
			}

			if ( $retType == 'return' ) { return $ret; }
			else { die( json_encode( $ret ) ); }
		}

		public function __action_publish()
		{
			global $wpdb;

            $ret = array(
                'status'        => 'invalid',
                'msg'          => '',
            );
			
			$request = array(
				'itemid' 	=> isset($_REQUEST['itemid']) ? (int)$_REQUEST['itemid'] : 0,
			);
			
			$status = 'invalid'; $status_msg = '';
			if( $request['itemid'] > 0 ) {
				$table = $wpdb->prefix  . $this->opt["custom_table"];

				$row = $wpdb->get_row( "SELECT * FROM " . $table . " WHERE id = '" . ( $request['itemid'] ) . "'", ARRAY_A );
				
				$row_id = (int)$row['id'];

				if ($row_id>0) {
				
					// publish/unpublish
					if ( 1 ) {
						$wpdb->update( 
							$table, 
							array( 
								'publish'		=> 'Y' == $row['publish'] ? 'N' : 'Y'
							), 
							array( 'id' => $row_id ), 
							array( 
								'%s'
							), 
							array( '%d' ) 
						);
					}

					//keep page number & items number per page
					$_SESSION['WooZoneLiteListTable']['keepvar'] = array('paged' => true, 'posts_per_page' => true);
					
					$status = 'valid';
					$status_msg = 'row published successfully.';
				}
				else {
					$status_msg = 'error: ' . __FILE__ . ":" . __LINE__;
				}
			}
			else {
				$status_msg = 'error: ' . __FILE__ . ":" . __LINE__;
			}
			
			$ret = array_merge($ret, array(
				'status' 	=> $status,
				'msg'		=> $status_msg
			));
			return $ret;
		}
		
		public function __action_delete()
		{
			global $wpdb;
			
            $ret = array(
                'status'        => 'invalid',
                'msg'          => '',
            );
			
			$request = array(
				'itemid' 	=> isset($_REQUEST['itemid']) ? (int)$_REQUEST['itemid'] : 0
			);
			
			$status = 'invalid'; $status_msg = '';
			if( $request['itemid'] > 0 ) {
				$table = $wpdb->prefix  . $this->opt["custom_table"];

				$wpdb->delete( 
					$table, 
					array( 'id' => $request['itemid'] )
				);
				
				//keep page number & items number per page
				$_SESSION['WooZoneLiteListTable']['keepvar'] = array('posts_per_page' => true);
				
				$status = 'valid';
				$status_msg = 'row deleted successfully.';
			}
			else {
				$status_msg = 'error: ' . __FILE__ . ":" . __LINE__;
			}

			$ret = array_merge($ret, array(
				'status' 	=> $status,
				'msg'		=> $status_msg
			));
			return $ret;
		}
		
		public function __action_bulk_delete() {
			global $wpdb;
			
            $ret = array(
                'status'        => 'invalid',
                'msg'          => '',
            );
			
			$request = array(
				'id' 			=> isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? trim($_REQUEST['id']) : 0
			);

			if ($request['id']!=0) {
				$__rq2 = array();
				$__rq = explode(',', $request['id']);
				if (is_array($__rq) && count($__rq)>0) {
					foreach ($__rq as $k=>$v) {
						$__rq2[] = (int) $v;
					}
				} else {
					$__rq2[] = $__rq;
				}
				$request['id'] = implode(',', $__rq2);
			}
			
			$status = 'invalid'; $status_msg = '';
			if (!empty($request['id'])) {

				$table = $wpdb->prefix  . $this->opt["custom_table"];

				// delete record
				$query = "DELETE FROM " . $table . " where 1=1 and id in (" . ($request['id']) . ");";
				/*
				$query = "UPDATE " . ($table) . " set
						deleted = '1'
						where id in (" . ($request['id']) . ");";
				*/
				$__stat = $wpdb->query($query);
				
				if ($__stat!== false) {
					//keep page number & items number per page
					$_SESSION['WooZoneLiteListTable']['keepvar'] = array('posts_per_page' => true);
					
					$status = 'valid';
					$status_msg = 'bulk rows deleted successfully.';
				}
				else {
					$status_msg = 'error: ' . __FILE__ . ":" . __LINE__;
				}
			}
			else {
				$status_msg = 'error: ' . __FILE__ . ":" . __LINE__;
			}
			
			$ret = array_merge($ret, array(
				'status' 	=> $status,
				'msg'		=> $status_msg
			));
			return $ret;
		}

		public function __action_edit_inline()
		{
			global $wpdb;

            $ret = array(
                'status'        => 'invalid',
                'msg'          => '',
            );
			
			$request = array(
				'table'			=> isset($_REQUEST['table']) ? trim((string)$_REQUEST['table']) : '',
				'itemid' 		=> isset($_REQUEST['itemid']) ? (int)$_REQUEST['itemid'] : 0,
				'field_name'	=> isset($_REQUEST['field_name']) ? trim((string)$_REQUEST['field_name']) : '',
				'field_value'	=> isset($_REQUEST['field_value']) ? trim((string)$_REQUEST['field_value']) : '',
			);
			extract($request);
			
			$status = 'invalid'; $status_msg = '';
			if( $request['itemid'] > 0 ) {
				$table = $wpdb->prefix  . $table;

				if ( 1 ) {
				
					// update field
					if ( 1 ) {
						$wpdb->update(
							$table, 
							array( 
								$field_name		=> $field_value
							), 
							array( 'id' => $itemid ), 
							array( 
								'%s'
							), 
							array( '%d' ) 
						);
					}

					//keep page number & items number per page
					//$_SESSION['WooZoneLiteListTable']['keepvar'] = array('paged' => true, 'posts_per_page' => true);
					
					$status = 'valid';
					$status_msg = 'row field updated successfully.';
				}
				else {
					$status_msg = 'error: ' . __FILE__ . ":" . __LINE__;
				}
			}
			else {
				$status_msg = 'error: ' . __FILE__ . ":" . __LINE__;
			}
			
			$ret = array_merge($ret, array(
				'status' 	=> $status,
				'msg'		=> $status_msg
			));
			return $ret;
		}
	}
}