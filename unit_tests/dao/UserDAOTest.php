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
		// $this->logger->debug( "" . $ent ) ;
		$ap = $ent->computeAccessPrivilege( "note", "jn/c7/history/l1/Akbar" ) ;
		// $this->logger->debug( "" . $ap ) ;
		$this->assertTrue( $ap->isAccessible( "READ" ) ) ;
		$ap = $ent->computeAccessPrivilege( "note", "jn/c7/physics/l2/Heat") ;
		// $this->logger->debug( "" . $ap ) ;
		$this->assertFalse( $ap->isAccessible( "READ" ) ) ;
	}
}

?>