<?php

require_once( $_SERVER['DOCUMENT_ROOT']."/lib-app/php/page_preprocessor.php" ) ;
require_once( $_SERVER['DOCUMENT_ROOT']."/lib-app/php/api/api.php" ) ;
require_once( $_SERVER['DOCUMENT_ROOT']."/lib-app/php/api/api_utils.php" ) ;
require_once( $_SERVER['DOCUMENT_ROOT']."/lib-app/php/services/authorization_service.php" ) ;

$apiRequest = NULL ;
$apiResponse = NULL ;
$api = NULL ;

try {
	$apiRequest = APIUtils::createAPIRequest() ;
	$log->debug( "Successfully created API request." ) ;
}
catch( Exception $e ) {
	$log->error( "Error creating APIRequest." . $e->getMessage() ) ;
	APIUtils::writeAPIErrorResponse( APIResponse::SC_ERR_BAD_REQUEST, $e->getMessage() ) ;
}

try {
	$api = APIUtils::loadAPI( $apiRequest->appName, $apiRequest->resourceName ) ;
	$log->debug( "Successfully loaded API $apiRequest->resourceName" ) ;
}
catch( Exception $e ) {
	$log->error( "Error loading API." . $e->getMessage() ) ;
	APIUtils::writeAPIErrorResponse( APIResponse::SC_ERR_NOT_FOUND, $e->getMessage() ) ;	
}

try {
	$apiResponse = $api->execute( $apiRequest ) ;
}
catch( Exception $e ) {
	$log->error( "Error executing API." . $e->getMessage() ) ;
	APIUtils::writeAPIErrorResponse( APIResponse::SC_ERR_INTERNAL_SERVER_ERROR, $e->getMessage() ) ;	
}

try {
	if( $apiResponse->responseCode >= 400 ) {
		$log->warn( "API call " . $apiRequest->appName . "::" . $apiRequest->resourceName .
			        "failed with error code " . $apiResponse->responseCode ) ;
		$log->warn( "   API body " . $apiResponse->responseBody ) ;
	}
	APIUtils::writeAPIResponse( $apiResponse ) ;
}
catch( Exception $e ) {
	$log->error( "Error writing API response." . $e->getMessage() ) ;
	APIUtils::writeAPIErrorResponse( APIResponse::SC_ERR_INTERNAL_SERVER_ERROR, $e->getMessage() ) ;	
}

?>