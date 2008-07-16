<?php

define('DBServer', 'localhost');
define('DBUser', 'meandreportal');
define('DBPassword', 'D3M0.2008');
define('DBName', 'meandreportal');

define('RDFAPI_INCLUDE_DIR', dirname(__FILE__) . '/rdfapi-php/api/');
include(RDFAPI_INCLUDE_DIR . 'RdfAPI.php');
require(dirname(__FILE__) . '/clsSparqlRS.php');

?>