<?php

class Meandre {

	var $objTagsRS;

	// Load Flow Metadata by URI Param, Return as Array of Metadata
	function LoadFlowByURI($strInURI) {
		_log("meandre: LoadFlowByURI(meandre.php): strInURI: " . $strInURI);

		$model = ModelFactory::getDefaultModel();
		
		$intFlowPostID = $this->FindPostIDByURI($strInURI);
		if (!$intFlowPostID) {
			return false;
		}
		
		$strStore = get_post_meta($intFlowPostID, 'StoreURI', true);

		_log("meandre: LoadFlowByURI(meandre.php): strStore: " . $strStore);

		$model->load($strStore);
		
		/*
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
		*/

		$q = 'SELECT ?name, ?creator, ?date, ?description, ?rights, ?tag
		WHERE ( <'. $strInURI .'>, <meandre:name>, ?name ),
		      ( <'. $strInURI .'>, <dc:creator>, ?creator ),
		      ( <'. $strInURI .'>, <dc:date>, ?date ),
		      ( <'. $strInURI .'>, <dc:rights>, ?rights ),
		      ( <'. $strInURI .'>, <dc:description>, ?description ),
		      ( <'. $strInURI .'>, <meandre:tag>, ?tag )
		USING meandre for <http://www.meandre.org/ontology/> dc for <http://purl.org/dc/elements/1.1/>';

		$result2 = $model->rdqlQuery($q);

/****  for debug:
$rdqlIter = $model->rdqlQueryasIterator($q);

//get different Result labels as array
$result_labels=$rdqlIter->getResultLabels();

_log("meandre: LoadFlowByURI(meandre.php): FlowURI: " . $strInURI);
_log("meandre: LoadFlowByURI(meandre.php): Result Label Names: ");
for ($i=0; $i <count($result_labels); $i++) _log("meandre: LoadFlowByURI(meandre.php):     label: " .  $result_labels[$i]);
_log("meandre: LoadFlowByURI(meandre.php): Number of results: " . $rdqlIter->countResults()); 
*/

		$objFlowsRS = new SparqlRecordSet($result2);

		// Result Found
		if ($objFlowsRS->RowCount() != 0) {
			$objFlowsRS->MoveFirst();
			$arrThisFlow = $objFlowsRS->getRow();
			return $arrThisFlow;
		} else 
			_log("meandre: LoadFlowByURI(meandre.php): objFlowsRS.RowCount = 0");
		
	}
	
	// Find All Flows Containing Tag Param, Return as Array of Flow URIs
	function GetFlowsByTag($strInTag) {
		global $wpdb, $wp_query;
		$strSQL = 'SELECT FlowID, URI FROM (' . $wpdb->prefix . 'flowkeywords LEFT JOIN ' . $wpdb->prefix . 'flows ON ' . $wpdb->prefix . 'flowkeywords.FlowID = ' . $wpdb->prefix . 'flows.ID) LEFT JOIN ' . $wpdb->prefix . 'keywords ON ' . $wpdb->prefix . 'flowkeywords.KeywordID = ' . $wpdb->prefix . 'keywords.ID WHERE (' . $wpdb->prefix . 'keywords.Keyword = \'' . $wpdb->escape($strInTag) . '\')';

		$results = $wpdb->get_results($strSQL, OBJECT);
		
		if (!$results) {
			return false;
		}
		
		$arrURIs = array();
		
		foreach ($results as $arrThisRow) {
			$arrURIs[] = $arrThisRow->URI;
		}

		return array_unique($arrURIs);
	}
	
	function GetFlowsByTags(&$arrInTags) {
		global $wpdb, $wp_query;
		if (!is_array($arrInTags)) {
			return false;
		}
		
		// Wrap All Tags in Single Quotes
		$arrTags = array();
		foreach ($arrInTags as $strThisTag) {
			$arrTags[] = "'" . $wpdb->escape($strThisTag) . "'";
		}
		
		$strWhere = implode(",", $arrTags);
		
		$strSQL = 'SELECT FlowID, URI FROM (' . $wpdb->prefix . 'flowkeywords LEFT JOIN ' . $wpdb->prefix . 'flows ON ' . $wpdb->prefix . 'flowkeywords.FlowID = ' . $wpdb->prefix . 'flows.ID) LEFT JOIN ' . $wpdb->prefix . 'keywords ON ' . $wpdb->prefix . 'flowkeywords.KeywordID = ' . $wpdb->prefix . 'keywords.ID WHERE (' . $wpdb->prefix . 'keywords.Keyword IN (' . $strWhere . '))';
		$results = $wpdb->get_results($strSQL, OBJECT);
		
		if (!$results) {
			return false;
		}
		
		$arrURIs = array();
		
		foreach ($results as $arrThisRow) {
			$arrURIs[] = $arrThisRow->URI;
		}
		return array_unique($arrURIs);
	}

	// Find All Tags by URI Param, Return as Array of Tags
	function GetTagsByFlow($strInURI) {
		global $wpdb, $wp_query;
		$strSQL = 'SELECT Keyword FROM (' . $wpdb->prefix . 'flowkeywords LEFT JOIN ' . $wpdb->prefix . 'flows ON ' . $wpdb->prefix . 'flowkeywords.FlowID = ' . $wpdb->prefix . 'flows.ID) LEFT JOIN ' . $wpdb->prefix . 'keywords ON ' . $wpdb->prefix . 'flowkeywords.KeywordID = ' . $wpdb->prefix . 'keywords.ID WHERE (' . $wpdb->prefix . 'flows.URI = \'' . $wpdb->escape($strInURI) . '\')';
		$results = $wpdb->get_results($strSQL, OBJECT);
		
		if (!$results) {
			return false;
		}
		
		$arrTags = array();
		
		foreach ($results as $arrThisRow) {
			$arrTags[] = $arrThisRow->Keyword;
		}
		return array_unique($arrTags);
	}
	
	// Find All Tags, Return as Array of Tags
	function GetTags() {
		global $wpdb, $wp_query;
		$strSQL = 'SELECT Keyword, URI FROM (' . $wpdb->prefix . 'flowkeywords LEFT JOIN ' . $wpdb->prefix . 'flows ON ' . $wpdb->prefix . 'flowkeywords.FlowID = ' . $wpdb->prefix . 'flows.ID) LEFT JOIN ' . $wpdb->prefix . 'keywords ON ' . $wpdb->prefix . 'flowkeywords.KeywordID = ' . $wpdb->prefix . 'keywords.ID ORDER BY Keyword ASC';
		$results = $wpdb->get_results($strSQL, OBJECT);
		
		if (!$results) {
			return false;
		}
		
		$arrTags = array();
		
		foreach ($results as $arrThisRow) {
			$arrTags[] = $arrThisRow->Keyword;
		}
		return $arrTags;
	}

	function GetRepositoryURIs() {
		global $wp_query, $wpdb;
		
		$strSQL = 'SELECT meta_value FROM ' . $wpdb->postmeta . ' WHERE (meta_key = \'StoreURI\')';
		$results = $wpdb->get_results($strSQL, OBJECT);
		
		if (!$results) {
			return false;
		}
		
		$arrURIs = array();
		
		foreach ($results as $arrThisRow) {
			$arrURIs[] = $arrThisRow->meta_value;
		}
		
		if (is_array($arrURIs)) {
			return array_unique($arrURIs);
		}
	}
	
	function FindPostIDByURI($strInURI) {
		global $wp_query, $wpdb;
		if (strlen($strInURI) < 1) {
			return false;
		}
		
		$strSQL = 'SELECT post_id FROM ' . $wpdb->postmeta . ' WHERE (meta_key = \'FlowURI\' AND meta_value = \'' . $wpdb->escape($strInURI) . '\')';
		$results = $wpdb->get_results($strSQL, OBJECT);
		
		if ($results) {
			$intThisID = $results[0]->post_id;
		}

		if (!$intThisID) _log("meandre: FindPostIDByURI(meandre.php): Could not find post meta info for " . $strInURI);

		return $intThisID;
	}
	
	function FindImageByPostID($intInID) {
		global $wp_query, $wpdb;
		if (!is_numeric($intInID)) {
			return false;
		}
		
		return get_post_meta($intInID, 'ImageURI', true);
	}

}

?>
