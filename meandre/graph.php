<?php

require('include.php');

$strTagVal = $_GET['Tag'];
$strURIVal = $_GET['URI'];
$strStore = $_GET['Store'];

if (strlen($strStore) < 1) {
	return false;
}

if (!empty($strTagVal)) {
	$arrNodes[] = $strTagVal;
}

if (!empty($strURIVal)) {
	$arrNodes[] = $strURIVal;
}

LoadTags();

// Load all Flow Tags into Array, Keep Duplicates to Assign Weights
function LoadTags() {
	global $strStore, $objTagsRS;
	
	$modelFactory = new ModelFactory();
	$model = $modelFactory->getDefaultModel();
	$model->load($strStore);

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

	$result = $model->sparqlQuery($strQ);
	unset($model);
	$objTagsRS = new SparqlRecordSet($result);
}


// Write Flows Matching All Selected Tags
function SearchFlowsByTags() {
	global $strTagVal;
	static $arrFlowURIs = array();
	
	if (strlen($strTagVal) < 1) {
		return false;
	}
	
	// Find flows with this selected tag
	foreach (GetFlowsByTag($strTagVal) as $strThisFlowURI) {
		$arrFlowURIs[] = $strThisFlowURI;
		$arrThisFlow = LoadFlowByURI($strThisFlowURI);
		$arrFlows[$arrThisFlow['?name']] = $strThisFlowURI;
	}
	return $arrFlows;
}

function ListTagNodes($strInTag, $intInParentID, $intInLevel) {
	global $arrNodes, $intX;
	$arrTags  = array();
	$arrFlows = array();
	
	if (!is_array($arrNodes)) {
		$arrNodes = array();
	}

	foreach (GetFlowsByTag($strInTag) as $strThisFlowURI) {
		$arrFlows[] = $strThisFlowURI;
		foreach (GetTagsByFlow($strThisFlowURI) as $strThisTag) {
			if (!in_array($strThisTag, $arrTags) && $strThisTag != $strInTag) {
				$arrTags[] = $strThisTag;
			}
		}
	}
	
	if (empty($intX)) {
		$intX = 2;
	}
	
	foreach ($arrTags as $strThisTag) {
		if (in_array($strThisTag, $arrNodes)) {
			continue;
		}
		$arrNodes[] = $strThisTag;
		
		
?>
<Node id="n<?php echo $intX; ?>" prop="<?php echo $strThisTag; ?>" tag="<?php echo $strThisTag; ?>" type="tag"/>
<Edge fromID="n<?php echo $intX; ?>" toID="n<?php echo $intInParentID; ?>"/>
<?php
		$intX++;
	}
	
	foreach ($arrFlows as $strThisFlowURI) {
		if (in_array($strThisFlowURI, $arrNodes)) {
			continue;
		}
		$arrNodes[] = $strThisFlowURI;
		
		$arrThisFlow = LoadFlowByURI($strThisFlowURI);
?>
<Node id="n<?php echo $intX; ?>" prop="<?php echo $arrThisFlow['?name']; ?>" uri="<?php echo $strThisFlowURI; ?>" type="flow"/>
<Edge fromID="n<?php echo $intX; ?>" toID="n1"/>
<?php
		$intX++;
		if ($intInLevel < 2) {
			ListFlowNodes($strThisFlowURI, $intX, $intInLevel+1);
		}
	}
}

function ListFlowNodes($strInURI, $intInParentID, $intInLevel) {
	global $arrNodes, $intX;
	$arrThisFlow = LoadFlowByURI($strInURI);
	
	if (!is_array($arrNodes)) {
		$arrNodes = array();
	}
	
	if (empty($intX)) {
		$intX = 2;
	}

	foreach (GetTagsByFlow($strInURI) as $strThisTag) {
		if (in_array($strThisTag, $arrNodes)) {
			continue;
		}
		$arrNodes[] = $strThisTag;
?>
<Node id="n<?php echo $intX; ?>" prop="<?php echo $strThisTag; ?>" tag="<?php echo $strThisTag; ?>" type="tag"/>
<Edge fromID="n<?php echo $intX; ?>" toID="n<?php echo $intInParentID ?>"/>
<?php
		$intX++;
		if ($intInLevel < 2) {
			ListTagNodes($strThisTag, $intX, $intLevel+1);
		}
	}
}


// Find All Flows Containing Tag Param, Return as Array of Flow URIs
function GetFlowsByTag($strInTag) {
	global $objTagsRS;
	
	$objTagsRS->MoveFirst();
	while ($arrThisRow = $objTagsRS->getRow()) {
		$strThisURI = $arrThisRow['?uri'];
		$strThisTag = $arrThisRow['?tag'];
		
		if (strcasecmp($strThisTag, $strInTag) == 0) {
			$arrFlows[] = $strThisURI;
		}
	}
	return $arrFlows;
}

// Find All Tags by URI Param, Return as Array of Tags
function GetTagsByFlow($strInURI) {
	global $objTagsRS;
	
	$objTagsRS->MoveFirst();
	while ($arrThisRow = $objTagsRS->getRow()) {
		$strThisURI = $arrThisRow['?uri'];
		$strThisTag = $arrThisRow['?tag'];
		
		if (strcasecmp($strThisURI, $strInURI) == 0) {
			$arrTags[] = $strThisTag;
		}
	}
	return $arrTags;
}

// Load Flow Metadata by URI Param, Return as Array of Metadata
function LoadFlowByURI($strInURI) {
	global $strStore;
	$modelFactory = new ModelFactory();
	$model = $modelFactory->getDefaultModel();
	$model->load($strStore);

	$strQ = 'PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX meandre: <http://www.meandre.org/ontology/>
	PREFIX dc: <http://purl.org/dc/elements/1.1/>
	SELECT DISTINCT ?name ?creator ?date ?desc ?rights 
	WHERE { 
		<' . $strInURI . '> ?p ?o . 
	 <' . $strInURI . '> rdf:about meandre:flow_component . 
	<' . $strInURI . '> meandre:name ?name .
	<' . $strInURI . '> dc:creator ?creator .
	<' . $strInURI . '> dc:date ?date . 
	<' . $strInURI . '> dc:description ?desc . 
	<' . $strInURI . '> dc:rights ?rights 
	}';
	$q = 'SELECT ?name, ?creator, ?date, ?description, ?rights, ?tag
	WHERE ( <'. $strInURI .'>, <meandre:name>, ?name ),
		  ( <'. $strInURI .'>, <dc:creator>, ?creator ),
		  ( <'. $strInURI .'>, <dc:date>, ?date ),
		  ( <'. $strInURI .'>, <dc:rights>, ?rights ),
		  ( <'. $strInURI .'>, <dc:description>, ?description ),
		  ( <'. $strInURI .'>, <meandre:tag>, ?tag )
	USING meandre for <http://www.meandre.org/ontology/>
				  dc for <ihttp://purl.org/dc/elements/1.1/>';
	
	$result2 = $model->rdqlQuery($q);
	
	$objFlowsRS = new SparqlRecordSet($result2);

	// Result Found
	if ($objFlowsRS->RowCount() != 0) {
		$objFlowsRS->MoveFirst();
		$arrThisFlow = $objFlowsRS->getRow();
		return $arrThisFlow;
	}
	
}

echo '<?xml version="1.0"?>';
?>
<graph>
<?php if (!empty($strTagVal)) { ?>
<Node id="n1" prop="<?php echo $strTagVal; ?>" tag="<?php echo $strTagVal; ?>" type="tag"/>
<? ListTagNodes($strTagVal, 1, 1); ?>
<?php } else {
	$arrThisFlow = LoadFlowByURI($strURIVal);
?>
<Node id="n1" prop="<?php echo $arrThisFlow['?name']; ?>" uri="<?php echo $strURIVal; ?>" type="flow"/>
<? ListFlowNodes($strURIVal, 1, 1); ?>
<?php } ?>
</graph>