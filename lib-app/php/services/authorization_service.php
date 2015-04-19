<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/configs/default_entitlement_rules.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/utils/general_utils.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/utils/string_utils.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/services/service.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/vo/user.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/utils/execution_context.php" ) ;

use sandy\phpfw\entitlement as ent ;

class AuthorizationException extends Exception {
} 

class DefaultEntitlementRules {

	public $resourceType ;
	public $permissibleOps ;
	public $indifferenceOps ;
	public $conflictStrategy ;

	function __construct( $resourceType, $configObj ) {

		$this->resourceType = $resourceType ;
		$this->indifferenceOps = array() ;

		$this->permissibleOps = Utils::getAttributeValue( $configObj, 
			                                         "permissible_ops", true ) ;

		$indiffOps = Utils::getAttributeValue( $configObj, 
			                                 "default_indifferent_ops", true ) ;
		foreach( $indiffOps as $op ) {
			array_push( $this->indifferenceOps, ent\Operation::fromRawOp( $op ) ) ;
		}

		$this->conflictStrategy = Utils::getAttributeValue( $configObj, 
			                               "default_conflict_strategy", true ) ;
	}	
}

class AuthorizationService {

	private $logger ;
	private $defaultEnitlementRules ;

	function __construct( $configJSONData ) {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
		$this->defaultEnitlementRules = array() ;
		$this->parseDefaultEntitlementRules( $configJSONData ) ;
	}

	private function parseDefaultEntitlementRules( $configJSONData ) {
		
		$appConfigs = json_decode( $configJSONData ) ;
		if( json_last_error() != JSON_ERROR_NONE ) {
			throw new Exception( "Entitlement default rules JSON format is " .
				                 "incorrect. Please check." ) ;
		}

		foreach( $appConfigs as $resourceType => $configObj ) {
			$this->defaultEnitlementRules[ $resourceType ] = 
			         new DefaultEntitlementRules( $resourceType, $configObj ) ;
		}
	}

	function isUserInRole( $user, $rolePattern ) {

		foreach( $user->getRoles() as $role ) {
			if( StringUtils::matchSimplePattern( $rolePattern, $role ) ) {
				return true ;
			}
		}
		return false ;
	}

	function hasAccess( $user, $guard, $opName ) {

		$guardComponents = explode( ":", $guard ) ;
		if( count( $guardComponents ) != 2 ) {
			throw new AuthorizationException( "Guard $guard - wrong format." ) ;
		}
		$resType = trim( $guardComponents[0] ) ;
		$path    = trim( $guardComponents[1] ) ;

		$entitlement      = $user->getEntitlement() ;
		$accessPrivileges = $entitlement->computeAccessPrivilege( $resType, $path ) ;
		$privilege        = $accessPrivileges->getAccessPrivilege( $opName ) ;	

		if( $privilege == ent\AccessPrivilege::AP_INDEFINITE ) {

			$defaultRules = $this->defaultEnitlementRules[ $resType ] ;
			foreach( $defaultRules->indifferenceOps as $op ) {
				if( $op->getOpName() == $opName ) {
					return !$op->isForbidden() ;
				}
			}
			throw new Exception( "Indifference rules for resource $resourceType " .
				                 " is not configured in the system." ) ;
		}
		else if( $privilege == ent\AccessPrivilege::AP_CONFLICT ) {

			$defaultRules = $this->defaultEnitlementRules[ $resType ] ;
			return $defaultRules->conflictStrategy == "allow" ;
		}
		else if( $privilege == ent\AccessPrivilege::AP_ACCESS ) {

			return true ;
		}
		else if( $privilege == ent\AccessPrivilege::AP_FORBID ) {

			return false ;
		}
	}
}

class Authorizer {

	static $logger ;
	static $service ;

	static function isUserInRole( $role ) {
		return self::$service->isUserInRole( 
						ExecutionContext::getCurrentUser(), $role ) ;
	}

	static function hasAccess( $guard, $opName ) {
		return self::$service->hasAccess( 
			            ExecutionContext::getCurrentUser(), $guard, $opName ) ;
	}
}

Authorizer::$logger  = Logger::getLogger( "Authorizer" ) ;
Authorizer::$service = new AuthorizationService( $DEFAULT_ENTITLEMENTS ) ;

?>