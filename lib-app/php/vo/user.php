<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/vo/entitlement.php" ) ;

use sandy\phpfw\entitlement as ent ;

class User {

	const INCLUSION_RIGHTS_KEY = "inclusion.rights" ;
	const EXCLUSION_RIGHTS_KEY = "exclusion.rights" ;

	const AUTH_RES_TYPE_ROLE = "ROLE" ;

	private $userName     = NULL ;
	private $preferences  = NULL ;
	private $entitlement  = NULL ;
	private $roles        = NULL ;

	function __construct( $name ) {

		$this->userName    = $name ;
		$this->preferences = array() ;
		$this->roles       = array() ;
		$this->entitlement = NULL ;
	}

	function getUserName() {
		return $this->userName ;
	}

	function getPreference( $key, $defaultValue=NULL ) {
		if( array_key_exists( $key, $this->preferences ) ) {
			return $this->preferences[ $key ] ;
		}
		return $defaultValue ;
	}

	function getPreferences() {
		return $this->preferences ;
	}

	function getRoles() {
		return $this->roles ;
	}

	function isRoleEnabled( $role ) {
		return in_array( $role, $this->roles ) ;
	}

	function getEntitlement() {
		return $this->entitlement ;
	}

	// --------------------- Setters -------------------------------------------
	function setPreference( $key, $value ) {

		if( $value === 'true' ) {
			$value = true ;
		}
		else if( $value === 'false' ) {
			$value = false ;
		}

		$this->preferences[ $key ] = $value ;
	}

	function addRoles( $rolesList ) {
		foreach( $rolesList as $role ) {
			$this->addRole( $role ) ;
		}
	}

	function addRole( $roleName ) {
		if( !in_array( $roleName, $this->roles ) ) {
			array_push( $this->roles, $roleName ) ;
		}
	}

	function addRawSelector( $selectorString ) {
		$this->entitlement->addRawSelector( $selectorString ) ;
	}

	function addSelector( $selector ) {
		$this->entitlement->addSelector( $selector ) ;
	}

	function addPrivileges( $operations ) {
		$this->entitlement->addPrivileges( $operations ) ;
	}

	function addPrivilege( $operation ) {
		$this->entitlement->addPrivilege( $operation ) ;
	}

	function setEntitlement( $entitlement ) {
		$this->entitlement = $entitlement ;
	}
}

?>