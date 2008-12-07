<?php

set_time_limit(0);

require('include.php');
require('clsDB.php');

Init();
	
function LoadModels() {
	global $objModel;
	$arrStores = GetRepositoryURIs();
	
	if (!is_array($arrStores)) {
		return false;
	}
	
	$modelFactory = new ModelFactory();
	$model = $modelFactory->getDefaultModel();
	
	foreach ($arrStores as $strThisURI) {
		$model2 = $modelFactory->getDefaultModel();
		$model2->load($strThisURI);
		$model->addModel($model2);
		unset($model2);
	}
	
	$objModel = $model;
}

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

function GetRepositoryURIs() {
	global $objDB, $strPre;
	
	$strSQL = 'SELECT meta_value FROM ' . $strPre . 'postmeta WHERE (meta_key = \'StoreURI\')';
	$objDB->Query($strSQL);
	$intRows = mysql_num_rows($objDB->objResult);
	
	if ($intRows == 0) {
		return false;
	}
	
	$arrURIs = array();
	
	while ($arrThisRow = mysql_fetch_array($objDB->objResult)) {
		$arrURIs[] = $arrThisRow['meta_value'];
	}
	
	if (is_array($arrURIs)) {
		return array_unique($arrURIs);
	}
}

function Init() {
	global $strPre;
	
	LoadModels();
	$objTagsRS = LoadTags();
	
	if (!$objTagsRS) {
		return false;
	}

	$objTagsRS->MoveFirst();
	ClearFlowKeywords();

	while ($arrThisRow = $objTagsRS->getRow()) {
		$strThisTag = $arrThisRow['?tag'];
		$strThisFlow = $arrThisRow['?uri'];
		
		$intFlowID = InsertFlow($strThisFlow);
		$intKeyID = InsertTag($strThisTag);
		
		if ($intFlowID && $intKeyID) {
			InsertFlowKeyword($intFlowID, $intKeyID);
		}
	}
}

function InsertFlow($strInURI) {
	global $objDB, $strPre;
	$strSQL = 'SELECT ID FROM ' . $strPre . 'flows WHERE (URI = \'' . FixString($strInURI) . '\')';
	
	$objDB->Query($strSQL);
	$intRows = mysql_num_rows($objDB->objResult);
	if ($intRows == 0) {
		$strSQL = 'INSERT INTO ' . $strPre .  'flows (URI) VALUES (\'' . FixString($strInURI) . '\')';
		$objDB->Query($strSQL);
		return mysql_insert_id($objDB->objConn);
	}
	else {
		$arrRow = mysql_fetch_array($objDB->objResult);
		return $arrRow['ID'];
	}
}

function InsertTag($strInTag) {
	global $objDB, $strPre;
	$strSQL = 'SELECT ID FROM ' . $strPre . 'keywords WHERE (Keyword = \'' . FixString($strInTag) . '\')';
	
	$objDB->Query($strSQL);
	$intRows = mysql_num_rows($objDB->objResult);
	if ($intRows == 0) {
		$strSQL = 'INSERT INTO ' . $strPre .  'keywords (Keyword) VALUES (\'' . FixString($strInTag) . '\')';
		$objDB->Query($strSQL);
		return mysql_insert_id($objDB->objConn);
	}
	else {
		$arrRow = mysql_fetch_array($objDB->objResult);
		return $arrRow['ID'];
	}
}

function InsertFlowKeyword($intInFlowID, $intInKeyID) {
	global $objDB, $strPre;
	$strSQL = 'INSERT INTO ' . $strPre . 'flowkeywords (FlowID, KeywordID) VALUES (' . $intInFlowID . ',' . $intInKeyID . ')';
	$objDB->Query($strSQL);
}

function ClearFlowKeywords() {
	global $objDB, $strPre;
	$strSQL = 'DELETE * FROM ' . $strPre . 'flowkeywords';
	$objDB->Query($strSQL);
}


function FixString($strInString) {
	if (get_magic_quotes_gpc()) {
		$strInString = stripslashes($strInString);
	}
	return mysql_real_escape_string($strInString);
}

?>
Meandre database updated!