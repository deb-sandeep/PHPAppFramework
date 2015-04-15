<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/services/service.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/vo/user.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/utils/execution_context.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/utils/string_utils.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/utils/a12n_utils.php" ) ;

class AuthorizationException extends Exception {
} 

class AuthorizationService {

	private $logger ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
	}

	function isUserInRole( $user, $rolePattern ) {

		foreach( $user->getRoles() as $userRole ) {
			if( StringUtils::matchSimplePattern( $rolePattern, $userRole ) ) {
				return true ;
			}
		}
		return false ;
	}

	function getAccessFlags( $user, $entitlementGuard ) {

		$this->logger->debug( "Checking user entitlement for '" . $user->getUserName() .
			                  "' against guard $entitlementGuard" ) ;

		$accessFlags = NULL ;
		$guardComponents = A12NUtils::getGuardComponents( $entitlementGuard ) ;
		$guardType  = $guardComponents[0] ;
		$guardPath  = $guardComponents[1] ;

		$inclEnts          = $user->getInclusionEntitlements( $guardType ) ;
		$inclOverridesEnts = $user->getInclusionOverrideEntitlements( $guardType ) ;
		$exclEnts          = $user->getExclusionEntitlements( $guardType ) ;
		$exclOverrideEnts  = $user->getExclusionOverrideEntitlements( $guardType ) ;

		$this->logger->debug( "\tGuard type = $guardType" ) ;
		$this->logger->debug( "\tGuard path = $guardPath" ) ;
		$this->logger->debug( "\tNumber of inclusion entitlements          = " . 
			                  sizeof( $inclEnts ) ) ;
		$this->logger->debug( "\tNumber of inclusion override entitlements = " . 
			                  sizeof( $inclOverridesEnts ) ) ;
		$this->logger->debug( "\tNumber of exclusion entitlements          = " . 
			                  sizeof( $exclEnts ) ) ;
		$this->logger->debug( "\tNumber of exclusion override entitlements = " . 
			                  sizeof( $exclOverrideEnts ) ) ;

		$this->logger->debug( "Verifying against inclusion entitlements." ) ;
		$accessFlags = $this->matchEntitlements( $guardPath, $inclEnts ) ;
		if( $accessFlags == NULL || !$accessFlags->hasPrivileges() ) {
			$this->logger->debug( "No inclusion filter match. Not entitled." ) ;
			return NULL ;
		}
		else {
			$inclOverrideMatchAccessFlag = $this->matchEntitlements( 
				                              $guardPath, $inclOverridesEnts ) ;
			if( $inclOverrideMatchAccessFlag != NULL ) {
				$this->logger->debug( "Inclusion override filter match." ) ;
				$accessFlags->superimpose( $inclOverrideMatchAccessFlag ) ;
				if( !$accessFlags->hasPrivileges() ) {
					$this->logger->debug( "All privs revoked by inclusion override." ) ;
					return NULL ;
				}
				$this->logger->debug( "Inclusion override changed access privs." ) ;
			}
		}

		$this->logger->debug( "Verifying against exclusion entitlements." ) ;
		if( $this->matchEntitlements( $guardPath, $exclEnts ) != NULL ) {
			$this->logger->debug( "Exclusion entitlements match." ) ;

			$this->logger->debug( "Verifying against exclusion overrides." ) ;
			if( $this->matchEntitlements( $guardPath, $exclOverrideEnts ) != NULL ) {
				$this->logger->debug( "Exclusion overrides match." ) ;
				return $accessFlags ;
			}
			return NULL ;
		}
		else {
			return $accessFlags ;
		}
	}

	private function matchEntitlements( $guardPath, $entitlements ) {

		$accessFlags = NULL ;
		foreach( $entitlements as $entitlement ) {
			$accessFlags = $entitlement->match( $guardPath ) ;
			if( $accessFlags != NULL ) {
				break ;
			}
		}
		return $accessFlags ;
	}

}

class Authorizer {

	static $logger ;
	static $service ;

	static function isUserInRole( $role ) {
		return self::$service->isUserInRole( 
						ExecutionContext::getCurrentUser(), $role ) ;
	}

	static function getAccessFlags( $entitlementGuard ) {
		return self::$service->getAccessFlags( 
			           	ExecutionContext::getCurrentUser(), $entitlementGuard ) ;
	}
}

Authorizer::$logger  = Logger::getLogger( "Authorizer" ) ;
Authorizer::$service = new AuthorizationService() ;

?>