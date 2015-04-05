<?php

require_once( $_SERVER['DOCUMENT_ROOT'] . "/lib-app/php/configs/constants.php" ) ;
require_once( $_SERVER['DOCUMENT_ROOT'] . "/lib-app/php/configs/config.php" ) ;

// Global variables
$logger ;
$dbConn ;

$initializer_chain = array() ;
$interceptor_chain = array() ;

require_once( DOCUMENT_ROOT . "/lib-app/php/utils/execution_context.php" ) ;

// Load the initializers
require_once( DOCUMENT_ROOT . "/lib-app/php/initializers/" . "log_initializer.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/initializers/" . "session_initializer.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/initializers/" . "db_initializer.php" ) ;

// Load the interceptors
require_once( DOCUMENT_ROOT . "/lib-app/php/interceptors/" . "request_type_interceptor.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/interceptors/" . "authentication_interceptor.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/interceptors/" . "user_preference_interceptor.php" ) ;

require_once( DOCUMENT_ROOT . "/lib-app/php/api/api_invoker.php" ) ;

try {
	runInitializers() ;
	runInterceptors() ;
}
catch( Exception $e ) {
	if( ExecutionContext::isWebRequest() ) {
		include( ERROR_PAGE_PATH ) ;
		die() ;
	}
	else {
		APIInvoker::writeErrorResponse( "API exception. Message = $e" ) ;
	}
}

// =================================================================================================
function runInitializers() {
	global $initializer_chain, $logger ;

	try {
		foreach( $initializer_chain as $initializer ) {
			if( isset( $logger ) ) {
				$logger->debug( "Running initializer :: " . 
					            get_class( $initializer ) ) ;
			}
			$initializer->initialize() ;
		}
	}
	catch( Exception $e ) {
		$logger->error( "Exception during initialization. " .
			            "Message = " . $e->getMessage() ) ;
		throw $e ;
	}
	$logger->debug( "System initialized successfully" ) ;
}

function runInterceptors() {
	global $interceptor_chain, $logger ;

	try {
		foreach ( $interceptor_chain as $interceptor ) {

			$interceptorName = get_class( $interceptor ) ;

			if( $interceptor->canInterceptRequest() ) {
				$logger->debug( "Running interceptor :: $interceptorName" ) ;
				$interceptor->intercept() ;
			}
			else {
				$logger->debug( "Interceptor $interceptorName chose not to intercept" ) ;
			}
		}
	}
	catch( Exception $e ) {
		$logger->error( "Exception during interception. " . 
			            "Message = " . $e->getMessage() ) ;
		throw $e ;
	}
	$logger->debug( "Request interception successfully completed." ) ;
}

?>