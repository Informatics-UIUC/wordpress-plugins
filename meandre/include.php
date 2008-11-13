<?php

define('DBServer', 'localhost');
define('DBUser', 'meandreportal');
define('DBPassword', 'D3M0.2008');
//define('DBName', 'WPMUportal');
define('DBName', 'seasr');

$strPre = 'wp_1_';
//$strPre = 'wp_2_';

define('RDFAPI_INCLUDE_DIR', dirname(__FILE__) . '/rdfapi-php/api/');
include_once(RDFAPI_INCLUDE_DIR . 'RdfAPI.php');
require(dirname(__FILE__) . '/clsSparqlRS.php');

?>