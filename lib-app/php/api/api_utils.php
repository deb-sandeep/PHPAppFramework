<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/api/api.php" ) ;

class APIUtils {

	static $logger ;

	const REWRITTEN_URL_PATH_KEY = "_path_" ;
	const REWRITTEN_URL_APP_KEY  = "_app_" ;

	static function createAPIRequest() {

		self::$logger->debug( "Creating API request." ) ;
		$apiRequest = new APIRequest() ;

		$apiRequest->appName = $_REQUEST[ self::REWRITTEN_URL_APP_KEY ] ;
		if( $apiRequest->appName == "" ) {
			$apiRequest->appName = "_common" ;
		}

		if( !array_key_exists( self::REWRITTEN_URL_PATH_KEY, $_REQUEST ) ) {
			throw new Exception( "API resource path missing in request." ) ;
		}
		self::$logger->debug( "Resource path = " . $_REQUEST[ self::REWRITTEN_URL_PATH_KEY ] ) ;
		$resourcePath = explode( "/", $_REQUEST[ self::REWRITTEN_URL_PATH_KEY ] ) ;

		$apiRequest->method = $_SERVER[ 'REQUEST_METHOD' ] ;
		self::$logger->debug( "Request method = " . $apiRequest->method ) ;

		$apiRequest->resourceName = $resourcePath[0] ;
		self::$logger->debug( "Resource name = " . $apiRequest->resourceName ) ;

		$apiRequest->requestPathComponents = array_splice( $resourcePath, 1 ) ;
		self::$logger->debug( "Path components:" ) ;
		foreach( $apiRequest->requestPathComponents as $pc ) {
			self::$logger->debug( "\t$pc" ) ;
		}

		self::$logger->debug( "Parameters:" ) ;
		foreach( $_REQUEST as $key => $value ) {
			if( $key != self::REWRITTEN_URL_PATH_KEY ) {
				self::$logger->debug( "\t$key = $value" ) ;
				$apiRequest->parametersMap[ $key ] = $value ;
			}
		}

		self::$logger->debug( "Request body:" ) ;
		$requestBody = file_get_contents( 'php://input' ) ;	
		self::$logger->debug( "$requestBody" ) ;

		if( $requestBody != NULL ) {
			self::$logger->debug( "Decoding request body" ) ;
			$apiRequest->requestBody = json_decode( $requestBody ) ;
			if( $apiRequest->requestBody == NULL || 
				json_last_error() !== JSON_ERROR_NONE ) {
				throw new Exception( "Request body is not JSON." ) ;
			}
		}
		return $apiRequest ;
	}

	static function writeAPIResponse( $apiResponse ) {

		self::$logger->debug( "Writing API response" ) ;

		$code = $apiResponse->responseCode ;
		self::$logger->debug( "Response code in response - $code" ) ;
		if( $code == APIResponse::SC_OK || $code == APIResponse::SC_CREATED ) {
			if( is_null( $apiResponse->responseBody ) ) {
				$code = APIResponse::SC_NO_CONTENT ;
				self::$logger->debug( "Changing response code to $code" ) ;
			}
		}

		http_response_code( $code ) ;

		if( !is_null( $apiResponse->responseBody ) ) {
			$bodyContent = "" ;
			if( is_object( $apiResponse->responseBody ) || 
				is_array( $apiResponse->responseBody )) {

				$bodyContent = json_encode( $apiResponse->responseBody, 
					                        JSON_NUMERIC_CHECK ) ;
			}
			else {
				$bodyContent = $apiResponse->responseBody ;
			}

			self::$logger->debug( "Response body - $bodyContent" ) ;
			echo $bodyContent ; 
		}
		die() ;
	}

	static function writeAPIErrorResponse( $responseCode, $message=NULL ) {

		$apiResponse = new APIResponse() ;
		$apiResponse->responseCode = $responseCode ;
		$apiResponse->responseBody = $message ;

		self::writeAPIResponse( $apiResponse ) ;
	}

	static function loadAPI( $appName, $apiName ) {

		global $API_INCLUDE_FOLDER_LIST ;
		$apiDefinitionFile = NULL ;
		$api = NULL ;

		self::$logger->debug( "Loading API $apiName for app $appName" ) ;

		$apiDefinitionFile = DOCUMENT_ROOT . "/apps/$appName/php/api/$apiName" . "API.php" ;
		self::$logger->debug( "Loading API definition file - $apiDefinitionFile" ) ;

		if( file_exists( $apiDefinitionFile ) ) {
			self::$logger->debug( "API found" ) ;
			include_once( $apiDefinitionFile ) ;
			$className = $apiName . "API" ;
			$api = new $className() ;
			if( !($api instanceof API) ) {
				throw new Exception( "API is not an instance of API class." ) ;
			}
		}
		else {
			throw new Exception( "API $apiName not found on server." ) ;
		}

		return $api ;
	}		
}

APIUtils::$logger = Logger::getLogger( "APIUtils" ) ;

?>