<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/initializers/" . "initializer.php" ) ;

class DBInitializer extends Initializer {

	private $logger ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
        array_push( $GLOBALS[ 'initializer_chain' ], $this ) ;
	}

	function initialize() {

		global $dbConn ;

		$dbConn = mysqli_connect( DB_HOST, DB_USER, DB_PASSWORD, DB_SCHEMA ) ;
		$dbConn->set_charset("utf8") ;
		if( mysqli_connect_errno() ) {
		    throw new Exception( "Failed to connect to MySQL: " . mysqli_connect_error() ) ;
		}
		$this->logger->debug( "\tInitialized the DBConnection" ) ;		
	}
}

new DBInitializer() ;

?>