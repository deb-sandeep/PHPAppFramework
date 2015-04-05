<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/interceptors/interceptor.php" ) ;

class RequestTypeInterceptor extends Interceptor {

	function __construct() {
        array_push( $GLOBALS[ 'interceptor_chain' ], $this ) ;
	}

	function canInterceptRequest() {
		return true ;
	}

	function intercept() {
		global $logger ;

		$requestType = "WEB" ;
		$accept = $_SERVER[ 'HTTP_ACCEPT' ] ;
		if( $accept == "application/json" ) {
			$requestType = "API" ;
		}

		$logger->debug( "\tRequest is of type $requestType." ) ;
		ExecutionContext::setRequestType( $requestType ) ;
	}	
}

new RequestTypeInterceptor() ;

?>