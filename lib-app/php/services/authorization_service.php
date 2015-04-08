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

	function isUserEntitled( $user, $entitlementGuard ) {

		$this->logger->debug( "Checking user entitlement for '" . $user->getUserName() .
			                  "' against guard $entitlementGuard" ) ;

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
		if( $this->guardMatches( $guardPath, $inclEnts ) ) {
			$this->logger->debug( "Inclusion entitlements match." ) ;

			$this->logger->debug( "Verifying against inclusion overrides." ) ;
			if( $this->guardMatches( $guardPath, $inclOverridesEnts ) ) {
				$this->logger->debug( "Inclusion overrides match." ) ;
				return false ;
			}
		}
		else {
			return false ;
		}

		$this->logger->debug( "Verifying against exclusion entitlements." ) ;
		if( $this->guardMatches( $guardPath, $exclEnts ) ) {
			$this->logger->debug( "Exclusion entitlements match." ) ;

			$this->logger->debug( "Verifying against exclusion overrides." ) ;
			if( $this->guardMatches( $guardPath, $exclOverrideEnts ) ) {
				$this->logger->debug( "Exclusion overrides match." ) ;
				return true ;
			}
		}
		else {
			return true ;
		}

		return false ;
	}

	private function guardMatches( $guardPath, $entitlements ) {
		foreach( $entitlements as $entitlement ) {
			if( StringUtils::matchSimplePattern( $entitlement, $guardPath ) ) {
				$this->logger->debug( "\tMatched" ) ;
				return true ;
			}
		}
		return false ;
	}
}

class Authorizer {

	static $logger ;
	static $service ;

	static function isUserInRole( $role ) {
		return self::$service->isUserInRole( 
						ExecutionContext::getCurrentUser(), $role ) ;
	}

	static function isUserEntitled( $entitlementGuard ) {
		return self::$service->isUserEntitled( 
			           	ExecutionContext::getCurrentUser(), $entitlementGuard ) ;
	}
}

Authorizer::$logger  = Logger::getLogger( "Authorizer" ) ;
Authorizer::$service = new AuthorizationService() ;

?>