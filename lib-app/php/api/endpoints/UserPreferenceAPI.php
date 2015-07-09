<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/api/api.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/utils/execution_context.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/services/user_preference_service.php" ) ;

class UserPreferenceAPI extends API {

	private $upSvc = NULL ;

	function __construct() {
		parent::__construct() ;
		$this->upSvc = new UserPreferenceService() ;
	}

	// -------------------------------------------------------------------------
	// GET request processing
	// -------------------------------------------------------------------------
	public function doGet( $request, &$response ) {

		$this->logger->debug( "Executing doGet in UserPreferenceAPI" ) ;

		$keys = $request->getParameter( "keys" ) ;
		$responseBody = NULL ;

		if( $keys == NULL ) {
			// Get all the parameters
			$responseBody = $this->upSvc->getAllUserPreferences() ;
		}
		else {
			// Get only requested parameters
			$keyArray = explode( ",", $keys ) ;
			$responseBody = array() ;
			foreach( $keyArray as $key ) {
				$val = $this->upSvc->getUserPreference( $key ) ;
				if( $val != null ) {
					$responseBody[ $key ] = $val ;
				}
			}
		}

		$response->responseCode = APIResponse::SC_OK ;
		$response->responseBody = $responseBody ;
	}

	// -------------------------------------------------------------------------
	// PUT request processing
	// -------------------------------------------------------------------------
	public function doPut( $request, &$response ) {

		$this->logger->debug( "Executing doPut in UserPreferenceAPI" ) ;

		$this->upSvc->saveUserPreferences( $request->requestBody ) ;

		$response->responseCode = APIResponse::SC_OK ;
		$response->responseBody = "Update successful" ;
	}

	// -------------------------------------------------------------------------
	// DELETE request processing
	// -------------------------------------------------------------------------
	public function doDelete( $request, &$response ) {

		$this->logger->debug( "Executing doDelete in UserPreferenceAPI" ) ;

		$responseBody = "" ;
		if( $request->requestBody != NULL && 
			property_exists($request->requestBody, "keys" ) ) {
			
			$concatenatedKeys = $request->requestBody->keys ;
			$keys = explode( ",", $concatenatedKeys ) ;

			$this->upSvc->deleteUserPreferences( $keys ) ;
			$response->responseCode = APIResponse::SC_OK ;
			$response->responseBody = "Delete successful" ;
		}
		else {
			$response->responseCode = APIResponse::SC_ERR_BAD_REQUEST ;
			$response->responseBody = "keys not found in request" ;
		}
	}
}

?>