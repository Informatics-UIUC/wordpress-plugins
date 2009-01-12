<?php

require('functions.php');

$strURI = $_GET['URI'];

if (empty($strURI)) {
	return false;
}

GetWebUI();

function GetWebUI() {
	global $strURI;
	
	$strResponse = CurlIt($strURI, 'admin', 'admin');

	if ($strResponse) {
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
		header('Location: http://' . $strWebUIHost . ':' . $intWebUIPort);
		return false;
	}

}

?>
<html><head><script language="Javascript">window.setTimeout("location.reload()", 3000);</script></head><body>Loading...</body></html>