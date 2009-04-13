<?php
/*
Plugin Name: WP Meandre
Plugin URI: 
Description: Meandre Wordpress ShortTag Functionality
Author: Wes DeMoney
Version: 1.3
Author URI: http://www.infinetsoftware.com
*/

require_once('include.php');
require_once('meandre.php');
require_once('meandreflow.php');
require_once('meandretags.php');
require_once(dirname(__FILE__) . '/update.php');

// If Wordpress
if (function_exists('add_action')) {
	// Install If Needed
	register_activation_hook(__FILE__, 'WPInstallMeandre');
	
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
	
	// Update Flow Data From RDF When Published/Updated
	add_action('publish_page', 'MeandreUpdateFlow');
	add_action('publish_post', 'MeandreUpdateFlow');
	
	// Use Our Own Stylesheet
	add_action('wp_head', 'MeandreStylesheet');
}

function InitMeandreTab() {
	add_options_page('WP Quiz Lander', 'Meandre', 8, 'meandre/admintab.php');
}

function MeandreStylesheet() {
	echo '<link rel="stylesheet" type="text/css" href="' . get_option('home') . '/wp-content/plugins/meandre/styles.css' . '"/>';
}

function MeandreUpdateFlow($intInPostID) {
	$strStoreURI = get_post_meta($intInPostID, 'StoreURI', true);
	$strFlowURI = get_post_meta($intInPostID, 'FlowURI', true);

	if (strlen($strStoreURI) < 1 || strlen($strFlowURI) < 1) {
		return false;
	}
	
	// Load Flow Models and Recordset of URIs/Tags
	LoadModels($strStoreURI);
	$objTagsRS = LoadTags();
	
	if (!$objTagsRS) {
		return false;
	}

	// Flush Existing Flow URI/Tag Mappings
	ClearFlowKeywordsByFlow($strFlowURI);
	
	$objTagsRS->MoveFirst();

	// Loop URI/Tags
	while ($arrThisRow = $objTagsRS->getRow()) {
		$strThisTag = $arrThisRow['?tag'];
		$strThisFlow = $arrThisRow['?uri'];
		
		// Ignore Other Flows That May Have Been Found in This Store
		if ($strThisFlow != $strFlowURI) {
			continue;
		}
		
		// Create Flow and Tag Records as Needed
		$intFlowID = InsertFlow($strThisFlow);
		$intKeyID = InsertTag($strThisTag);
		
		// Map Flows to Tags
		if ($intFlowID && $intKeyID) {
			InsertFlowKeyword($intFlowID, $intKeyID);
		}
	}
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