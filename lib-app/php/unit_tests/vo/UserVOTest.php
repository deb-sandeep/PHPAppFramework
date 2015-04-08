<?php

require_once( "../vo/user.php" ) ;

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

	public function testAddEntitlements() {

		$this->user->addEntitlement( "+:page:/admin/profile*.php" ) ;
		$this->user->addEntitlement( "+:page:/billing/**" ) ;
		$this->user->addEntitlement( "+:chapter:Chapter" ) ;
		$this->user->addEntitlement( "-:page:/admin/php/**" ) ;

		$this->user->addEntitlement( "(+):page:/admin/profile_su.php" ) ;
		$this->user->addEntitlement( "(-):page:/admin/php/vo/**" ) ;

		$includeEntsPage     = $this->user->getInclusionEntitlements( "page" ) ;
		$excludeEntsPage     = $this->user->getExclusionEntitlements( "page" ) ;
		$includeEntsChapter  = $this->user->getInclusionEntitlements( "chapter" ) ;
		$includeOverrideEnts = $this->user->getInclusionOverrideEntitlements( "page" ) ;
		$excludeOverrideEnts = $this->user->getExclusionOverrideEntitlements( "page" ) ;

		$this->assertCount( 2, $includeEntsPage ) ;
		$this->assertContains( "/admin/profile*.php", $includeEntsPage ) ;
		$this->assertContains( "/billing/**",         $includeEntsPage ) ;

		$this->assertCount( 1, $excludeEntsPage ) ;
		$this->assertContains( "/admin/php/**", $excludeEntsPage ) ;

		$this->assertCount( 1, $includeEntsChapter ) ;
		$this->assertContains( "Chapter", $includeEntsChapter ) ;

		$this->assertCount( 1, $includeOverrideEnts ) ;
		$this->assertContains( "/admin/profile_su.php", $includeOverrideEnts ) ;

		$this->assertCount( 1, $excludeOverrideEnts ) ;
		$this->assertContains( "/admin/php/vo/**", $excludeOverrideEnts ) ;
	}
}
?>