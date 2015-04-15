<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/vo/user.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/utils/execution_context.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/services/authorization_service.php" ) ;

class AuthorizationServiceTest extends PHPUnit_Framework_TestCase {

	private $logger ;
	private $user ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
	}

	protected function setUp() {

		ExecutionContext::setCurrentUser( "Sandeep" ) ;
		$this->user = &ExecutionContext::getCurrentUser() ;
	}

	function testUserInRole() {

		$this->user->addRole( "supervisor" ) ;
		$this->user->addRole( "superintendent" ) ;
		$this->user->addRole( "superuser" ) ;
		$this->user->addRole( "student.class.7" ) ;
		$this->user->addRole( "student.class.6" ) ;

		$this->assertTrue( Authorizer::isUserInRole( "superus?r" ), "superus?r" ) ;
		$this->assertTrue( Authorizer::isUserInRole( "super*r" ), "super*r" ) ;
		$this->assertTrue( Authorizer::isUserInRole( "super*t" ), "super*t" ) ;
		$this->assertTrue( Authorizer::isUserInRole( "student.*.7" ), "student.*.7" ) ;
	}

	function testSimpleAuthorizationPass() {

		$this->user->addEntitlement( "+::page:**/php/**" ) ;
		$guard = "page:/lib-app/php/services/profile.php" ;

		$this->assertNotNull( Authorizer::getAccessFlags( $guard ) ) ;
	}

	function testSimpleAuthorizationFail() {

		$this->user->addEntitlement( "-::page:**/admin/**" ) ;
		$guard = "page:/lib-app/php/services/admin/profile.php" ;

		$this->assertNull( Authorizer::getAccessFlags( $guard ) ) ;
	}

	function testInclusionOverride() {

		$this->user->addEntitlement( "+::page:**/php/**" ) ;
		$this->user->addEntitlement( "(+)::page:**/profile.php" ) ;

		// this guard should pass the **/php/** entitlement, but get 
		// overridden by **/profile.php - net entitlement false.
		$guard = "page:/lib-app/php/services/profile.php" ;

		$this->assertNull( Authorizer::getAccessFlags( $guard ) ) ;
	}

	function testExclusionOverride() {

		$this->user->addEntitlement( "+::page:/lib-app/php/**" ) ;
		$this->user->addEntitlement( "-::page:**/php/**" ) ;
		$this->user->addEntitlement( "(-)::page:**/profile.php" ) ;

		// Inclusion filter will approve
		// this guard should fail the **/php/** entitlement, but get 
		// overridden by **/profile.php - net entitlement true.
		$guard = "page:/lib-app/php/services/profile.php" ;

		$this->assertNotNull( Authorizer::getAccessFlags( $guard ) ) ;
	}

	function testCheckReducedAccess() {

		$this->user->addEntitlement( "+:w:page:/lib-app/php/**" ) ;
		$guard = "page:/lib-app/php/services/profile.php" ;
		
		$this->assertFalse( Authorizer::isReadAuthorized( $guard ) ) ;
		$this->assertTrue ( Authorizer::isWriteAuthorized( $guard ) ) ;
		$this->assertFalse( Authorizer::isExecuteAuthorized( $guard ) ) ;
	}

	function testCheckReducedAccessOverride() {

		$this->user->addEntitlement( "+   :  w  :page:/lib-app/php/**" ) ;
		$this->user->addEntitlement( "(+) : ..+ :page:/lib-app/php/**/profile.php" ) ;
		$guard = "page:/lib-app/php/services/profile.php" ;
		
		$this->assertFalse( Authorizer::isReadAuthorized( $guard ) ) ;
		$this->assertTrue ( Authorizer::isWriteAuthorized( $guard ) ) ;
		$this->assertTrue ( Authorizer::isExecuteAuthorized( $guard ) ) ;
	}
}