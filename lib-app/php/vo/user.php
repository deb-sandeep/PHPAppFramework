<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/utils/a12n_utils.php" ) ;

class User {

	const INCLUSION_RIGHTS_KEY = "inclusion.rights" ;
	const EXCLUSION_RIGHTS_KEY = "exclusion.rights" ;

	const AUTH_RES_TYPE_ROLE = "ROLE" ;

	private $logger ;

	private $userName     = NULL ;
	private $preferences  = NULL ;
	private $entitlements = NULL ;
	private $roles        = NULL ;

	function __construct( $name ) {

		$this->logger = Logger::getLogger( __CLASS__ ) ;
		
		$this->userName    = $name ;
		$this->preferences = array() ;
		$this->entitlements= array() ;
		$this->roles       = array() ;
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

	private function getEntitlementRightsContainer( $entitlementType ) {

		$container = NULL ;
		if( array_key_exists( $entitlementType, $this->entitlements ) ) {
			$container = $this->entitlements[ $entitlementType ] ;
		}
		return $container ;
	}

	function getInclusionEntitlements( $entitlementType ) {
		return $this->getEntitlements( $entitlementType, A12NUtils::OP_INCLUDE ) ;
	}

	function getExclusionEntitlements( $entitlementType ) {
		return $this->getEntitlements( $entitlementType, A12NUtils::OP_EXCLUDE ) ;
	}

	function getInclusionOverrideEntitlements( $entitlementType ) {
		return $this->getEntitlements( $entitlementType,
			                           A12NUtils::OP_INCLUDE_OVERRIDE ) ;
	}

	function getExclusionOverrideEntitlements( $entitlementType ) {
		return $this->getEntitlements( $entitlementType,
			                           A12NUtils::OP_EXCLUDE_OVERRIDE ) ;
	}

	function getRoles() {
		return $this->roles ;
	}

	function getAllEntitlementsAsStringArray() {

		$allEntitlements = array() ;
		$opTypeList = array( A12NUtils::OP_INCLUDE, A12NUtils::OP_EXCLUDE, 
			                 A12NUtils::OP_INCLUDE_OVERRIDE, 
			                 A12NUtils::OP_EXCLUDE_OVERRIDE ) ;

		foreach( $this->entitlements as $entType => $entContainer ) {
			foreach( $opTypeList as $opType ) {
				$container = $entContainer[ $opType ] ;
				foreach( $container as $entitlement ) {
					array_push( $allEntitlements,
						        $opType . ":" . $entType . ":" . $entitlement ) ;
				}
			}
		}
		return $allEntitlements ;
	}

	// --------------------- Setters -------------------------------------------
	function setPreference( $key, $value ) {
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

	function addEntitlements( $entitlementList ) {

		foreach( $entitlementList as $entitlement ) {
			$this->addEntitlement( $entitlement ) ;
		}
	}

	function addEntitlement( $entitlement ) {
		
		$container = NULL ;
		$entitlementContainer = NULL ;

		$patternComponents  = A12NUtils::getPatternComponents( $entitlement ) ;
		$opType             = $patternComponents[0] ;
		$entitlementType    = $patternComponents[1] ;
		$entitlementPattern = $patternComponents[2] ;

		$this->logger->debug( "Adding entitlement for user $this->userName" ) ;
		$this->logger->debug( "\topType = $opType" ) ;
		$this->logger->debug( "\tentitlementType = $entitlementType" ) ;
		$this->logger->debug( "\tentitlementPattern = $entitlementPattern" ) ;

		if( !array_key_exists( $entitlementType, $this->entitlements ) ) {
			
			$container = array() ;
			$container[ A12NUtils::OP_INCLUDE ] = array() ;
			$container[ A12NUtils::OP_EXCLUDE ] = array() ;
			$container[ A12NUtils::OP_INCLUDE_OVERRIDE ] = array() ;
			$container[ A12NUtils::OP_EXCLUDE_OVERRIDE ] = array() ;

			$this->entitlements[ $entitlementType ] = &$container ;
		}
		else {
			$container = &$this->entitlements[ $entitlementType ] ;
		}

		$entitlementContainer = &$container[ $opType ] ;
		if( !in_array( $entitlementPattern, $entitlementContainer ) ) {
			$this->logger->debug( "Pushing pattern $entitlementPattern" ) ;
			array_push( $entitlementContainer, $entitlementPattern ) ;
		}
	}

	private function getEntitlements( $entitlementType, $opType ) {

		$entitlements = NULL ;
		
		if( array_key_exists( $entitlementType, $this->entitlements ) ) {
			$container = $this->entitlements[ $entitlementType ] ;
			$entitlements = $container[ $opType ] ;
		}

		if( $entitlements == NULL ) {
			$entitlements = array() ;
		}
		return $entitlements ;
	}

}

?>