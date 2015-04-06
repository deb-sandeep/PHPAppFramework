<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/interceptors/interceptor.php" ) ;

class RequestTypeInterceptor extends Interceptor {

	private $logger ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
        array_push( $GLOBALS[ 'interceptor_chain' ], $this ) ;
	}

	function canInterceptRequest() {
		return true ;
	}

	function intercept() {

		$requestType = "WEB" ;
		$accept = $_SERVER[ 'HTTP_ACCEPT' ] ;
		if( $accept == "application/json" ) {
			$requestType = "API" ;
		}

		$this->logger->debug( "\tRequest is of type $requestType." ) ;
		ExecutionContext::setRequestType( $requestType ) ;
	}	
}

new RequestTypeInterceptor() ;

?>