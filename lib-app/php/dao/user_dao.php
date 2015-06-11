<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/dao/abstract_dao.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/vo/entitlement.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/utils/general_utils.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/utils/string_utils.php" ) ;

use sandy\phpfw\entitlement as ent ;

class UserDAOImpl extends AbstractDAO {

	private $logger ;

	function __construct() {
		parent::__construct() ;
		$this->logger = Logger::getLogger( __CLASS__ ) ;
	}

	function getUserPassword( $userName ) {

	    return parent::selectSingleValue( 
	    		"select user.password " .
	            "from user.user " .
	            "where user.name='$userName'" ) ;
    }

	function saveNewAuthenticationToken( $userName, $token, $tokenType ) {

		parent::executeInsert(
		         "insert into user.auth_token " .
		         "( user_name, token, token_type, creation_time ) " .
		         "values ( " .
		         	"'$userName', " .
		         	"'$token', " .
		         	"'$tokenType', " .
		         	"NOW()" .
		         ")" ) ;
	}

	function getUserNameForToken( $token ) {

	    return parent::selectSingleValue( 
	    		"select user_name " .
	            "from user.auth_token " .
	            "where token = '$token'" ) ;
	}

	function deleteAuthenticationToken( $token ) {
		parent::executeDelete( "delete from user.auth_token where token = '$token'"	) ;
	}

	function updateLastAccessTime( $userName, $authToken ) {

		parent::executeUpdate( "update user.auth_token " .
			                   "set last_access_time = NOW() " .
			                   "where token = '$authToken'", 0 ) ;

		parent::executeUpdate( "update user.user " .
			                   "set last_access_time = NOW() " .
			                   "where name = '$userName'", 0 ) ;
	}

	function removeObsoleteTokens() {

		parent::executeDelete( 
			"delete from user.auth_token " .
			"where " .
			"( token_type = 'SESSION' and " .
			   "timestampdiff( HOUR, last_access_time, NOW() ) >= " . 
			   NUM_HOURS_FOR_SESSION_AUTH_TOKEN_TO_BECOME_OBSOLETE .  
			" ) or " .
			"( token_type = 'REMEMBER_ME' and " .
			   "timestampdiff( DAY, last_access_time, NOW() ) >= " . 
			   NUM_DAYS_TO_REMEMBER_INACTIVE_USER . 
			" )", 0
		) ;
	}

	/**
	 * Returns a database result set with the following columns
	 * - key
	 * - value
	 */
	function loadUserPreferences( $userName ) {

$query = <<< QUERY
select m.key, if( p.value is null, m.default_value, p.value ) as value 
from user.user_preferences p right join 
     user.user_preferences_master m 
on 
	p.key = m.key 
where 
	p.user_name = '$userName' or 
	p.user_name is null
QUERY;

		return parent::getResultAsMap( $query ) ;
	}

	function saveUserPreference( $userName, $key, $value ) {

		$this->logger->debug( "Saving pref for $userName. [$key]=$value" ) ;

		$query = <<< QUERY
insert into `user`.`user_preferences` (`user_name`, `key`, `value`) 
values ( '$userName', '$key', '$value' ) 
on duplicate key update `value` = values ( `value` )
QUERY;

		parent::executeInsert( $query ) ;
	}


	function getUserRoles( $userName ) {

		$query = <<< QUERY
select distinct role_name from user.user_roles 
where user_name = '$userName'
QUERY;

		$roles = parent::getResultAsArray( $query ) ;
		if( count( $roles ) > 0 ) {
			$this->collectNestedRoles( $roles, $roles ) ;
		}

		return $roles ;
	}

	private function collectNestedRoles( &$allRoles, $thisLevelRoles ) {

		$roleCSV = implode( "','", $thisLevelRoles ) ;
		$query = <<< QUERY
select distinct child_role from user.roles
where name in ( '$roleCSV' ) and child_role is not NULL
QUERY;

		$childRoles = parent::getResultAsArray( $query ) ;

		if ( count( $childRoles ) > 0 ) {

			$nextLevelCheckRoles = array() ;
			foreach ( $childRoles as $childRole ) {
				if( !in_array( $childRole, $allRoles ) ) {
					array_push( $nextLevelCheckRoles, $childRole ) ;
					array_push( $allRoles, $childRole ) ;
				}
			}

			if( count( $nextLevelCheckRoles ) > 0 ) {
				$this->collectNestedRoles( $allRoles, $nextLevelCheckRoles ) ;
			}
		}
	}

	function getEntitlementsForUser( $userName ) {
		
		$this->logger->debug( "Getting entitlements for user $userName" ) ;
		
		$ent = new ent\Entitlement( "Entitlement for [$userName]" ) ;

		$roles = $this->getUserRoles( $userName ) ;
		foreach( $roles as $role ) {
			$this->loadRawEntitlementsForEntity( $ent, 'ROLE', $role ) ;
			$this->loadAliasEntitlementsForEntity( $ent, 'ROLE', $role ) ;
		}

		$this->loadRawEntitlementsForEntity( $ent, 'USER', $userName ) ;
		$this->loadAliasEntitlementsForEntity( $ent, 'USER', $userName ) ;

		return $ent ;
	}

	private function loadRawEntitlementsForEntity( &$ent, $entityType, $entityName ) {

		$this->logger->debug( "Loading raw entitlements for $entityType $entityName" ) ;

		$query = <<< QUERY
select selector_alias, permissible_ops 
from user.entity_entitlement
where
	entity_type = '$entityType' and
	entity_name = '$entityName' and
	entitlement_type = 'RAW'
QUERY;

		$result  = parent::executeSelect( $query, 0 ) ;

	    while( $row = $result->fetch_array() ) {

	    	$selectorAlias  = trim( $row[ "selector_alias"  ] ) ;
	    	$permissibleOps = explode( ",", $row[ "permissible_ops" ] ) ;

			$this->loadRawEntitlementsForSelectorAlias
				                 ( $ent, $selectorAlias, $permissibleOps ) ;
	    }
	}

	private function loadRawEntitlementsForSelectorAlias( &$ent, $selectorAlias, 
		                                                  $permissibleOps ) {
		
		if( StringUtils::isEmptyOrNull( $selectorAlias ) &&
			Utils::isArrayEmpty( $permissibleOps ) ) {
			throw new Exception( "Both permissible ops and selector alias " . 
				                 "can't be null or empty" ) ;
		}

		$nextLevelAliases     = array( $selectorAlias ) ;
		$alreadyLoadedPaths   = array() ;
		$alreadyLoadedAliases = array() ;

		if( StringUtils::isEmptyOrNull( $selectorAlias ) ) {

			foreach( $permissibleOps as $op ) {
				if( !StringUtils::isEmptyOrNull( $op ) ) {
					$ent->addPrivilege( $op ) ;
				}
			}
		}	
		else {
			$this->collectAllPathsForSelectorAliases( $nextLevelAliases, 
				                                      $alreadyLoadedPaths, 
				                                      $alreadyLoadedAliases ) ;
			
			if( Utils::isArrayEmpty( $permissibleOps ) ) {
				foreach( $alreadyLoadedPaths as $path ) {
					$ent->addRawSelector( $path ) ;
				}
			}
			else {
				$childEnt = new ent\Entitlement( "Child " . $ent->getAlias() . "-" . 
					                             $selectorAlias ) ;
				foreach( $alreadyLoadedPaths as $path ) {
					$childEnt->addRawSelector( $path ) ;
				}

				foreach( $permissibleOps as $op ) {
					if( !StringUtils::isEmptyOrNull( $op ) ) {
						$childEnt->addPrivilege( $op ) ;
					}
				}

				$ent->addChildEntitlement( $childEnt ) ;
			}
		}
	}

	private function collectAllPathsForSelectorAliases( 
		             $aliases, &$alreadyLoadedPaths, &$alreadyLoadedAliases ) {

		$nextLevelAliases = array() ;
		$query = "select selector_type, selector_value " .
                 "from user.entitlement_selector_alias " .
				 "where " .
	             "alias_name in ( '" . implode( "','", $aliases ) . "' )" ;

		$result  = parent::executeSelect( $query, 0 ) ;
		foreach( $aliases as $alias ) {
			array_push( $alreadyLoadedAliases, $alias ) ;
		}

	    while( $row = $result->fetch_array() ) {

	    	$selectorType  = $row[ "selector_type"   ] ;
	    	$selectorValue = trim( $row[ "selector_value"  ] ) ;

	    	if( $selectorType == 'PATH' ) {
	    		if( !in_array( $selectorValue, $alreadyLoadedPaths ) ) {
	    			array_push( $alreadyLoadedPaths, $selectorValue ) ;
	    		}
	    	}
	    	else if( $selectorType == 'SELECTOR_ALIAS' ) {
	    		if( !in_array( $selectorValue, $alreadyLoadedAliases ) ) {
	    			array_push( $nextLevelAliases, $selectorValue ) ;
	    		}
	    	}
	    }

	    if( count( $nextLevelAliases ) > 0 ) {
	    	$this->logger->debug( "Collecting paths recursively for aliases " .
	    	                      implode( ",", $nextLevelAliases ) ) ;
	    	$this->collectAllPathsForSelectorAliases( $nextLevelAliases,
	    		                                      $alreadyLoadedPaths,
	    		                                      $alreadyLoadedAliases ) ;
	    }
	}

	private function loadAliasEntitlementsForEntity( &$ent, $entityType, $entityName ) {

		$this->logger->debug( "Loading alias entitlements for $entityType $entityName" ) ;

		$query = <<< QUERY
select distinct entitlement_alias
from user.entity_entitlement
where 
	entity_type = '$entityType' and
    entity_name = '$entityName' and
    entitlement_type = 'ENT_ALIAS' 
QUERY;

		$aliases  = parent::getResultAsArray( $query, 0 ) ;
		foreach( $aliases as $entAlias ) {
			$childEnt = $this->loadEntitlementAlias( $entAlias ) ;
			$ent->addChildEntitlement( $childEnt ) ;
		}
	}

	private function loadEntitlementAlias( $alias ) {
		$ent = new ent\Entitlement( $alias ) ;
		$this->loadRawValuesForEntitlementAlias( $ent, $alias ) ;
		$this->loadChildEntitlementAliases( $ent, $alias ) ;
		return $ent ;
	}

	private function loadChildEntitlementAliases( $ent, $alias ) {

		$query = <<< QUERY
select distinct child_entitlement_alias
from user.entitlement_alias
where 
	alias_name = '$alias' and
	entitlement_type = 'ENT_ALIAS'
QUERY;

		$childAliases = parent::getResultAsArray( $query, 0 ) ;
		foreach( $childAliases as $childAlias ) {
			$childEnt = $this->loadEntitlementAlias( $childAlias ) ;
			$ent->addChildEntitlement( $childEnt ) ;
		}
	}

	private function loadRawValuesForEntitlementAlias( $ent, $alias ) {

		$query = <<< QUERY
select selector_alias, permissible_ops
from user.entitlement_alias
where 
	alias_name = '$alias' and
	entitlement_type = 'RAW'
QUERY;

		$result = parent::executeSelect( $query, 0 ) ;
	    while( $row = $result->fetch_array() ) {

	    	$selectorAlias  = trim( $row[ "selector_alias"  ] ) ;
	    	$permissibleOps = explode( ",", $row[ "permissible_ops" ] ) ;

			$this->loadRawEntitlementsForSelectorAlias
				                 ( $ent, $selectorAlias, $permissibleOps ) ;
	    }
	}
}

?>