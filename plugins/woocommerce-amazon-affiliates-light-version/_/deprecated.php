<?php
if(class_exists('WooZoneLiteDeprecated') != true) {
	class WooZoneLiteDeprecated {
		public function __construct($here = __FILE__) {
		}


		private function check_if_table_exists() {
				// assets asynchronous download
			$table_name_report_assets = $this->db->prefix . "amz_assets";
					if ($this->db->get_var("show tables like '$table_name_report_assets'") != $table_name_report_assets) {
							$sql = "CREATE TABLE " . $table_name_report_assets . " (
					`id` BIGINT(15) UNSIGNED NOT NULL AUTO_INCREMENT,
					`post_id` INT(11) NOT NULL,
					`asset` VARCHAR(225) NULL DEFAULT NULL,
					`thumb` VARCHAR(225) NULL DEFAULT NULL,
					`download_status` VARCHAR(20) NULL DEFAULT 'new' COMMENT 'new, success, inprogress, error, remote',
					`hash` VARCHAR(32) NULL DEFAULT NULL,
					`media_id` INT(11) NULL DEFAULT '0',
					`msg` TEXT NULL,
					`date_added` DATETIME NULL DEFAULT NULL,
					`date_download` DATETIME NULL DEFAULT NULL,
					`image_sizes` TEXT NULL,
					PRIMARY KEY (`id`),
					INDEX `post_id` (`post_id`),
					INDEX `hash` (`hash`),
					INDEX `media_id` (`media_id`),
					INDEX `download_status` (`download_status`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
	
							require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
							dbDelta($sql);
					}
			
			$table_name_report_products = $this->db->prefix . "amz_products";
					if ($this->db->get_var("show tables like '$table_name_report_products'") != $table_name_report_products) {
							$sql = "CREATE TABLE " . $table_name_report_products . " (
					`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					`post_id` INT(11) NOT NULL,
					`post_parent` INT(11) NULL DEFAULT '0',
					`type` ENUM('post','variation') NULL DEFAULT 'post',
					`title` TEXT NULL,
					`nb_assets` INT(4) NULL DEFAULT '0',
					`nb_assets_done` INT(4) NULL DEFAULT '0',
					`status` ENUM('new','success') NULL DEFAULT 'new',
					PRIMARY KEY (`post_id`, `id`),
					UNIQUE INDEX `post_id` (`post_id`),
					INDEX `post_parent` (`post_parent`),
					INDEX `type` (`type`),
					INDEX `nb_assets` (`nb_assets`),
					INDEX `nb_assets_done` (`nb_assets_done`),
					INDEX `id` (`id`),
					INDEX `status` (`status`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
	
							require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
							dbDelta($sql);
					}
					
					// cross sell
						$table_name_cross_sell = $this->db->prefix . "amz_cross_sell";
						if ($this->db->get_var("show tables like '$table_name_cross_sell'") != $table_name_cross_sell) {
								$sql = "CREATE TABLE " . $table_name_cross_sell . " (
										`ASIN` VARCHAR(10) NOT NULL,
										`products` TEXT NULL,
										`nr_products` INT(11) NULL DEFAULT NULL,
										`add_date` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
										`is_variable` CHAR(1) NULL DEFAULT 'N',
										`nb_tries` TINYINT(1) UNSIGNED NULL DEFAULT '0',
										PRIMARY KEY (`ASIN`),
										UNIQUE INDEX `ASIN` (`ASIN`)
								) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
		
								require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
								dbDelta($sql);
						}
						
						// report logs
						$table_name_report = $this->db->prefix . "amz_report_log";
						if ($this->db->get_var("show tables like '$table_name_report'") != $table_name_report) {
								$sql = "CREATE TABLE " . $table_name_report . " (
										`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
										`log_id` VARCHAR(50) NULL DEFAULT NULL,
										`log_action` VARCHAR(50) NULL DEFAULT NULL,
										`desc` VARCHAR(255) NULL DEFAULT NULL,
										`log_data_type` VARCHAR(50) NULL DEFAULT NULL,
										`log_data` LONGTEXT NULL,
										`source` TEXT NULL,
										`date_add` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
										PRIMARY KEY (`id`),
										INDEX `log_id` (`log_id`),
										INDEX `log_action` (`log_action`)
								) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		
								require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
								dbDelta($sql);
						}
						
						// auto import - queue asins
						$table_name_queue = $this->db->prefix . "amz_queue";
						if ($this->db->get_var("show tables like '$table_name_queue'") != $table_name_queue) {
								$sql = "CREATE TABLE " . $table_name_queue . " (
					`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					`asin` VARCHAR(100) NOT NULL COLLATE 'utf8_unicode_ci',
					`status` VARCHAR(20) NOT NULL COLLATE 'utf8_unicode_ci',
					`status_msg` TEXT NOT NULL COLLATE 'utf8_unicode_ci',
					`from_op` VARCHAR(30) NOT NULL COLLATE 'utf8_unicode_ci',
					`created_date` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00',
					`imported_date` TIMESTAMP NULL DEFAULT NULL,
					`nb_tries` SMALLINT(1) UNSIGNED NOT NULL DEFAULT '0',
					`nb_tries_prev` SMALLINT(1) UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY (`id`),
					UNIQUE INDEX `asin_from_op` (`asin`, `from_op`),
					INDEX `nb_tries` (`nb_tries`),
					INDEX `from_op` (`from_op`),
					INDEX `status` (`status`)
								) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		
								require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
								dbDelta($sql);
						}

						// auto import - saved searches
						$table_name_savedsearch = $this->db->prefix . "amz_search";
						if ($this->db->get_var("show tables like '$table_name_savedsearch'") != $table_name_savedsearch) {
								$sql = "CREATE TABLE " . $table_name_savedsearch . " (
					`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					`code` VARCHAR(32) NOT NULL COLLATE 'utf8_unicode_ci',
					`publish` ENUM('Y','N') NOT NULL DEFAULT 'Y' COLLATE 'utf8_unicode_ci',
					`status` VARCHAR(20) NOT NULL COLLATE 'utf8_unicode_ci',
					`status_msg` TEXT NOT NULL COLLATE 'utf8_unicode_ci',
					`params` TEXT NOT NULL COLLATE 'utf8_unicode_ci',
					`provider` VARCHAR(20) NOT NULL COLLATE 'utf8_unicode_ci',
					`search_title` VARCHAR(100) NOT NULL COLLATE 'utf8_unicode_ci',
					`country` VARCHAR(10) NOT NULL COLLATE 'utf8_unicode_ci',
					`recurrency` VARCHAR(10) NOT NULL COLLATE 'utf8_unicode_ci',
					`created_date` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00',
					`started_at` TIMESTAMP NULL DEFAULT NULL,
					`ended_at` TIMESTAMP NULL DEFAULT NULL,
					`run_date` TIMESTAMP NULL DEFAULT NULL,
					`nb_tries` SMALLINT(1) UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY (`id`),
					UNIQUE INDEX `code` (`code`),
					INDEX `provider` (`provider`),
					INDEX `country` (`country`),
					INDEX `recurrency` (`recurrency`),
					INDEX `status` (`status`),
					INDEX `publish` (`publish`),
					INDEX `run_date` (`run_date`),
					INDEX `nb_tries` (`nb_tries`)
								) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		
								require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
								dbDelta($sql);
						}
		}
		

	}
}
die;
?>