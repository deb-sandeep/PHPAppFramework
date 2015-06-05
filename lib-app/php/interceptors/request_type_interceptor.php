<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/configs/constants.php" ) ;
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

		$requestType = REQUEST_TYPE_WEB ;
		if( PHP_SELF == API_GATEWAY_SERVICE_PATH ) {
			$requestType = REQUEST_TYPE_API ;
		}

		$this->logger->debug( "\tRequest is of type $requestType." ) ;
		ExecutionContext::setRequestType( $requestType ) ;
	}	
}

new RequestTypeInterceptor() ;

?>