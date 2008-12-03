<?php
/*
Plugin Name: WP Meandre
Plugin URI: 
Description: Meandre Wordpress ShortTag Functionality
Author: Wes DeMoney
Version: 1.2
Author URI: http://www.infinetsoftware.com
*/

require_once('include.php');
require_once('meandre.php');
require_once('meandreflow.php');
require_once('meandretags.php');

// If Wordpress
if (function_exists('add_action')) {
	// Install If Needed
	register_activation_hook(__FILE__,'WPInstallMeandre');
	
	// Initialize Plugin
	add_action('plugins_loaded', 'InitMeandre');

	// Add Admin Tab
	add_action('admin_menu', 'InitMeandreTab');
}


function InitMeandre() {
	global $objMeandreTags;
	global $objMeandreFlow;
	$objMeandreTags = new MeandreTags();
	$objMeandreFlow = new MeandreFlow();
}

function InitMeandreTab() {
	add_options_page('Update Meandre', 'Update Meandre', 8, __FILE__, 'WriteAdminTab');
}
	
function WriteAdminTab() {
//	$strOut = '<input type="button" onClick="window.open(\'../wp-content/plugins/meandre/update.php\');" value="Update Database"/>';
	$strOut = '<iframe src="../wp-content/plugins/meandre/update.php" width="300" height="300" frameborder="0"></iframe>';
	echo $strOut;
}

function WPInstallMeandre() {
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	global $wpdb;
	
	
	if ($wpdb->get_var('show tables like \'' . $wpdb->prefix . 'flows\'') != $wpdb->prefix . 'flows') {

		$strSQL = 'CREATE TABLE ' . $wpdb->prefix . 'flowkeywords (
			FlowID int(11) NOT NULL,
			KeywordID int(11) NOT NULL
			);';
		dbDelta($strSQL);

		$strSQL = 'CREATE TABLE ' . $wpdb->prefix . 'flows (
			ID int(11) NOT NULL auto_increment,
			URI varchar(255) NOT NULL,
			PRIMARY KEY  (ID)
			);';
		dbDelta($strSQL);
		
		$strSQL = 'CREATE TABLE ' . $wpdb->prefix . 'keywords (
			ID int(11) NOT NULL auto_increment,
			Keyword varchar(50) NOT NULL,
			PRIMARY KEY  (ID)
			);';
		dbDelta($strSQL);
   }
}

?>