<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/initializers/" . "initializer.php" ) ;

class SessionInitializer extends Initializer {

	private $logger ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
        array_push( $GLOBALS[ 'initializer_chain' ], $this ) ;
	}

	function initialize() {
		session_start() ;
		$this->logger->debug( "\tSession initialized." ) ;
	}
}

new SessionInitializer() ;

?>