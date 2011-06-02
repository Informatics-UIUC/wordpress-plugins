<?php

define('RDFAPI_INCLUDE_DIR', dirname(__FILE__) . '/rdfapi-php/api/');
include_once(RDFAPI_INCLUDE_DIR . 'RdfAPI.php');
require(dirname(__FILE__) . '/clsSparqlRS.php');

if(!function_exists('_log')){
  function _log( $message ) {
    if( WP_DEBUG === true ){
      if( is_array( $message ) || is_object( $message ) ){
        error_log( print_r( $message, true ) );
      } else {
        error_log( $message );
      }
    }
  }
}
?>
