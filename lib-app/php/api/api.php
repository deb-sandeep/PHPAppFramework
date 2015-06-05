<?php

final class APIRequest {

	const METHOD_GET    = "GET" ;
	const METHOD_PUT    = "PUT" ;
	const METHOD_POST   = "POST" ;
	const METHOD_DELETE = "DELETE" ;

	public $method                = NULL ;
	public $appName               = NULL ;
	public $resourceName          = NULL ;
	public $requestPathComponents = NULL ;
	public $parametersMap         = [] ;
	public $requestBody           = NULL ;
}

final class APIResponse {

	const SC_OK                        = 200 ;
	const SC_CREATED                   = 201 ;
	const SC_NO_CONTENT                = 204 ;
	const SC_ERR_BAD_REQUEST           = 400 ;
	const SC_ERR_FORBIDDEN             = 403 ;
	const SC_ERR_UNAUTHORIZED          = 401 ;
	const SC_ERR_NOT_FOUND             = 404 ;
	const SC_ERR_INTERNAL_SERVER_ERROR = 500 ;
	const SC_ERR_NOT_IMPLEMENTED       = 501 ;
	
	public $responseCode = 0 ;
	public $responseBody = NULL ;
}

abstract class API {

	protected $logger ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
	}

	final function execute( $request ) {
		$response = new APIResponse() ;
		$response->responseCode = APIResponse::SC_ERR_NOT_IMPLEMENTED ;

		$this->logger->debug( "Executing API $request->method " .
			                  "$request->resourceName" ) ;

		switch( $request->method ) {

			case APIRequest::METHOD_GET:
				$this->doGet( $request, $response ) ;
				break ;

			case APIRequest::METHOD_PUT:
				$this->doPut( $request, $response ) ;
				break ;

			case APIRequest::METHOD_POST:
				$this->doPost( $request, $response ) ;
				break ;

			case APIRequest::METHOD_DELETE:
				$this->doDelete( $request, $response ) ;
				break ;
		}

		return $response ;
	}

	function doGet( $request, &$response ) {
		$this->logger->debug( "Executing doGet in API base class." ) ;
	}

	function doPut( $request, &$response ) {
		$this->logger->debug( "Executing doPut in API base class." ) ;
	}

	function doPost( $request, &$response ) {
		$this->logger->debug( "Executing doPost in API base class." ) ;
	}

	function doDelete( $request, &$response ) {
		$this->logger->debug( "Executing doDelete in API base class." ) ;
	}
}
?>