<?php

class AbstractDAOTestCase extends PHPUnit_Framework_TestCase {

	private $logger ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
	}

	function setUp() {

		global $dbConn ;
		$dbConn = mysqli_connect( "localhost", "root", getenv( "DB_PASSWORD"), "study" ) ;
		if( mysqli_connect_errno() ) {
		    throw new Exception( "Failed to connect to MySQL: " . mysqli_connect_error() ) ;
		}
		$this->logger->debug( "Initialized the DBConnection" ) ;
	}

	function tearDown() {

		global $dbConn ;
		if( $dbConn != NULL ) {
			$dbConn->close() ;
			$this->logger->debug( "Closed the DBConnection" ) ;
		}
	}
}

?>