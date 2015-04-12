<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/interceptors/interceptor.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/utils/server_context.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/utils/execution_context.php" ) ;

class LandingPageInterceptor extends Interceptor {

	private $logger ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
        array_push( $GLOBALS[ 'interceptor_chain' ], $this ) ;
	}

	function canInterceptRequest() {

		if( ExecutionContext::isWebRequest() ) {
			$this->logger->debug( "Current requested page is " . PHP_SELF ) ;
			if( PHP_SELF == "/index.php" ) {
				return true ;
			}
		}
		return false ;
	}

	function intercept() {

		$user = ExecutionContext::getCurrentUser() ;
		$defaultAppName = $user->getPreference( UPK_DEFAULT_APP_NAME ) ;

		$this->logger->debug( "Default application is $defaultAppName" ) ;
		
		$defaultLandingPage = ServerContext::getDefaultLandingPage( $defaultAppName ) ;
		$this->logger->debug( "Default LP $defaultLandingPage" ) ;

		include( DOCUMENT_ROOT . $defaultLandingPage ) ;
		exit() ;
	}	
}

new LandingPageInterceptor() ;

?>