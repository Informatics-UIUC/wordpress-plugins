<?php

require('functions.php');

$strURI = $_GET['URI'];

if (empty($strURI)) {
	return false;
}

ExecuteFlow();

function ExecuteFlow() {
	global $strURI;
	
	$strResponse = CurlIt($strURI, 'admin', 'admin');
	echo $strResponse;
}

?>