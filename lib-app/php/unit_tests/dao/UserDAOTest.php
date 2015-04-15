<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/unit_tests/dao/AbstractDAOTestCase.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/dao/user_dao.php" ) ;

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

		$this->assertCount( 6, $roles ) ;
		$this->assertContains( "ut.role.0", $roles ) ;
		$this->assertContains( "ut.role.1", $roles ) ;
		$this->assertContains( "ut.role.1.1", $roles ) ;
		$this->assertContains( "ut.role.1.2", $roles ) ;
		$this->assertContains( "ut.role.1.1.1", $roles ) ;
		$this->assertContains( "ut.role.1.1.2", $roles ) ;
	}

	function testLoadUserEntitlements() {

		$entitlements = $this->userDAO->getUserEntitlements( 'UTUser' ) ;

		$this->assertCount( 4, $entitlements ) ;
		$this->assertContains( "+::ut_page:/pattern/A/**", $entitlements ) ;
		$this->assertContains( "+::ut_page:/pattern/B/**", $entitlements ) ;
		$this->assertContains( "+::ut_page:/alias/A/**",   $entitlements ) ;
		$this->assertContains( "+::ut_page:/alias/A/**",   $entitlements ) ;
	}
}

?>