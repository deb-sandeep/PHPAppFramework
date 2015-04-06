<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/utils/http_utils.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/api/api.php" ) ;

class APIInvoker {

	const SERIALIZED_REQUEST_KEY = "apiRequest" ;

	const PROCESSING_RESULT_ERROR   = "ERROR" ;
	const PROCESSING_RESULT_SUCCESS = "SUCCESS" ;

	private $logger ;

	private $apiName = NULL ;
	private $requestPayload = NULL ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
	}

	public function handleRequest() {

		try {
			$this->validateRequest() ;
			$api = $this->loadAPI() ;

			$api->setRequestPayload( $this->requestPayload ) ;
			$api->preExecute() ;
			$api->execute() ;
			$api->postExecute() ;

			$executionStatus    = $api->getExecutionStatus() ;
			$executionStatusMsg = $api->getExecutionStatusMessage() ;
			$responsePayload    = NULL ;
			if( $executionStatus == self::PROCESSING_RESULT_SUCCESS ) {
				$responsePayload = $api->getResponse() ;
			}

			self::writeResponse( $executionStatus, $executionStatusMsg, 
				                 $responsePayload ) ;
		}
		catch( Exception $e ) {
			$this->logger->error( "Exception while handling API request. $e" ) ;
			self::writeErrorResponse( $e ) ;
		}
	}

	private function validateRequest() {

		$this->logger->debug( "Validating API request" ) ;

		$serializedRequest = HTTPUtils::getRequestParameterValue(
								                self::SERIALIZED_REQUEST_KEY ) ;
		if( $serializedRequest == NULL ) {
			throw new Exception( self::SERIALIZED_REQUEST_KEY . 
				                 " parameter not found in API request" ) ;
		}
		else {
			$this->logger->debug( "API serialized request = $serializedRequest" ) ;
		}

		$request = json_decode( $serializedRequest ) ;
		if( json_last_error() != JSON_ERROR_NONE ) {
			throw new Exception( "Invalid JSON received. Error code = " . 
				                 json_last_error() ) ;
		}

		if( !isset( $request->{ 'apiName' } ) ) {
			throw new Exception( "apiName not set in request." ) ;
		}
		else {
			$this->apiName = $request->{ 'apiName' } ;
			$this->logger->debug( "API to invoke = $this->apiName" ) ;
		}

		if( isset( $request->{ 'payload' } ) ) {
			$this->requestPayload = $request->{ 'payload' } ;
		}
		else {
			$this->logger->warn( "No payload found in API request." ) ;
		}
	}

	private function loadAPI() {

		global $API_INCLUDE_FOLDER_LIST ;

		$apiDefinitionFile = NULL ;

		foreach( $API_INCLUDE_FOLDER_LIST as $apiIncludeFolder ) {
			$this->logger->debug( "Checking API include folder - $apiIncludeFolder" ) ;
			
			$apiDefinitionFile = $apiIncludeFolder . $this->apiName . ".php" ;
			if( file_exists( $apiDefinitionFile ) ) {
				$this->logger->debug( "API found" ) ;
				break ;
			}
			else {
				$apiDefinitionFile = NULL ;
			}
		}

		if( $apiDefinitionFile != NULL ) {
			include_once( $apiDefinitionFile ) ;
			return new $this->apiName() ;
		}
		else {
			throw new Exception( "API $this->apiName not found on server." ) ;
		}
	}

	public function writeErrorResponse( $message ) {
		$this->writeResponse( self::PROCESSING_RESULT_ERROR, $message ) ;
	}

	private function writeResponse( $status, $message, $payload=NULL ) {

	    $this->logger->debug( "Sending API response" ) ;
	    $this->logger->debug( "\tStatus = $status" ) ;
	    $this->logger->debug( "\tMessage = $message" ) ;

		$response = array() ;
		$processingStatus   = array() ;

	    $processingStatus[ "status" ]  = $status ;
	    $processingStatus[ "message" ] = $message ;

		$response[ "processingStatus" ] = $processingStatus ;
		$response[ "payload" ] = $payload ;

	    $outputString = json_encode( $response, JSON_NUMERIC_CHECK ) ;
	    $this->logger->debug( "\tResponse string = $outputString" ) ;
	    echo $outputString ;

	    die() ;
	}
}
?>