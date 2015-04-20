<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/vo/user.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/utils/execution_context.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/services/authorization_service.php" ) ;

use sandy\phpfw\entitlement as ent ;

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

	// It is assumed that universe has READ access via DEFAULT_ENTITLEMENTS
	function testUserEntitlements() {

		$entChild1 = new ent\Entitlement( "Child1" ) ;
		$entChild1->addRawSelector( "+:property:test_app/**" ) ;
		$entChild1->addRawSelector( "-:property:test_app/exclude_props/**" ) ;
		$entChild1->addPrivilege( "READ" ) ;
		$entChild1->addPrivilege( "WRITE" ) ;

		$this->user->addEntitlement( $entChild1 ) ;

		$this->assertTrue( Authorizer::hasAccess( "property:test_app/propA", "READ" ) ) ;
		$this->assertTrue( Authorizer::hasAccess( "property:test_app/propA", "WRITE" ) ) ;
		$this->assertTrue( Authorizer::hasAccess( "property:other_app/A",    "READ" ) ) ;
	}

	// Assumed that default conflict resolution strategy is deny (not allow)
	function testUserEntitlementsConflict() {

		$ent1 = new ent\Entitlement( "E1" ) ;
		$ent1->addRawSelector( "+:property:test_app/**" ) ;
		$ent1->addPrivilege( "-:WRITE" ) ;

		$ent2 = new ent\Entitlement( "E2" ) ;
		$ent2->addRawSelector( "+:property:test_app/a/**" ) ;
		$ent2->addPrivilege( "+:WRITE" ) ;

		$this->user->addEntitlement( $ent1 ) ;
		$this->user->addEntitlement( $ent2 ) ;

		$this->assertFalse( Authorizer::hasAccess( "property:test_app/a/propA", "WRITE" ) ) ;
	}
}