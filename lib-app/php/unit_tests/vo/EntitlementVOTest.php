<?php

require_once( "../vo/entitlement.php" ) ;

use sandy\phpfw\entitlement as ent ;

class EntitlementVOTest extends PHPUnit_Framework_TestCase {

	private $logger ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
	}

	function testSelectorConstruction() {

		$sel = new ent\Selector( "+:property:test_app/**" ) ;
		$this->assertEquals( ent\Selector::OP_INCLUDE, $sel->getOpType() ) ;
		$this->assertEquals( "property", $sel->getResourceType() ) ;
		$this->assertEquals( "test_app/**", $sel->getPattern() ) ;
		$this->assertEquals( "  +:property:test_app/**", "".$sel ) ;
	}


	function testEntitlementCreation() {

		$ent = new ent\Entitlement( "TestEntitlement" ) ;
		$ent->addRawSelector( "(+):property:test_app/Y" ) ; 
		$ent->addRawSelector( "+:property:test_app/B" ) ; 
		$ent->addRawSelector( "+:property:test_app/A" ) ; 
		$ent->addRawSelector( "(-):property:test_app/C" ) ; 
		$ent->addRawSelector( "-:property:test_app/C" ) ; 
		$ent->addRawSelector( "(+):property:test_app/D" ) ; 
		$ent->addPrivileges ( array( "READ", "WRITE" ) ) ;
		$ent->addPrivilege  ( "-:EXECUTE" ) ;

		$child = new ent\Entitlement( "ChildEntitlement" ) ;
		$child->addRawSelector( "+:table:user" ) ;
		$child->addPrivilege( "+:READ" ) ;

		$ent->addChildEntitlement( $child ) ;

		//$this->logger->debug( "" . $ent ) ;
		$this->assertFalse( $child->addChildEntitlement( $ent ) ) ;
		$this->assertFalse( $child->addChildEntitlement( $child ) ) ;
	}

	function testAccessPrivilegeCollation() {

		$priv = new ent\AccessPrivilege() ;
		$priv->addPrivilege( ent\Operation::fromRawOp( "+:READ" ) ) ;
		$priv->addPrivilege( ent\Operation::fromRawOp( "-:READ" ) ) ;
		$priv->addPrivilege( ent\Operation::fromRawOp( "-:READ" ) ) ;
		$priv->addPrivilege( ent\Operation::fromRawOp( "+:WRITE" ) ) ;
		$priv->addPrivilege( ent\Operation::fromRawOp( "+:WRITE" ) ) ;
		$priv->addPrivilege( ent\Operation::fromRawOp( "-:WRITE" ) ) ;

		$this->assertTrue( $priv->isForbidden( "READ" ) ) ;
		$this->assertTrue( $priv->isAccessible( "WRITE" ) ) ;
	}

	function testAccessPrivilegeMerge() {

		$priv1 = new ent\AccessPrivilege() ;
		$priv1->addRawPrivilege( "+:WRITE" ) ;
		$priv1->addRawPrivilege( "+:READ" ) ;
		$priv1->addRawPrivilege( "+:READ" ) ;

		
		$priv2 = new ent\AccessPrivilege() ;
		$priv2->addRawPrivilege( "+:READ" ) ;
		$priv2->addRawPrivilege( "-:READ" ) ;
		$priv2->addRawPrivilege( "-:READ" ) ;

		$priv = new ent\AccessPrivilege() ;
		$priv->merge( $priv1 ) ;
		$priv->merge( $priv2 ) ;
		$priv->normalize() ;

		$this->assertTrue( $priv->isAccessible( "READ" ) ) ;
		$this->assertTrue( $priv->isAccessible( "WRITE" ) ) ;
	}

	function testAccessPrivilegeComputation() {

		$ent = new ent\Entitlement( "TE" ) ;
		$ent->addRawSelector( "+:property:test_app/**" ) ;
		$ent->addRawSelector( "-:property:test_app/exclude_props/**" ) ;
		$ent->addPrivilege( "READ" ) ;
		$ent->addPrivilege( "-:WRITE" ) ;

		$privs = $ent->computeAccessPrivilege( "property", "test_app/propA" ) ;
		$this->assertTrue( $privs->isAccessible( "READ" ) ) ;
		$this->assertTrue( $privs->isForbidden( "WRITE" ) ) ;

		$privs = $ent->computeAccessPrivilege( "property", "test_app/exclude_props/**" ) ;
		$this->assertTrue( $privs->isIndefinite( "READ" ) ) ;
		$this->assertTrue( $privs->isIndefinite( "WRITE" ) ) ;
	}

	function testAccessPrivilegeSimpleCompleteUniverse() {

		$ent = new ent\Entitlement( "Overall") ;

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

		$ent->addChildEntitlement( $entChild1 ) ;
		$ent->addChildEntitlement( $entROW ) ;

		$privs = $ent->computeAccessPrivilege( "property", "test_app/propA" ) ;
		$this->assertTrue( $privs->isAccessible( "READ" ) ) ;
		$this->assertTrue( $privs->isAccessible( "WRITE" ) ) ;

		$privs = $ent->computeAccessPrivilege( "property", "test_app/exclude_props/propB" ) ;
		$this->assertTrue( $privs->isAccessible( "READ" ) ) ;
		$this->assertFalse( $privs->isAccessible( "WRITE" ) ) ;
	}
}