<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/initializers/" . "initializer.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/utils/" .        "server_context.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/utils/" .        "cache.php" ) ;

class ServerInitializer extends Initializer {

	private $logger ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
        array_push( $GLOBALS[ 'initializer_chain' ], $this ) ;
	}

	function initialize() {
		ServerContext::setAppConfigs( $GLOBALS[ 'APP_CONFIG_DATA' ] ) ;
		Cache::initialize() ;
		$this->logger->debug( "Server initialized." ) ;
	}
}

new ServerInitializer() ;

?>