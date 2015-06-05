<?php
require_once( $_SERVER['DOCUMENT_ROOT']."/lib-app/php/page_preprocessor.php" ) ;

// This is an empty page. Request to logout will be processed by the authentication
// interceptor.

// NOTE: The user will be redirected to a page defined by the configuration 
//       ExecutionContext::getLogoutPage().
?>