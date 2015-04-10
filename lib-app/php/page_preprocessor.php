<?php

require_once( $_SERVER['DOCUMENT_ROOT'] . "/lib-app/php/configs/constants.php" ) ;
require_once( $_SERVER['DOCUMENT_ROOT'] . "/lib-app/php/configs/config.php" ) ;

// Initialize logging
require_once( 'log4php/Logger.php' ) ;
Logger::configure( DOCUMENT_ROOT . "/lib-app/php/configs/log4php-config.xml" ) ;
$log = Logger::getLogger( PHP_SELF ) ;
$log->debug( "=============================================================" ) ;

$dbConn ;

$initializer_chain = array() ;
$interceptor_chain = array() ;

require_once( DOCUMENT_ROOT . "/lib-app/php/utils/execution_context.php" ) ;

// Load the initializers
require_once( DOCUMENT_ROOT . "/lib-app/php/initializers/" . "session_initializer.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/initializers/" . "db_initializer.php" ) ;

// Load the interceptors
require_once( DOCUMENT_ROOT . "/lib-app/php/interceptors/" . "request_type_interceptor.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/interceptors/" . "authentication_interceptor.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/interceptors/" . "user_context_interceptor.php" ) ;

require_once( DOCUMENT_ROOT . "/lib-app/php/api/api_utils.php" ) ;

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
		APIUtils::writeAPIErrorResponse( APIResponse::SC_ERR_INTERNAL_SERVER_ERROR, $e ) ;
	}
}

// =================================================================================================
function runInitializers() {
	global $initializer_chain, $log ;

	try {
		foreach( $initializer_chain as $initializer ) {
			$log->debug( "Running initializer :: " . get_class( $initializer ) ) ;
			$initializer->initialize() ;
		}
	}
	catch( Exception $e ) {
		$log->error( "Exception during initialization. " .
			         "Message = " . $e->getMessage() ) ;
		throw $e ;
	}
	$log->debug( "System initialized successfully" ) ;
}

function runInterceptors() {
	global $interceptor_chain, $log ;

	try {
		foreach ( $interceptor_chain as $interceptor ) {

			$interceptorName = get_class( $interceptor ) ;

			if( $interceptor->canInterceptRequest() ) {
				$log->debug( "Running interceptor :: $interceptorName" ) ;
				$interceptor->intercept() ;
			}
			else {
				$log->debug( "Interceptor $interceptorName chose not to intercept" ) ;
			}
		}
	}
	catch( Exception $e ) {
		$log->error( "Interception exception. Message = " . $e->getMessage() ) ;
		throw $e ;
	}
	$log->debug( "Request interception successfully completed." ) ;
}

?>