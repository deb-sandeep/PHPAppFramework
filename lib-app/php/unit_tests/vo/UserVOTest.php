<?php

require_once( 'log4php/Logger.php' ) ;
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

	public function testAddRight() {

		$this->user->addRight( "ROLE", true, "Admin" ) ;
		$this->user->addRight( "ROLE", true, "Class-VII-Student" ) ;
		$this->user->addRight( "ROLE", false, "Class-VII-Admin" ) ;
		$this->user->addRight( "ROLE", false, "Class-VII-Tutor" ) ;

		$this->assertNotNull( $this->user->getInclusionRights( "ROLE" ) ) ;
		$this->assertNotNull( $this->user->getExclusionRights( "ROLE" ) ) ;

		$inclRights = $this->user->getInclusionRights( "ROLE" ) ;
		$this->assertContains( "Admin", $inclRights ) ;
		$this->assertContains( "Class-VII-Student", $inclRights ) ;
		$this->assertCount( 2, $inclRights ) ;

		$exclRights = $this->user->getExclusionRights( "ROLE" ) ;
		$this->assertContains( "Class-VII-Admin", $exclRights ) ;
		$this->assertContains( "Class-VII-Tutor", $exclRights ) ;
	}
}
?>