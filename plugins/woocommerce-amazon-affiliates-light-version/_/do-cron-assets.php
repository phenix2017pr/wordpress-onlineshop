<?php
if ( !defined('ABSPATH') ) {
	$absolute_path = __FILE__;
	$path_to_file = explode( 'wp-content', $absolute_path );
	$path_to_wp = $path_to_file[0];
 
	/** Set up WordPress environment */
	require_once( $path_to_wp.'wp-load.php' );
	require_once( $path_to_wp.'/wp-content/plugins/woozonelite/modules/assets_download/init.php' );

	@ini_set('max_execution_time', 0);
	@set_time_limit(0); // infinte
	WooZoneLiteAssetDownload_cronjob();
}