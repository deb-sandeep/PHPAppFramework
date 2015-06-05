<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/api/api.php" ) ;

class APIRequestResponseTest extends PHPUnit_Framework_TestCase {

	private $logger ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
	}

	public function testResponseBody() {

		$response = new APIResponse() ;
		$response->responseCode = APIResponse::SC_OK ;

		$response->responseBody = "A message" ;
		$this->assertTrue( is_string( $response->responseBody ) ) ;

		$response->responseBody = array( "A", "B" ) ;
		$this->assertTrue( is_array( $response->responseBody ) ) ;

		$response->responseBody = new APIResponse() ;
		$this->assertTrue( is_object( $response->responseBody ) ) ;
	}
}

?>