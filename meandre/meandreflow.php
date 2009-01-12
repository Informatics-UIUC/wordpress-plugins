<?php

class MeandreFlow extends Meandre {

	function MeandreFlow() {
		if (!function_exists('add_shortcode')) return;
		
		add_shortcode('MeandreDescribeFlow', array(&$this, 'DescribeFlow'));
		add_shortcode('MeandreListTagsByFlow', array(&$this, 'ListTagsByURI'));
		add_shortcode('MeandreListFlowsByFlowTags', array(&$this, 'RelatedFlowsByFlowTags'));
		add_shortcode('MeandreFlowImage', array(&$this, 'FlowImage'));
	}
	
	function DescribeFlow() {
		global $post;
		
		$strStoreURI = get_post_meta($post->ID, 'StoreURI', true);
		$strFlowURI = get_post_meta($post->ID, 'FlowURI', true);
		$strExecURI = get_post_meta($post->ID, 'ExecuteURI', true);
		$strMeandreServer = get_post_meta($post->ID, 'MeandreServer', true);
		
		if (strlen($strStoreURI) < 1) {
			return false;
		}
		
		if (strlen($strFlowURI) < 1) {
			return false;
		}
		
		if (strlen($strExecURI) < 1) {
			$strExecURI = get_bloginfo('home') . '/wp-content/plugins/meandre/execute.php?MeandreServer=' . urlencode($strMeandreServer) . '&FlowURI=' . urlencode($strFlowURI);
		}
		
		$arrFlow = $this->LoadFlowByURI($strFlowURI);
		
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
		$strOut .= '<div id="Keywords"><span class="Label">Keywords:</span> ' . $this->ListTagsByURI($strFlowURI) . '</div>';
		$strOut .= '<div id="Execute"><input type="button" value="Execute" onClick="window.open(\'' . $strExecURI . '\');"/></div>';
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
	function ListTagsByURI($strInFlowURI) {		
		if (strlen($strInFlowURI) < 1) {
			return false;
		}
		
		$arrTags = $this->GetTagsByFlow($strInFlowURI);

		if (empty($arrTags) || !is_array($arrTags)) {
			return false;
		}
		
		$strOut = '<div id="TagsByFlow"><ul>';
		
		$intTagCount = count($arrTags);
		$intX = 1;
		
		$strHome = get_option('home');
		
		foreach ($arrTags as $strThisTag) {
			if ($intX == $intTagCount) {
				$strOut .= '<li class="Last">';
			}
			else {
				$strOut .= '<li>';
			}
			$intX++;

			$strOut .= '<a href="' . $strHome . '/keyword-cloud/?Tags[]=' . urlencode($strThisTag) . '">' . htmlspecialchars($strThisTag) . '</a></li>';
		}
		$strOut .= '</ul></div>';
		
		return $strOut;
	}
	
	// Write Flows Related to a Flow by the Flow's Associated Tags
	function RelatedFlowsByFlowTags() {
		global $post;
		
		$strFlowURI = get_post_meta($post->ID, 'FlowURI', true);
		if (strlen($strFlowURI) < 1) {
			return false;
		}
		
		$arrTags = $this->GetTagsByFlow($strFlowURI);
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
					$strThisImage = get_bloginfo('home') . '/wp-content/plugins/meandre/flow.gif';
				}
				$strThisViewFlowURI = get_permalink($intThisPostID);
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

?>