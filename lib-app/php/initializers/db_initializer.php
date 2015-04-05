<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/initializers/" . "initializer.php" ) ;

class DBInitializer extends Initializer {

	function __construct() {
        array_push( $GLOBALS[ 'initializer_chain' ], $this ) ;
	}

	function initialize() {

		global $logger, $dbConn ;

		$dbConn = mysqli_connect( DB_HOST, DB_USER, DB_PASSWORD, DB_SCHEMA ) ;
		if( mysqli_connect_errno() ) {
		    throw new Exception( "Failed to connect to MySQL: " . mysqli_connect_error() ) ;
		}
		$logger->debug( "\tInitialized the DBConnection" ) ;		
	}
}

new DBInitializer() ;

?>