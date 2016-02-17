<?php
require_once( DOCUMENT_ROOT . "/lib-app/php/api/api.php" ) ;

class PingAPI extends API {

	function __construct() {
		parent::__construct() ;
	}

	// -------------------------------------------------------------------------
	// GET request processing
	// -------------------------------------------------------------------------
	public function doGet( $request, &$response ) {

		$this->logger->debug( "Executing doGet in PingAPI" ) ;
		$response->responseCode = APIResponse::SC_OK ;
		$response->responseBody = "Pong" ;
	}
}

?>