<?php

function CurlIt($strInURI, $strInUser = '', $strInPass = '') {
        if (!function_exists('curl_exec')) {
                return false;
        }
        if (!$strInURI) {
                return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $strInURI);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        if ($strInUser) {
                curl_setopt($ch, CURLOPT_USERPWD, $strInUser . ':' . $strInPass);
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }

        $strResult = curl_exec($ch);
        $err    = curl_errno( $ch );
        $http_code  = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

        curl_close($ch);

        if ($err != 0 || ((int)($http_code / 100)) != 2) {
          return false;
        } else { 
          return $strResult;
        }
}

?>
