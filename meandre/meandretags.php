<?php

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
		// Sort Alphabetically
		ksort($this->arrTagWeights);
	}


	// Write Tag Cloud
	function ListTags() {
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
				$strThisViewFlowURI = get_permalink($intThisPostID);
				$strOut .= '<li><a href="' . $strThisViewFlowURI . '"><img src="' . $strThisImage . '" border="0"/><div class="MeandreListFlowTitle">' . htmlspecialchars($arrThisFlow['?name']) . '</div></a></li>';
				}
			}
		//}
		
		$strOut .= '</ul></div>';
		return $strOut;
	}

}

?>