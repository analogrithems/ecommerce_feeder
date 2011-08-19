<?php

function installDBTable(){
	global $wpdb;
	$table_name = $wpdb->prefix . 'data_feeder';
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		  `id` int(5) NOT NULL AUTO_INCREMENT,
		  `name` varchar(255) NOT NULL,
		  `object` varchar(255) NOT NULL,
		  `dbuser` varchar(255) DEFAULT NULL COMMENT 'password for connection method',
		  `dbpassword` varchar(255) DEFAULT NULL COMMENT 'password for connection type',
		  `type` varchar(255) DEFAULT NULL COMMENT 'The source, xml,csv,sql',
		  `db_driver` varchar(255) DEFAULT NULL COMMENT 'The Database Driver',
		  `dbhost` varchar(255) DEFAULT NULL COMMENT 'The server to connect to ',
		  `dbname` varchar(255) DEFAULT NULL COMMENT 'The database name',
		  `source` mediumtext,
		  `schedule` varchar(25) DEFAULT NULL,
		  `direction` enum('import','export') NOT NULL,
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `name` (`name`)
		) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}

}

?>
