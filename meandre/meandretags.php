<?php
/*
Plugin Name: WP Meandre
Plugin URI: 
Description: Meandre Wordpress ShortTag Functionality
Author: Wes DeMoney
Version: 1.1
Author URI: http://www.infinetsoftware.com
*/

require('include.php');

// If Wordpress, Load Plugins
if (function_exists('add_action')) {
	add_action('plugins_loaded', create_function('', 'global $objWPMeandre; $objWPMeandre = new WPMeandre();' ));
}

class WPMeandre {

	function WPMeandre() {
		global $objMeandreTags;
		global $objMeandreFlow;
		$objMeandreTags = new MeandreTags();
		$objMeandreFlow = new MeandreFlow();
	}
}

class Meandre {

	var $objTagsRS;

	// Load Flow Metadata by URI Param, Return as Array of Metadata
	function LoadFlowByURI($strInURI) {
		$modelFactory = new ModelFactory();
		$model = $modelFactory->getDefaultModel();
		$model->load($this->strStore);

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
	
	// Find All Flows Containing Tag Param, Return as Array of Flow URIs
	function GetFlowsByTag($strInTag) {
		global $wpdb, $wp_query;
		$strSQL = 'SELECT FlowID, URI FROM (' . $wpdb->prefix . 'flowkeywords LEFT JOIN ' . $wpdb->prefix . 'flows ON ' . $wpdb->prefix . 'flowkeywords.FlowID = ' . $wpdb->prefix . 'flows.ID) LEFT JOIN ' . $wpdb->prefix . 'keywords ON ' . $wpdb->prefix . 'flowkeywords.KeywordID = ' . $wpdb->prefix . 'keywords.ID WHERE (' . $wpdb->prefix . 'keywords.Keyword = \'' . $strInTag . '\')';
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
			$arrTags[] = "'" . $strThisTag . "'";
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
		$strSQL = 'SELECT Keyword FROM (' . $wpdb->prefix . 'flowkeywords LEFT JOIN ' . $wpdb->prefix . 'flows ON ' . $wpdb->prefix . 'flowkeywords.FlowID = ' . $wpdb->prefix . 'flows.ID) LEFT JOIN ' . $wpdb->prefix . 'keywords ON ' . $wpdb->prefix . 'flowkeywords.KeywordID = ' . $wpdb->prefix . 'keywords.ID WHERE (' . $wpdb->prefix . 'flows.URI = \'' . $strInURI . '\')';
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
		
		$strSQL = 'SELECT meta_value FROM ' . $wpdb->postmeta . ' WHERE (meta_key = \'RepositoryURI\')';
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
		
		$strSQL = 'SELECT post_id, meta_key, meta_value FROM ' . $wpdb->postmeta . ' WHERE (meta_value = \'' . mysql_real_escape_string($strInURI) . '\')';
		$results = $wpdb->get_results($strSQL, OBJECT);
		
		if ($results) {
			$intThisID = $results[0]->post_id;
		}
		return $intThisID;
	}
	
	function FindImageByPostID($intInID) {
		global $wp_query, $wpdb;
		if (!is_numeric($intInID)) {
			return false;
		}
		
		$strSQL = 'SELECT meta_value FROM ' . $wpdb->postmeta . ' WHERE (post_id = ' . $intInID . ' AND meta_key = \'image\')';
		$results = $wpdb->get_results($strSQL, OBJECT);
		
		if ($results) {
			$strThisImage = $results[0]->meta_value;
		}
		return $strThisImage;
	}

}

class MeandreFlow extends Meandre {

	function MeandreFlow() {
		if (!function_exists('add_shortcode')) return;
		
		add_shortcode('MeandreDescribeFlow', array(&$this, 'DescribeFlow'));
		add_shortcode('MeandreListTagsByFlow', array(&$this, 'ListTagsByURI'));
		add_shortcode('MeandreListFlowsByFlowTags', array(&$this, 'RelatedFlowsByFlowTags'));
		add_shortcode('MeandreFlowImage', array(&$this, 'FlowImage'));
	}
	
	function DescribeFlow($arrParams) {
		extract(shortcode_atts(array('store' => '', 'flow' => '', 'execute' => ''), $arrParams));
		
		if (strlen($store) < 1) {
			return false;
		}
		
		if (strlen($flow) < 1) {
			return false;
		}
		
		if (strlen($execute) < 1) {
			$execute = 'http://demo.seasr.org:1714/services/execute/flow.txt?uri=' . $flow;
		}

		$this->strStore = $store;
		
		$arrFlow = $this->LoadFlowByURI($flow);
		
		if (!$arrFlow) {
			return false;
		}
		
		$strOut = '<div id="MeandreDescribeFlow">';
		$strOut .= '<div id="Name">' . $arrFlow['?name'] . '</div>';
		$strOut .= 'Posted by ' . $arrFlow['?creator'];
		$strOut .= ' on ' . date('M j Y h:i:sa', strtotime($arrFlow['?date']));
		$strOut .= '<div id="Wrap">';
		$strOut .= '<div id="Left">';
		$strOut .= '<div id="Description">' . $arrFlow['?description'] . '</div>';
		$strOut .= '<div id="Keywords"><span class="Label">Keywords:</span> ' . $this->ListTagsByURI($arrParams) . '</div>';
		$strOut .= '<div id="Execute"><input type="button" value="Execute" onClick="window.open(\'' . $execute . '\');"/></div>';
		$strOut .= '</div>';
		$strOut .= $this->FlowImage();
		$strOut .= '</div>';
		$strOut .= '<div style="clear: both; height: 1px; overflow: clip;">&nbsp;</div>';
//		$strOut .= '<div id="MoreInfo">';
//		$strOut .= '<div id="Rights"><span class="Label">Rights:</span> ' . $arrFlow['?rights'] . '</div>';
//		$strOut .= '<div id="URI"><span class="Label">URI:</span> ' . $flow . '</div>';
//		$strOut .= '<div id="Source"><span class="Label">Source:</span> ' . $store . '</div>';
//		$strOut .= '</div>';
		$strOut .= '</div>';
		
		return $strOut;
	}
	
	function FlowImage() {
		global $wp_query;
		$intPostID = $wp_query->post->ID;
		
		$strImage = $this->FindImageByPostID($intPostID);
		
		if (strlen($strImage) > 1) {
			$strOut = '<div id="FlowImage"><img src="' . $strImage . '"/></div>';
		}
		return $strOut;
	}
	
	// Write Tags Associated with a Flow by URI
	function ListTagsByURI($arrParams) {
		extract(shortcode_atts(array('store' => '', 'flow' => ''), $arrParams));
		
		if (strlen($flow) < 1) {
			return false;
		}
		
		if (strlen($store) < 1) {
			return false;
		}

		$arrTags = $this->GetTagsByFlow($flow);

		if (empty($arrTags) || !is_array($arrTags)) {
			return false;
		}
		
		$strOut = '<div id="TagsByFlow"><ul>';
		
		$intTagCount = count($arrTags);
		$intX = 1;
		
		foreach ($arrTags as $strThisTag) {
			if ($intX == $intTagCount) {
				$strOut .= '<li class="Last">';
			}
			else {
				$strOut .= '<li>';
			}
			$intX++;

			$strOut .= '<a href="' . get_option('home') . '/keyword-cloud/?Tags[]=' . urlencode($strThisTag) . '">' . htmlspecialchars($strThisTag) . '</a></li>';
		}
		$strOut .= '</ul></div>';
		
		return $strOut;
	}
	
	// Write Flows Related to a Flow by the Flow's Associated Tags
	function RelatedFlowsByFlowTags($arrParams) {
		extract(shortcode_atts(array('store' => '', 'flow' => ''), $arrParams));
		
		if (strlen($flow) < 1) {
			return false;
		}
		
		if (strlen($store) < 1) {
			return false;
		}
		
		$arrTags = $this->GetTagsByFlow($flow);
		$arrFlowWeights = array();
		
		if (!$arrTags) {
			return false;
		}
		
		foreach ($arrTags as $strThisTag) {
			$arrFlowsByTag = $this->GetFlowsByTag($strThisTag);
			foreach ($arrFlowsByTag as $strThisFlowURI) {
				if ($strThisFlowURI == $flow) {
					continue;
				}
				if (array_key_exists($strThisFlowURI, $arrFlowWeights)) {
					$arrFlowWeights[$strThisFlowURI]++;
					continue;
				}
				
				$arrFlowWeights[$strThisFlowURI] = 1;
				$arrFlows[$strThisFlowURI] = $this->LoadFlowByURI($strThisFlowURI);
			}
		}
		
		$intControl = 1;
		
		arsort($arrFlowWeights);
		
		$strOut = '<div id="MeandreListFlows"><span class="Label">Related Flows</span><ul>';
		
		foreach($arrFlowWeights as $strThisURI => $intThisWeight) {
			$arrThisFlow = $arrFlows[$strThisURI];
			$arrFlows[$arrThisFlow['?name']] = $strThisURI;
			
			$intThisPostID = $this->FindPostIDByURI($strThisURI);
				
			if (is_numeric($intThisPostID)) {
				$strThisImage = $this->FindImageByPostID($intThisPostID);
				if (strlen($strThisImage) < 1) {
					$strThisImage = 'wp-content/plugins/meandre/flow.gif';
				}
				$strThisViewFlowURI = get_option('home') . '/?p=' . $intThisPostID;
			}
			
			$strOut .= '<li><a href="' . $strThisViewFlowURI . '"><img src="' . $strThisImage . '" border="0"/><div class="MeandreListFlowTitle">' . htmlspecialchars($arrThisFlow['?name']) . '</div></a></li>';
		
			$intControl++;
			//if ($intControl == 5) {
			//	break;
			//}
		}
		
		$strOut .= '</ul></div>';
		return $strOut;
		
	}

}

class MeandreTags extends Meandre {

	var $arrTagsVal;
	var $arrTagWeights;
	var $arrTags;

	function MeandreTags() {
		if (!function_exists('add_shortcode')) return;
		
		$this->blnTagsLoaded = false;
		$this->arrTagsVal = $_GET['Tags'];
		
		add_shortcode('MeandreTagCloud', array(&$this, 'ListTags'));
		add_shortcode('MeandreListSelectedTags', array(&$this, 'ListSelectedTags'));
		add_shortcode('MeandreListFlowsByTags', array(&$this, 'SearchFlowsByTags'));
	}
	
	function Init() {
		static $blnInit;
		if ($blnInit) {
			return false;
		}
		$blnInit = true;
		
		$this->arrTags = array();
		$this->arrTags = $this->GetTags();

		if ($this->AreTagsSelected() == true) {
			$this->ParseTagsVal();
		}
		if ($this->AreTagsSelected() == true) {
			$this->LoadTagWeightsFiltered();
		}
		else {
			$this->LoadTagWeights();
		}
	}

	function AreTagsSelected() {
		if (!empty($this->arrTagsVal)) {
			return true;
		}
		else {
			return false;
		}
	}


	// Loop Selected Tags from Querystring and Remove Nulls
	function ParseTagsVal() {
		foreach($this->arrTagsVal as $strThisKey => $strThisTag) {
			if (empty($strThisTag) or strlen($strThisTag) < 1) {
				unset($this->arrTagsVal[$strThisKey]);
			}
		}
	}

	// Loop Through Flow Tags Array, Assign Weights Based on Occurances
	function LoadTagWeights() {		
		$this->arrTagWeights = array();
		foreach ($this->arrTags as $strThisTag) {
			if (array_key_exists($strThisTag, $this->arrTagWeights)) {
				$this->arrTagWeights[$strThisTag]++;
			}
			else {
				$this->arrTagWeights[$strThisTag] = 1;
			}
		}
	}


	// Assign Weights Differently Based on Filtered Tags
	function LoadTagWeightsFiltered() {
		$arrTags = array();
		$arrTagWeights = array();
		$arrFlowURIs = array();
		
		// Loop Through Selected Tags
//		foreach ($this->arrTagsVal as $strThisKey => $strThisTag) {
			
			$arrSomeTags = $this->GetFlowsByTags($this->arrTagsVal);

			// Find Flows that Match Each Tag
			foreach ($arrSomeTags as $strThisFlowURI) {
				// Ignore Duplicate Flows
				if (in_array($strThisFlowURI, $arrFlowURIs)) {
					continue;
				}
				
				// Find Tags By Each Flow and Filter Flows that Dont Have ALL the Selected Tags
				$arrThisFlowTags = $this->GetTagsByFlow($strThisFlowURI);
				foreach ($this->arrTagsVal as $strThisTag) {
					if (!in_array($strThisTag, $arrThisFlowTags)) {
						continue 2;
					}
				}
				
				// Flow Processed, Add to List of Flows
				$arrFlowURIs[] = $strThisFlowURI;
				
				// Loop Through Processed Flows Tags to Increment Tag Weights
				foreach ($arrThisFlowTags as $strThisFlowTag) {
					// Tag not Counted, Set Count at 1
					if (!in_array($strThisFlowTag, $arrTags)) {
						$arrTags[] = $strThisFlowTag;
						$this->arrTagWeights[$strThisFlowTag] = 1;
					}
					// Increment Tag Count
					else {
						$this->arrTagWeights[$strThisFlowTag]++;
					}
				}			
			}
//		}
	}


	// Write Tag Cloud
	function ListTags($arrParams) {
		extract(shortcode_atts(array('store' => ''), $arrParams));
				
		
		$this->Init();

		// Sizes are Font-Size Percentages
		$intMinSize = 100;
		$intMaxSize = 250;
		
		// Find Largest and Smallest Tag Weights
		$intMaxCount = max(array_values($this->arrTagWeights));
		$intMinCount = min(array_values($this->arrTagWeights));
		
		// Calculate Weight Spread and Use to Normalize Font-Size Increments
		$intSpread = $intMaxCount - $intMinCount;
		if ($intSpread == 0) {
			$intSpread = 1;
		}
		
		$intStep = ($intMaxSize - $intMinSize) / $intSpread;
		
		// Serialize Selected Tag String for Appending in Tag Link
		if (is_array($this->arrTagsVal)) {
			$strTagsSerial = implode('&Tags[]=', $this->arrTagsVal);
		}
		
		$strOut = '<div id="MeandreTagCloud"><ul>';
		
		// Loop Through Tag Weights Array to Write Tag Cloud
		foreach ($this->arrTagWeights as $strThisTag => $intThisCount) {
			// Ignore Outputting Already Selected Tags
			if (is_array($this->arrTagsVal)) {
				if (in_array($strThisTag, $this->arrTagsVal)) {
					continue;
				}
			}
			
			// Ignore Orphaned Tags, May Not be an Issue
			if ($intThisCount < 1) {
				continue;
			}
			
			// Determine this Font-Size % According to Normalized Increments
			$intSize = $intMinSize + (($intThisCount - $intMinCount) * $intStep);
			
			// Alternate Tag Class
			if ($blnAltTag) {
				$strClass = 'class="AltTag"';
			}
			else {
				$strClass = '';
			}
			
			$strOut .= '<li><a href="' . get_option('home') . '/keyword-cloud/?Tags[]=' . $strTagsSerial . '&Tags[]=' . $strThisTag . '" style="font-size: ' . $intSize . '%;" ' . $strClass . '>' . htmlspecialchars($strThisTag) . '</a></li>';
		
			if ($blnAltTag) { $blnAltTag = false; } else { $blnAltTag = true; }
		}
		$strOut .= '</ul></div>';
		return $strOut;
	}

	// Write Selected Tags to Page, Allow for Easy Removal of Selected Tags Filter
	function ListSelectedTags() {
		if (empty($this->arrTagsVal)) {
			return false;
		}
		
		$strOut = '<div id="MeandreListSelectedTags"><ul>';
	
		foreach($this->arrTagsVal as $strThisKey => $strThisTag) {
		
			// Generate Tag Serial to Make Link to Remove Selected Tag from Filter
			$arrTemp = $this->arrTagsVal;
			unset($arrTemp[array_search($strThisTag, $arrTemp)]);
			$strTagsSerial = implode('&Tags[]=', $arrTemp);

			
			$strOut .= '<li>' . htmlspecialchars($strThisTag) .  '&nbsp;<a href="' . get_option('home') . '/keyword-cloud/?Tags[]=' . $strTagsSerial . '"><span>[x]</span></a></li>';
		}
		$strOut .= '</ul></div>';
		return $strOut;
	}


	// Write Flows Matching All Selected Tags
	function SearchFlowsByTags($arrParams) {
		$this->Init();

		$arrFlowURIs = array();
		
		if (!is_array($this->arrTagsVal) || empty($this->arrTagsVal)) {
			return false;
		}
		
		$strOut = '<div id="MeandreListFlows"><ul>';
		
		// Loop through selected tags
		//foreach ($this->arrTagsVal as $strThisKey => $strThisTag) {
			// Find flows with this selected tag
			
			$arrSomeFlows = array();
			$arrSomeFlows = $this->GetFlowsByTags($this->arrTagsVal);
			
			foreach ($arrSomeFlows as $strThisFlowURI) {
			
				// Skip if flow is already in this index
				if (in_array($strThisFlowURI, $arrFlowURIs)) {
					continue;
				}
				
				// Make sure this flow matches all selected tags
				$arrThisFlowTags = $this->GetTagsByFlow($strThisFlowURI);
				foreach ($this->arrTagsVal as $strThisTag) {
					if (!in_array($strThisTag, $arrThisFlowTags)) {
						continue 2;
					}
				}
				
				// Add flow to index
				$arrFlowURIs[] = $strThisFlowURI;
				$arrThisFlow = $this->LoadFlowByURI($strThisFlowURI);
				
				$intThisPostID = $this->FindPostIDByURI($strThisFlowURI);
				
				if (is_numeric($intThisPostID)) {
				$strThisImage = $this->FindImageByPostID($intThisPostID);
				if (strlen($strThisImage) < 1) {
					$strThisImage = 'wp-content/plugins/meandre/flow.gif';
				}
				$strOut .= '<li><a href="' . get_option('home') . '/?p=' . $intThisPostID . '"><img src="' . $strThisImage . '" border="0"/><div class="MeandreListFlowTitle">' . htmlspecialchars($arrThisFlow['?name']) . '</div></a></li>';
				}
			}
		//}
		
		$strOut .= '</ul></div>';
		return $strOut;
	}

}

?>