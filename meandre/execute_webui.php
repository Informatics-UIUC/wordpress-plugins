<?php

require('functions.php');

$strURI = $_GET['URI'];

if (empty($strURI)) {
	return false;
}

$result = GetWebUI();

if (!$result) {
  return false;
}

echo $result;

function GetWebUI() {
	global $strURI;
	
	return CurlIt($strURI, 'admin', 'admin');
}

?>
