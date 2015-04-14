<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/utils/server_context.php" ) ;

class ServerContextTest extends PHPUnit_Framework_TestCase {

	private $logger ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
	}

	function testBasicParse() {

		$config = 
<<<CONFIG
{
	"landing_page" : "test.php",
	"test_app" : {
		"landing_page" : "dlp_test_app.php"
	},
	"jove_notes" : {
		"some_property" : "some_value"
	}
}
CONFIG;

		ServerContext::setAppConfigs( $config ) ;
		$this->assertEquals( "test.php",         
							 ServerContext::getLandingPage() ) ;
		
		$this->assertEquals( "dlp_test_app.php", 
			                 ServerContext::getLandingPage( "test_app" ) ) ;

		$this->assertEquals( "test.php", 
			                 ServerContext::getLandingPage( "jove_notes" ) ) ;

		try{
			ServerContext::getLandingPage( "non_configured_app" ) ;
			$this->fail( "landing page for non configured app returned." ) ;
		}
		catch( Exception $e ) {
		}
	}

	
}