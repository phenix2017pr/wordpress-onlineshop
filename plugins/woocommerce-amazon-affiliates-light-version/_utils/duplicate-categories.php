<?php
/**
 * HOW TO MAKE A REQUEST:
 * /wp-content/plugins/woozonelite/_utils/duplicate-categories.php
 */

if ( !defined('ABSPATH') ) {
	$absolute_path = __FILE__;
	$path_to_file = explode( 'wp-content', $absolute_path );
	$path_to_wp = $path_to_file[0];

	/** Set up WordPress environment */
	require_once( $path_to_wp.'/wp-load.php' );
} else {
	die('wrong path!');
}

		function getAmazonCategs( $req_aff_id=null )
		{
			global $WooZoneLite;
			
			$ret = array(); $retd = array();
			
			// try to read the plugin_root/assets/browsenodes.csv file
			$csv_file_content = $WooZoneLite->wp_filesystem->get_contents( $WooZoneLite->cfg['paths']['plugin_dir_path'] . 'assets/browsenodes.csv' );
			
			if( trim($csv_file_content) != "" ){
				$rows = explode("\r", $csv_file_content);
				if( count($rows) > 0 ){
					foreach ($rows as $key => $value) {
						$csv[] = explode(",", $value);
					}
				}
			}
			//var_dump('<pre>',$csv,'</pre>');

			//default
			if( count($csv[0]) > 0 ){
				foreach ($csv[0] as $key => $value) {
					if ( empty($value) ) continue 1;
					$ret["$value"] = array();
				}
			}
			
			// go through and build
			$c = 0;
			foreach ($csv as $k => $v) {
				if ( !$c ) {
					$c++; continue 1;
				}
				
				$cc = 0;
				foreach ($v as $kk => $vv) {
					if ( $cc == 0 || empty($vv) ) {
						$cc++; continue 1;
					}

					$country = $csv[0][$kk];

					if ( isset($ret["$country"]["$vv"]) && !empty($ret["$country"]["$vv"]) ) ;
					else {
						$ret["$country"]["$vv"] = array();
					}
					array_push($ret["$country"]["$vv"], $v[0]);
					$cc++;
				}
				$c++;
			}
			//var_dump('<pre>',$ret,'</pre>');
			
			// show duplicates
			$c = 0;
			foreach ($ret as $k => $v) {
				foreach ($v as $kk => $vv) {
					if ( count($vv) > 1 ) {
						$retd["$k"]["$kk"] = $vv;
					}
				}
				$c++;
			}
			var_dump('<pre>----------------------------- Duplicate categories per country: ',$retd,'</pre>'); 
			
			return $retd;
		}

$categs = $WooZoneLite->amzHelper->getAmazonCategs();
var_dump('<pre>----------------------------- Categories Name & ID: ', $categs, '</pre>');
getAmazonCategs();
die('debug...');
