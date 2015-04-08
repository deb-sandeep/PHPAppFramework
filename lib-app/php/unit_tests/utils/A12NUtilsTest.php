<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/utils/a12n_utils.php" ) ;

class A12NUtilsTest extends PHPUnit_Framework_TestCase {

	private $logger ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
	}

	function testA12NExceptionCreation() {

		$exception = new A12NException( A12NException::INVALID_ENTITLEMENT_PATTERN ) ;
		assertEquals( A12NException::INVALID_ENTITLEMENT_PATTERN, 
			          $exception ) ;

		$exception = new A12NException( A12NException::INVALID_ENTITLEMENT_PATTERN,
			                            "(x)" ) ;
		assertEquals( A12NException::INVALID_ENTITLEMENT_PATTERN . "::(x)",
			          $exception ) ;
	}	

	function testCorrectPatternComponentExtraction() {

		$pattern = "(+):page:/admin/password.php" ;
		$components = A12NUtils::getPatternComponents( $pattern ) ;

		$this->assertEquals( $components[0], A12NUtils::OP_INCLUDE_OVERRIDE ) ;
		$this->assertEquals( $components[1], "page" ) ;
		$this->assertEquals( $components[2], "/admin/password.php" ) ;
	}

	function testIncorrectPatternComponentExtraction() {

		$pattern = "(x):page:/admin/password.php" ;
		try {
			$components = A12NUtils::getPatternComponents( $pattern ) ;
			$this->fail( "Test should have thrown an exception." ) ;
		}
		catch( A12NException $e ) {
			$this->assertEquals( A12NException::INVALID_ENTITLEMENT_PATTERN, 
				                 $e->getCode() ) ;
		}
	}
}

?>