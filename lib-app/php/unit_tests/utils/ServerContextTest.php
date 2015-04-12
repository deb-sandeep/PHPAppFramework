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
	"default_landing_page" : "test.php",
	"test_app" : {
		"default_landing_page" : "dlp_test_app.php"
	},
	"jove_notes" : {
		"some_property" : "some_value"
	}
}
CONFIG;

		ServerContext::setAppConfigs( $config ) ;
		$this->assertEquals( "test.php",         
							 ServerContext::getDefaultLandingPage() ) ;
		
		$this->assertEquals( "dlp_test_app.php", 
			                 ServerContext::getDefaultLandingPage( "test_app" ) ) ;

		$this->assertEquals( "test.php", 
			                 ServerContext::getDefaultLandingPage( "jove_notes" ) ) ;

		try{
			ServerContext::getDefaultLandingPage( "non_configured_app" ) ;
			$this->fail( "landing page for non configured app returned." ) ;
		}
		catch( Exception $e ) {
		}
	}

	
}