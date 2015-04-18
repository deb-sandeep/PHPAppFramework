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
		$ent->addPermittedOp( array( "READ", "WRITE" ) ) ;
		$ent->addPermittedOp( "-:EXECUTE" ) ;

		$child = new ent\Entitlement( "ChildEntitlement" ) ;
		$child->addRawSelector( "+:table:user" ) ;
		$child->addPermittedOp( "+:READ" ) ;

		$ent->addChildEntitlement( $child ) ;

		//$this->logger->debug( "" . $ent ) ;
		$this->assertFalse( $child->addChildEntitlement( $ent ) ) ;
		$this->assertFalse( $child->addChildEntitlement( $child ) ) ;
	}

	function testAccessPrivilegeCollation() {

		$priv = new ent\AccessPrivilege() ;
		$priv->addPriviledge( ent\Operation::fromRawOp( "+:READ" ) ) ;
		$priv->addPriviledge( ent\Operation::fromRawOp( "-:READ" ) ) ;
		$priv->addPriviledge( ent\Operation::fromRawOp( "-:READ" ) ) ;
		$priv->addPriviledge( ent\Operation::fromRawOp( "+:WRITE" ) ) ;
		$priv->addPriviledge( ent\Operation::fromRawOp( "+:WRITE" ) ) ;
		$priv->addPriviledge( ent\Operation::fromRawOp( "-:WRITE" ) ) ;

		$this->assertFalse( $priv->hasAccess( "READ" ) ) ;
		$this->assertTrue( $priv->hasAccess( "WRITE" ) ) ;
	}

	function testEntitlementAccessPrivileges() {

		$ent = new ent\Entitlement( "TE" ) ;
		$ent->addPermittedOp( "+:READ" ) ;
		$ent->addPermittedOp( "-:READ" ) ;
		$ent->addPermittedOp( "-:READ" ) ;

		$this->assertFalse( $ent->getAccessPrivileges()->hasAccess( "READ" ) ) ;
	}

	function testAccessPrivilegeMerge() {

		$priv1 = new ent\AccessPrivilege() ;
		$priv1->addPriviledge( ent\Operation::fromRawOp( "+:WRITE" ) ) ;
		$priv1->addPriviledge( ent\Operation::fromRawOp( "+:READ" ) ) ;
		$priv1->addPriviledge( ent\Operation::fromRawOp( "+:READ" ) ) ;

		
		$priv2 = new ent\AccessPrivilege() ;
		$priv2->addPriviledge( ent\Operation::fromRawOp( "+:READ" ) ) ;
		$priv2->addPriviledge( ent\Operation::fromRawOp( "-:READ" ) ) ;
		$priv2->addPriviledge( ent\Operation::fromRawOp( "-:READ" ) ) ;

		$priv1->merge( $priv2 ) ;
		$this->assertTrue( $priv1->hasAccess( "READ" ) ) ;
	}

	function testAccessPrivilegeComputation() {

		$ent = new ent\Entitlement( "TE" ) ;
		$ent->addRawSelector( "+:property:test_app/**" ) ;
		$ent->addPermittedOp( "READ" ) ;

		$privs = $ent->computeAccessPrivilege( "property", "test_app/propA" ) ;
		$this->assertTrue( $privs->hasAccess( "READ" ) ) ;
	}
}