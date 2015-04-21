<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/vo/user.php" ) ;

use sandy\phpfw\entitlement as ent ;

class UserVOTest extends PHPUnit_Framework_TestCase {

	private $logger ;
	private $user ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
	}

	protected function setUp() {
		$this->user = new User( "Sandeep" ) ;
	}

	public function testUserName() {

		$this->assertEquals( "Sandeep", $this->user->getUserName() ) ;
	}

	public function testUserPreferencesAreSet() {

		$this->user->setPreference( "key1", "value1" ) ;
		$this->assertEquals( "value1", $this->user->getPreference( "key1" ) ) ;
	}

	public function testUserPreferenceOverride() {

		$this->user->setPreference( "key1", "value1" ) ;
		$this->user->setPreference( "key1", "value2" ) ;

		$this->assertEquals( "value2", $this->user->getPreference( "key1" ) ) ;
	}

	public function testBooleanUserPreference() {
		
		$this->user->setPreference( "key1", true ) ;
		$this->assertEquals( true, $this->user->getPreference( "key1" ) ) ;
	}

	public function testIntUserPreference() {
		
		$this->user->setPreference( "key1", 23 ) ;
		$this->assertEquals( 23, $this->user->getPreference( "key1" ) ) ;
	}

	public function testAddRoles() {

		$this->user->addRole( "Admin" ) ;
		$this->user->addRole( "Class-VII-Student" ) ;

		$this->assertCount( 2, $this->user->getRoles() ) ;
		$this->assertContains( "Admin", $this->user->getRoles() ) ;
		$this->assertContains( "Class-VII-Student", $this->user->getRoles() ) ;
	}

	public function testUserEntitlements() {

		$entChild1 = new ent\Entitlement( "Child1" ) ;
		$entChild1->addRawSelector( "+:property:test_app/**" ) ;
		$entChild1->addRawSelector( "-:property:test_app/exclude_props/**" ) ;
		$entChild1->addPrivilege( "READ" ) ;
		$entChild1->addPrivilege( "WRITE" ) ;

		$entROW = new ent\Entitlement( "row" ) ;
		$entROW->addRawSelector( "+  :property:**" ) ;
		$entROW->addRawSelector( "-  :property:test_app/**" ) ;
		$entROW->addRawSelector( "(-):property:test_app/exclude_props/**" ) ;
		$entROW->addPrivilege( "READ" ) ;

		$ent = new ent\Entitlement( "Root" ) ;
		$ent->addChildEntitlement( $entChild1 ) ;
		$ent->addChildEntitlement( $entROW ) ;

		$this->user->setEntitlement( $ent ) ;

		$ent = $this->user->getEntitlement() ;

		$privs = $ent->computeAccessPrivilege( "property", "test_app/propA" ) ;
		$this->assertTrue( $privs->isAccessible( "READ" ) ) ;
		$this->assertTrue( $privs->isAccessible( "WRITE" ) ) ;

		$privs = $ent->computeAccessPrivilege( "property", "test_app/exclude_props/propB" ) ;
		$this->assertTrue( $privs->isAccessible( "READ" ) ) ;
		$this->assertFalse( $privs->isAccessible( "WRITE" ) ) ;
	}
}
?>