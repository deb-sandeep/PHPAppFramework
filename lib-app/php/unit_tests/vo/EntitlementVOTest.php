<?php

require_once( "../vo/entitlement.php" ) ;

class EntitlementVOTest extends PHPUnit_Framework_TestCase {

	private $logger ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
	}

	public function testEntitlementParseSuccessForOpInclude() {

		$entStringIncl  = "+   : rw  : property : test_app.moduleA.prop.name" ;

		$ent = new Entitlement( $entStringIncl ) ;
		$this->assertEquals( Entitlement::OP_INCLUDE, $ent->getOpType() ) ;
		$this->assertTrue( $ent->isReadPermitted() ) ;
		$this->assertTrue( $ent->isWritePermitted() ) ;
		$this->assertFalse( $ent->isExecutePermitted() ) ;
		$this->assertEquals( "property", $ent->getResourceType() ) ;
		$this->assertEquals( "test_app.moduleA.prop.name", $ent->getPattern() ) ;
		$this->assertEquals( "+:rw:property:test_app.moduleA.prop.name", "".$ent ) ;
	}

	public function testEntitlementParseSuccessForOpIncludeOverride() {

		$entStringInclO = "(+) : -.+ : property : test_app.moduleA.prop.name" ;

		$ent = new Entitlement( $entStringInclO ) ;
		$this->assertEquals( Entitlement::OP_INCLUDE_OVERRIDE, $ent->getOpType() ) ;
		$this->assertFalse( $ent->isReadPermitted() ) ;
		$this->assertNull( $ent->isWritePermitted() ) ;
		$this->assertTrue( $ent->isExecutePermitted() ) ;
		$this->assertEquals( "property", $ent->getResourceType() ) ;
		$this->assertEquals( "test_app.moduleA.prop.name", $ent->getPattern() ) ;
		$this->assertEquals( "(+):-.+:property:test_app.moduleA.prop.name", "".$ent ) ;
	}

	public function testEntitlementParseSuccessForEmptyAccessType() {

		$entStringInclO = "(+) :: property : test_app.moduleA.prop.name" ;

		$ent = new Entitlement( $entStringInclO ) ;
		$this->assertEquals( Entitlement::OP_INCLUDE_OVERRIDE, $ent->getOpType() ) ;
		$this->assertFalse( $ent->isReadPermitted() ) ;
		$this->assertFalse( $ent->isWritePermitted() ) ;
		$this->assertFalse( $ent->isExecutePermitted() ) ;
		$this->assertEquals( "property", $ent->getResourceType() ) ;
		$this->assertEquals( "test_app.moduleA.prop.name", $ent->getPattern() ) ;
		$this->assertEquals( "(+):---:property:test_app.moduleA.prop.name", "".$ent ) ;
	}

	public function testEntitlementParseSuccessForEmptyAccessType1() {

		$entString = "+ :: property : test_app.moduleA.prop.name" ;

		$ent = new Entitlement( $entString ) ;
		$this->assertEquals( Entitlement::OP_INCLUDE, $ent->getOpType() ) ;
		$this->assertTrue( $ent->isReadPermitted() ) ;
		$this->assertTrue( $ent->isWritePermitted() ) ;
		$this->assertTrue( $ent->isExecutePermitted() ) ;
		$this->assertEquals( "property", $ent->getResourceType() ) ;
		$this->assertEquals( "test_app.moduleA.prop.name", $ent->getPattern() ) ;
		$this->assertEquals( "+:rwx:property:test_app.moduleA.prop.name", "".$ent ) ;
	}

	public function testEntitlementParseFailures() {

		$strings = array(
			"x:",
			"+:z:",
			"+:..:pro:A"
		) ;

		foreach( $strings as $string ) {
			try{
				$ent = new Entitlement( $string ) ;
				$this->fail( "The pattern $string should have failed." ) ;
			}
			catch( Exception $e ) {
			}
		}
	}

	public function testAccessFlagSuperimposition() {

		$afA = new AccessFlags( Entitlement::OP_INCLUDE, true, true,  false ) ;
		$afB = new AccessFlags( Entitlement::OP_INCLUDE, NULL, false, true ) ;

		$this->assertTrue ( $afA->isReadPermitted() ) ;
		$this->assertTrue ( $afA->isWritePermitted() ) ;
		$this->assertFalse( $afA->isExecutePermitted() ) ;

		$afA->superimpose( $afB ) ;

		$this->assertTrue ( $afA->isReadPermitted() ) ;
		$this->assertFalse( $afA->isWritePermitted() ) ;
		$this->assertTrue ( $afA->isExecutePermitted() ) ;
	}
}