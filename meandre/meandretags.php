<?php
/*
Plugin Name: Meandre Tags
Plugin URI: 
Description: Meandre Wordpress ShortTag Functionality
Author: Wes DeMoney
Version: 1.0
Author URI: 
*/

require('include.php');


add_action('plugins_loaded', create_function('', 'global $objMeandreTags; $objMeandreTags = new MeandreTags();' ) );

class MeandreTags {

	var $arrTagsVal;
	var $arrTagWeights;
	var $objTagsRS;
	var $strStore;

	function MeandreTags() {
		if ( !function_exists('add_shortcode') ) return;
		
		$this->blnTagsLoaded = false;
		$this->arrTagsVal = $_GET['Tags'];
		
		add_shortcode('MeandreTagCloud' , array(&$this, 'ListTags') );
		add_shortcode('MeandreListSelectedTags' , array(&$this, 'ListSelectedTags') );
		add_shortcode('MeandreListFlowsByTags' , array(&$this, 'SearchFlowsByTags') );
		add_shortcode('MeandreListTagsByFlow' , array(&$this, 'ListTagsByURI') );
		add_shortcode('MeandreListFlowsByFlowTags' , array(&$this, 'RelatedFlowsByFlowTags') );
		add_shortcode('MeandreNodeBrowser', array(&$this, 'MeandreNodeBrowser'));
	}
	
	function Init() {
		// Init must be run prior to any other functionality, but should not be a constructor
		// Wordpress will construct the object whether it's used or not
		static $blnInit;
		if ($blnInit) {
			return false;
		}
		$blnInit = true;
		
		$this->LoadTags();
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
		if (empty($this->arrTagsVal)) {
			return false;
		}

		foreach($this->arrTagsVal as $strThisKey => $strThisTag) {
			if (empty($strThisTag) or strlen($strThisTag) < 1) {
				unset($this->arrTagsVal[$strThisKey]);
			}
		}
	}

	// Load all Flow Tags into Array, Keep Duplicates to Assign Weights
	function LoadTags() {
		$this->blnTagsLoaded = true;
		
		$modelFactory = new ModelFactory();
		$model = $modelFactory->getDefaultModel();
		$model->load($this->strStore);

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
		$this->objTagsRS = new SparqlRecordSet($result);
	}


	// Loop Through Flow Tags Array, Assign Weights Based on Occurances
	function LoadTagWeights() {		
		$this->objTagsRS->MoveFirst();
		$this->arrTagWeights = array();
		while ($arrThisRow = $this->objTagsRS->getRow()) {
			$strThisTag = $arrThisRow['?tag'];
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
		if (empty($this->arrTagsVal)) {
			return false;
		}
		
		$arrTags = array();
		$arrTagWeights = array();
		$arrFlowURIs = array();
		
		// Loop Through Selected Tags
		foreach ($this->arrTagsVal as $strThisKey => $strThisTag) {
			
			// Find Flows that Match Each Tag
			foreach ($this->GetFlowsByTag($strThisTag) as $strThisFlowURI) {
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
		}
	}


	// Write Tag Cloud
	function ListTags($arrParams) {
		extract(shortcode_atts(array('store' => ''), $arrParams));
		
		if (strlen($store) < 1) {
			return false;
		}
		
		$this->strStore = $store;
		$this->Init();

		if (empty($this->arrTagWeights)) {
			return false;
		}

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
		
		$blnAlt = false;
		$strOut = '<div id="MeandreTagCloud"><ul>';
		
		// Loop Through Tag Weights Array to Write Tag Cloud
		foreach ($this->arrTagWeights as $strThisTag => $intThisCount) {
			// Ignore Outputting Already Selected Tags
			if (is_array($this->arrTagsVal)) {
				if (in_array($strThisTag, $this->arrTagsVal)) {
					continue;
				}
			}
			
			// Determine this Font-Size % According to Normalized Increments
			$intSize = $intMinSize + (($intThisCount - $intMinCount) * $intStep);

			// Ignore Orphaned Tags, May Not be an Issue
			if ($intThisCount > 0) {
				if ($blnAlt == true) {
					$strAlt = 'class="AltTag"';
				}
				else {
					$strAlt = '';
				}
				$strOut .= '<li ' . $strAlt . '><a href="?Tags[]=' . $strTagsSerial . '&Tags[]=' . $strThisTag . '" style="font-size: ' . $intSize . '%">' . htmlspecialchars($strThisTag) . '</a></li>';
				
				if ($blnAlt == true) { $blnAlt = false; } else { $blnAlt = true; }
			}
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

			$strOut .= '<li><a href="' .  "" . '?Tags[]=' . $strTagsSerial . ' ">' . htmlspecialchars($strThisTag) . '</a></li>';
		}
		$strOut .= '</ul></div>';
		return $strOut;
	}


	// Write Flows Matching All Selected Tags
	function SearchFlowsByTags($arrParams) {
		extract(shortcode_atts(array('store' => ''), $arrParams));
		
		if (strlen($store) < 1) {
			return false;
		}
		$this->strStore = $store;
		$this->Init();

		$arrFlowURIs = array();
		
		if (!is_array($this->arrTagsVal)) {
			return false;
		}
		
		$strOut = '<div id="MeandreListFlows"><ul>';
		
		// Loop through selected tags
		foreach ($this->arrTagsVal as $strThisKey => $strThisTag) {
			// Find flows with this selected tag
			foreach ($this->GetFlowsByTag($strThisTag) as $strThisFlowURI) {
			
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
				$strOut .= '<li><a href="' . $_SERVER['PHP_SELF'] . '?p=' . $intThisPostID . '"><img src="' . $strThisImage . '" border="0"/><div class="MeandreListFlowTitle">' . htmlspecialchars($arrThisFlow['?name']) . '</div></a></li>';
				}
			}
		}
		
		$strOut .= '</ul></div>';
		return $strOut;
	}
	
	function MeandreNodeBrowser($arrParams) {
		extract(shortcode_atts(array('store' => '', 'tag' => '', 'flow' => ''), $arrParams));
		
		if (strlen($store) < 1) {
			return false;
		}
		
		$this->strStore = $store;
		
		if (strlen($tag) < 1 && strlen($flow) < 1) {
			return false;
		}
		
		$graphuri = 'wp-content/plugins/meandre/graph.php';
		$roameruri  = 'wp-content/plugins/meandre/RoamerDemo.swf';
		
		$strOut = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
			id="RoamerDemo" width="700" height="500"
			codebase="http://fpdownload.macromedia.com/get/flashplayer/current/swflash.cab">
			<param name="movie" value="' . $roameruri . '" />
			<param name="quality" value="high" />
			<param name="bgcolor" value="#FFFFFF" />
			<param name="allowScriptAccess" value="sameDomain" />
			<param name="flashVars" value="Store=' . $store . '&GraphURI=' . $graphuri . '&Tag=' . $tag . '&URI=' . $flow . '"/>
			<embed src="' . $roameruri . '" quality="high" bgcolor="#FFFFFF"
				width="700" height="500" name="RoamerDemo" align="middle"
				play="true"
				loop="false"
				quality="high"
				allowScriptAccess="sameDomain"
				flashVars = "Store=' . $store . '&GraphURI=' . $graphuri . '&Tag=' . $tag . '&URI=' . $flow . '"
				type="application/x-shockwave-flash"
				pluginspage="http://www.adobe.com/go/getflashplayer">
			</embed>
	</object>';
	
		echo $strOut;
	
	}

	// Find All Flows Containing Tag Param, Return as Array of Flow URIs
	function GetFlowsByTag($strInTag) {
		$this->objTagsRS->MoveFirst();
		while ($arrThisRow = $this->objTagsRS->getRow()) {
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
		$this->objTagsRS->MoveFirst();
		while ($arrThisRow = $this->objTagsRS->getRow()) {
			$strThisURI = $arrThisRow['?uri'];
			$strThisTag = $arrThisRow['?tag'];
			
			if (strcasecmp($strThisURI, $strInURI) == 0) {
				$arrTags[] = $strThisTag;
			}
		}
		return $arrTags;
	}
	
	// Write Tags Associated with a Flow by URI
	function ListTagsByURI($arrParams) {
		extract(shortcode_atts(array('store' => '', 'uri' => ''), $arrParams));
		
		if (strlen($uri) < 1) {
			return false;
		}
		
		if (strlen($store) < 1) {
			return false;
		}
		
		$this->strStore = $store;
		$this->Init();

		$arrTags = $this->GetTagsByFlow($uri);

		if (empty($arrTags) || !is_array($arrTags)) {
			return false;
		}
		
		$strOut = '<div id="MeandreListTagsByFlow"><ul>';

		foreach ($arrTags as $strThisTag) {
			$strOut .= '<li>' . htmlspecialchars($strThisTag) . '</li>';
		}
		$strOut .= '</ul></div>';
		
		return $strOut;
	}
	
	// Write Flows Related to a Flow by the Flow's Associated Tags
	function RelatedFlowsByFlowTags($arrParams) {
		extract(shortcode_atts(array('store' => '', 'uri' => ''), $arrParams));
		
		if (strlen($uri) < 1) {
			return false;
		}
		
		if (strlen($store) < 1) {
			return false;
		}
		
		$this->strStore = $store;
		$this->Init();

		$arrTags = $this->GetTagsByFlow($uri);
		$arrFlowWeights = array();
		
		if (!$arrTags) {
			return false;
		}
		
		foreach ($arrTags as $strThisTag) {
			foreach ($this->GetFlowsByTag($strThisTag) as $strThisFlowURI) {
				if ($strThisFlowURI == $uri) {
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
		
		$strOut = '<div id="MeandreListFlowsByFlowTags"><ul>';
		
		foreach($arrFlowWeights as $strThisURI => $intThisWeight) {
			$arrThisFlow = $arrFlows[$strThisURI];
			$arrFlows[$arrThisFlow['?name']] = $strThisURI;
			
			$intThisPostID = $this->FindPostIDByURI($strThisURI);
				
			if (is_numeric($intThisPostID)) {
				$strThisImage = $this->FindImageByPostID($intThisPostID);
				if (strlen($strThisImage) < 1) {
					$strThisImage = 'wp-content/plugins/meandre/flow.gif';
				}
				$strOut .= '<li><a href="?p=' . $intThisPostID . '"><img src="' . $strThisImage . '" border="0"/><div class="MeandreListFlowTitle">' . htmlspecialchars($arrThisFlow['?name']) . '</div></a></li>';
		

				$intControl++;
			}
			//if ($intControl == 5) {
			//	break;
			//}
		}
		
		$strOut .= '</ul></div>';
		return $strOut;
		
	}

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

?>
