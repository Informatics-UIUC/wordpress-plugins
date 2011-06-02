<?php

set_time_limit(0);

// Load Flows from Posts/Pages and Refresh Flow Tag Info
function LoadPostFlows() {
	global $arrFlowPosts;
	
	$arrFlowPosts = array();
	
	// Load Flow Models and Recordset of URIs/Tags
	LoadModels();
	$objTagsRS = LoadTags();
	
	if (!$objTagsRS) {
		return false;
	}

	// Flush Existing Flow URI/Tag Mappings
	ClearFlowKeywords();
	
	$objTagsRS->MoveFirst();

	// Loop URI/Tags
	while ($arrThisRow = $objTagsRS->getRow()) {
		$strThisTag = $arrThisRow['?tag'];
		$strThisFlow = $arrThisRow['?uri'];
		
		// Skip If Flow Post Doesn't Exist - For Cases Where Repositories Have Multiple Flows
		if (!FlowPostExists($strThisFlow)) {
			continue;
		}
		
		// Create Flow and Tag Records as Needed
		$intFlowID = InsertFlow($strThisFlow);
		$intKeyID = InsertTag($strThisTag);
		
		// Map Flows to Tags
		if ($intFlowID && $intKeyID) {
			InsertFlowKeyword($intFlowID, $intKeyID);
		}
		
		// Track Added Flows
		if (!in_array($strThisFlow, $arrFlowPosts)) {
			$arrFlowPosts[] = $strThisFlow;
		}
	}
}

// Find Flows In Stores From Wordpress Setting, Return Missing
function LoadOptionFlows() {
	global $arrMissingFlows, $arrFlowPosts;
	
	// Load Store URIs from Wordpress Setting
	$arrStores = GetOptionStoreURIs();
	
	if (!is_array($arrStores)) {
		return false;
	}
	
	$arrMissingFlows = array();

	// Loop Store URIs to Look for Flows
	foreach ($arrStores as $strThisStore) {
		// Find Flows in This Store
		$arrStoreFlows = FindFlowsInStore($strThisStore);
		
		if (!$arrStoreFlows) {
			continue;
		}
		
		if (!is_array($arrStoreFlows)) {
			continue;
		}
		
		// Remove Flow URIs Already in Posts
		$intFlowCount = count($arrStoreFlows);
		for ($intX = 0; $intX < $intFlowCount; $intX++) {
			$strThisFlow = $arrStoreFlows[$intX];
			if (in_array($strThisFlow, $arrFlowPosts)) {
				unset($arrStoreFlows[$intX]);
			}
		}
		
		// Track Missing Flows
		$arrMissingFlows[$strThisStore] = $arrStoreFlows;
	}
}

// Load Flow into Single Model, Run SparQL Query to Find Flows and Return as Array of Flow URIs
function FindFlowsInStore($strInStore) {
	if (empty($strInStore)) {
		return false;
	}
	
	// Load Model
	$modelFactory = new ModelFactory();
	$model = $modelFactory->getDefaultModel();
	$model->load($strInStore);
	
	$strQ = 'prefix meandre:  <http://www.meandre.org/ontology/> 
	prefix xsd:     <http://www.w3.org/2001/XMLSchema#> 
	prefix dc:      <http://purl.org/dc/elements/1.1/> 
	prefix rdfs:    <http://www.w3.org/2000/01/rdf-schema#> 
	prefix rdf:     <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	select DISTINCT ?uri
	where {
		?uri rdf:type meandre:flow_component
	}';

	// Query Model, Get Recordset of Flow URIs
	$result = $model->sparqlQuery($strQ);
	$objFlowsRS = new SparqlRecordSet($result);
	
	if (!$objFlowsRS) {
		return false;
	}

	$objFlowsRS->MoveFirst();

	// Loop Recordset and Add URIs to Array
	$arrFlows = array();
	while ($arrThisRow = $objFlowsRS->getRow()) {
		$strThisFlow = $arrThisRow['?uri'];
		$arrFlows[] = $strThisFlow;
	}
	
	return $arrFlows;
}

// Load Models From Flow Post Stores
function LoadModels($strInStore = '') {
	global $objModel;
	
	// If No Single Store URI Passed, Find All Store URIs
	if ($strInStore == '') {
		$arrStores = GetMetaStoreURIs();
	}
	else {
		$arrStores = array($strInStore);
	}
	
	if (!is_array($arrStores)) {
		return false;
	}
	
	// Load Store URIs into Models
	$modelFactory = new ModelFactory();
	$model = $modelFactory->getDefaultModel();
	
	if (!is_array($arrStores)) {
		return false;
	}
	
	// Assemble All Models into One
	foreach ($arrStores as $strThisURI) {
		$model2 = $modelFactory->getDefaultModel();
		$model2->load($strThisURI);
		$model->addModel($model2);
		unset($model2);
	}
	
	$objModel = $model;
}

// Run SparQL Query On Model and Return Recordset of URIs and Tags
function LoadTags() {
	global $objModel;
	if (!is_object($objModel)) {
		return false;
	}

	$strQ = 'prefix meandre:  <http://www.meandre.org/ontology/> 
	prefix xsd:     <http://www.w3.org/2001/XMLSchema#> 
	prefix dc:      <http://purl.org/dc/elements/1.1/> 
	prefix rdfs:    <http://www.w3.org/2000/01/rdf-schema#> 
	prefix rdf:     <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	select ?uri ?tag
	where {
		?uri rdf:type meandre:flow_component .
		?uri meandre:tag ?tag
	}';

	$result = $objModel->sparqlQuery($strQ);
	$objTagsRS = new SparqlRecordSet($result);
	return $objTagsRS;
}

// Query Post Meta and Return Array of Store URIs
function GetMetaStoreURIs() {
	global $wpdb;
	
	$strSQL = 'SELECT meta_value FROM ' . $wpdb->prefix . 'postmeta WHERE (meta_key = \'StoreURI\')';
	$results = $wpdb->get_results($strSQL, OBJECT);
	
	if (!$results) {
		return false;
	}
	
	foreach ($results as $arrThisRow) {
		$arrURIs[] = $arrThisRow->meta_value;
	}
	
	if (is_array($arrURIs)) {
		return array_unique($arrURIs);
	}
}

// Load Meandre Wordpress Option and Return Array of Store URIs
function GetOptionStoreURIs() {
	$arrOpts = get_option('MeandreOpts');
	
	if (!$arrOpts) {
		return false;
	}
	
	if (empty($arrOpts['RepositoryURIs'])) {
		return false;
	}
	
	$arrURIs = array();
	
	$arrRepositoryURIs = explode("\n", $arrOpts['RepositoryURIs']);
	
	foreach ($arrRepositoryURIs as $strThisURI) {
		$arrURIs[] = trim($strThisURI);
	}
	
	if (!empty($arrURIs)) {
		return array_unique($arrURIs);
	}
}

// Determine a Post Exists for a Flow and Return its Post ID
function FlowPostExists($strInURI) {
	global $wpdb;
	
	$strSQL = 'SELECT post_id FROM ' . $wpdb->prefix . 'postmeta WHERE (meta_key = \'FlowURI\' AND meta_value = \'' . FixString($strInURI) . '\')';
	$result = $wpdb->get_row($strSQL, OBJECT);
	
	if (!$result) {
		return false;
	}
	
	return $result->post_id;
}

// Determine if Flow ID Exists and if not, Create and Return Flow ID
function InsertFlow($strInURI) {
	global $wpdb;
	$strSQL = 'SELECT ID FROM ' . $wpdb->prefix . 'flows WHERE (URI = \'' . FixString($strInURI) . '\')';
	
	$result = $wpdb->get_row($strSQL, OBJECT);
	if (!$result) {
		$strSQL = 'INSERT INTO ' . $wpdb->prefix .  'flows (URI) VALUES (\'' . FixString($strInURI) . '\')';
		$wpdb->query($strSQL);
		return $wpdb->insert_id;
	}
	else {
		return $result->ID;
	}
}

// Determine if Tag ID Exists and if not, Create and Return Tag ID
function InsertTag($strInTag) {
	global $wpdb;
	$strSQL = 'SELECT ID FROM ' . $wpdb->prefix . 'keywords WHERE (Keyword = \'' . FixString($strInTag) . '\')';
	
	$result = $wpdb->get_row($strSQL, OBJECT);
	if (!$result) {
		$strSQL = 'INSERT INTO ' . $wpdb->prefix .  'keywords (Keyword) VALUES (\'' . FixString($strInTag) . '\')';
		$wpdb->query($strSQL);
		return $wpdb->insert_id;
	}
	else {
		return $result->ID;
	}
}

// Map FlowID to TagID
function InsertFlowKeyword($intInFlowID, $intInKeyID) {
	global $wpdb;
	$strSQL = 'INSERT INTO ' . $wpdb->prefix . 'flowkeywords (FlowID, KeywordID) VALUES (' . $intInFlowID . ',' . $intInKeyID . ')';
	$wpdb->query($strSQL);
}


// Flush FlowID/TagID Mappings to Refresh Data
function ClearFlowKeywords() {
	global $wpdb;
	$strSQL = 'DELETE FROM ' . $wpdb->prefix . 'flowkeywords';
	$wpdb->query($strSQL);
}

// Clear FlowID/TagID Mappings By FlowURI
function ClearFlowKeywordsByFlow($strInFlowURI) {
	global $wpdb;
	$strSQL = 'DELETE ' . $wpdb->prefix . 'flowkeywords FROM ' . $wpdb->prefix . 'flowkeywords FK LEFT JOIN ' . $wpdb->prefix . 'flows F ON FK.FlowID = F.ID WHERE (URI = \'' . FixString($strInFlowURI) . '\')';
	$wpdb->query($strSQL);
}

// SQL Injection Fix
function FixString($strInString) {
	if (get_magic_quotes_gpc()) {
		$strInString = stripslashes($strInString);
	}
	return mysql_real_escape_string($strInString);
}

?>
