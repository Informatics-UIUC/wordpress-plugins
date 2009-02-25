<?php

require('functions.php');

$strURI = $_GET['URI'];

if (empty($strURI)) {
	return false;
}

$strResp = GetWebUI();

if ($strResp) {
	echo $strResp;
}

function GetWebUI() {
	global $strURI;
	
	$strResponse = CurlIt($strURI, 'admin', 'admin');

	if ($strResponse) {
		return $strResponse;
		$arrLines = explode("\n", $strResponse);
		foreach ($arrLines as $strThisLine) {
			$arrThisPair = explode(' = ', $strThisLine);
			$strThisVar = trim($arrThisPair[0]);
			$strThisVal = trim($arrThisPair[1]);
			
			if ($strThisVar == 'hostname') {
				$strWebUIHost = $strThisVal;
			}
			if ($strThisVar == 'port') {
				$intWebUIPort = $strThisVal;
			}
		}
		if ($strWebUIHost != 'localhost') {
			return 'http://' . $strWebUIHost . ':' . $intWebUIPort;
		}
	}

	return false;
}

?>