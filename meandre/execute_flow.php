<?php

require('functions.php');

$strURI = $_GET['URI'];

if (empty($strURI)) {
	return false;
}

$result = ExecuteFlow();

if (!$result) {
  return false;
}

echo $result;

function ExecuteFlow() {
	global $strURI;
	
	return CurlIt($strURI, 'admin', 'admin');
}

?>
