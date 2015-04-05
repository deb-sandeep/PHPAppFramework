<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/initializers/" . "initializer.php" ) ;

class SessionInitializer extends Initializer {

	function __construct() {
        array_push( $GLOBALS[ 'initializer_chain' ], $this ) ;
	}

	function initialize() {

		global $logger ;
		session_start() ;
		$logger->debug( "\tSession initialized." ) ;
	}
}

new SessionInitializer() ;

?>