<?php
if ( !defined('ABSPATH') ) {
	die;
}

/**
 * aaWoozoneKeys
 * http://www.aa-team.name
 * =======================
 *
 * @author       AA-Team
 */
if (!class_exists('aaWoozoneKeysLib')) { class aaWoozoneKeysLib {
	
	// plugin global object
	private $the_plugin = null;
	//private $amzHelper = null;
	private $P = array();


	/**
	 * Constructor
	 */
	public function __construct( $parent=null, $postArr=array() ) {
		//die('gimi');
		
		//global $WooZoneLite;
		$this->the_plugin = $parent; //$WooZoneLite;
		//$this->amzHelper = $this->the_plugin->amzHelper;
		$this->P = $postArr;
	}
	
	public function get_available_access_key( $used_keys=array() ) {
		global $wpdb;

		$table = $wpdb->prefix . 'amz_keys';
		// AND a.locked='N'
		if ( !empty($used_keys) && is_array($used_keys) ) {
			$used_keys_ = implode(',', array_map(array($this->the_plugin, 'prepareForInList'), $used_keys));
			$row = $wpdb->get_row( "SELECT a.id, a.access_key, a.secret_key FROM " . $table . " as a WHERE 1=1 AND a.publish='Y' AND a.locked='N' and a.id NOT IN ($used_keys_) ORDER BY a.id ASC;", ARRAY_A );			
		}
		else {
			$row = $wpdb->get_row( "SELECT a.id, a.access_key, a.secret_key FROM " . $table . " as a WHERE 1=1 AND a.publish='Y' AND a.locked='N' ORDER BY a.id ASC;", ARRAY_A );
		}
		//$row_id = (int)$row['id'];
		return $row;
	}

	public function set_current_access_key_status( $id=0, $params=array(), $params_format=array() ) {
		global $wpdb;

		$table = $wpdb->prefix . 'amz_keys';
		$ret = $wpdb->update( 
			$table, 
			$params, 
			array( 'id' => $id ), 
			$params_format, 
			array( '%d' ) 
		);
		return $ret;
	}
	public function lock_current_access_key( $id=0 ) {
		//return $this->set_current_access_key_status( $id, array('locked' => 'Y'), array('%s') );
		global $wpdb;

		$table = $wpdb->prefix . 'amz_keys';
		$q = "UPDATE $table as a SET a.locked = 'Y', a.lock_time = NOW() WHERE 1=1 and a.id = %s;";
		$q = $wpdb->prepare( $q, $id );
		$res = $wpdb->query( $q );
		return $res;
	}
	public function unlock_current_access_key( $id=0, $pms=array() ) {
		//return $this->set_current_access_key_status( $id, array('locked' => 'N'), array('%s') );
		global $wpdb;

		$table = $wpdb->prefix . 'amz_keys';
		//$q = "UPDATE $table as a SET a.locked = 'N', a.lock_time = NOW() WHERE 1=1 and a.id = %s;";
		$qpart = array();
		$qpart[] = "UPDATE $table as a SET a.lock_time = NOW(), a.locked = 'N'";
		if ( !empty($pms) ) {
			foreach ($pms as $key => $val) {
				switch ($key) {
					case 'last_request_id':
						$qpart[] = ", a.$key = '$val'";
						break;
						
					case 'last_request_time':
						$qpart[] = ", a.$key = NOW()";
						break;
						
					case 'nb_requests':
						$qpart[] = ", a.$key = a.$key + 1";
						break;
				}
			}
		}
		$qpart[] = "WHERE 1=1 and a.id = %s;";
		$q = implode(' ', $qpart);
		$q = $wpdb->prepare( $q, $id );
		$res = $wpdb->query( $q );
		//return $q;
		//return $res;
		return '';
	}
	
	public function save_amazon_request( $pms=array() ) {
		global $wpdb;
		
		$pms = array_merge( array(
			'plugin_alias'			=> $this->the_plugin->alias,
			'id_amz_keys' 		=> 0,
			'request_params' 	=> isset($this->P) ? serialize( $this->P ) : '',
			'country' 			=> isset($this->P['__request']['country']) ? $this->P['__request']['country'] : '',
			'from_file' 		=> isset($this->P['from_file']) ? $this->P['from_file'] : '',
			'from_func' 		=> isset($this->P['from_func']) ? $this->P['from_func'] : '',
			'client_ip' 		=> isset($this->P['__request']['client_ip']) ? $this->P['__request']['client_ip'] : '',
			'client_website' 	=> isset($this->P['__request']['client_website']) ? $this->P['__request']['client_website'] : '',
			'status' 			=> '',
			'status_msg' 		=> '',
		), $pms );
		
		$table = $wpdb->prefix . 'amz_keys_req';
		$insert_id = $this->the_plugin->db_custom_insert(
			$table,
			array(
				'values' => array(
					'plugin_alias'			=> $pms['plugin_alias'],
					'id_amz_keys' 		=> $pms['id_amz_keys'],
					'request_params' 	=> serialize( $pms['request_params'] ),
					'country' 			=> $pms['country'],
					'from_file' 		=> $pms['from_file'],
					'from_func' 		=> $pms['from_func'],
					'client_ip' 		=> $pms['client_ip'],
					'client_website' 	=> $pms['client_website'],
					'status' 			=> $pms['status'],
					'status_msg' 		=> 'valid' != $pms['status'] ? $pms['status_msg'] : '',
				),
				'format' => array(
					'%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
				)
			),
			true // use <insert ignore>
		);
		return $insert_id;
	}

	// interval_sec = maximum interval in seconds after which a key is considered blocked and need to be reseted 
	public function reset_blocked_keys( $interval_sec=45 ) {
		global $wpdb;
		
		$ret = array(
			'status'		=> 'invalid',
			'msg'			=> '',
			'body'			=> '',
		);

		$table = $wpdb->prefix . 'amz_keys';

		$q = "SELECT a.id FROM " . $table . " as a WHERE 1=1 AND a.publish='Y' AND a.locked='Y' AND ( NOW() >= date_add( date_format( a.lock_time, '%Y-%m-%d %H:%i:%s' ), interval $interval_sec second ) ) ORDER BY a.id ASC;";
		//var_dump('<pre>', $q, '</pre>'); die('debug...'); 
		$rows = $wpdb->get_results( $q, ARRAY_A );
		if ( empty($rows) ) {
			return array_merge($ret, array('msg' => 'There are no keys blocked.'));
		}
		
		$rows_ids = array_column( $rows, 'id' );
		$rows_ids_ = implode(',', array_map(array($this->the_plugin, 'prepareForInList'), $rows_ids));
		
		$q = "UPDATE $table as a SET a.locked = 'N' WHERE 1=1 and a.id IN ($rows_ids_);";
		//var_dump('<pre>', $q, '</pre>'); die('debug...'); 
		$res = $wpdb->query( $q );

		return array_merge($ret, array('status' => 'valid', 'msg' => 'The following key IDs were updated: ' . implode(', ', $rows_ids)));
	}
	
} }

?>