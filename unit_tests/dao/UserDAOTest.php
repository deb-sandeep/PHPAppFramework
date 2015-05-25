<?php

require_once( DOCUMENT_ROOT . "/unit_tests/dao/AbstractDAOTestCase.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/dao/user_dao.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/vo/entitlement.php" ) ;

use sandy\phpfw\entitlement as ent ;

class UserDAOTest extends AbstractDAOTestCase {

	private $logger ;
	private $userDAO ;

	function __construct() {
		parent::__construct() ;
		$this->logger = Logger::getLogger( __CLASS__ ) ;
	}

	function setUp() {
		parent::setUp() ;
		$this->userDAO = new UserDAOImpl() ;
	}

	function testLoadUserPreferences() {

		$prefs = $this->userDAO->loadUserPreferences( "UTUser" ) ;
		$this->assertArrayHasKey( "ut.default.key.A", $prefs ) ;
		$this->assertArrayHasKey( "ut.default.key.B", $prefs ) ;

		$this->assertEquals( "valueA", $prefs[ "ut.default.key.A" ] ) ;
		$this->assertEquals( "valueB.override", $prefs[ "ut.default.key.B" ] ) ;
	}

	function testLoadUserRoles() {

		$roles = $this->userDAO->getUserRoles( "UTUser" ) ;
		foreach( $roles as $role ) {
			$this->logger->debug( "Role - $role" ) ;
		}	

		$this->assertGreaterThan( 6, count($roles) ) ;
		$this->assertContains( "ut.role.0", $roles ) ;
		$this->assertContains( "ut.role.1", $roles ) ;
		$this->assertContains( "ut.role.1.1", $roles ) ;
		$this->assertContains( "ut.role.1.2", $roles ) ;
		$this->assertContains( "ut.role.1.1.1", $roles ) ;
		$this->assertContains( "ut.role.1.1.2", $roles ) ;
	}

	function testLoadUserEntitlements() {
		$ent = $this->userDAO->getEntitlementsForUser( 'UTUser' ) ;
		$ap = $ent->computeAccessPrivilege( "chapter", "Class-8/History/12/0/The American Revolution" ) ;
		$this->assertTrue( $ap->isAccessible( "NOTES" ) ) ;
		$this->assertTrue( $ap->isAccessible( "FLASH_CARD" ) ) ;
		$this->assertTrue( $ap->isAccessible( "CHAPTER_STATS" ) ) ;

		$ap = $ent->computeAccessPrivilege( "chapter", "Class-8/History/12/0/The American Revolution" ) ;
		$this->assertTrue( $ap->isAccessible( "NOTES" ) ) ;
		$this->assertTrue( $ap->isAccessible( "FLASH_CARD" ) ) ;
		$this->assertTrue( $ap->isAccessible( "CHAPTER_STATS" ) ) ;
	}
}

?>