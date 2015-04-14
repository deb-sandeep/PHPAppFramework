<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/dao/abstract_dao.php" ) ;

interface UserDAO {

	// --------------- AUTHENTICATION FUNCTIONS --------------------------------
	/** @return NULL if a user with the given name is not found. */
	function getUserPassword( $userName ) ;

	function saveNewAuthenticationToken( $userName, $token, $tokenType ) ;

	function deleteAuthenticationToken( $token ) ;

	/** @return NULL if the given token is not found in the system. */
	function getUserNameForToken( $token ) ;

	function updateLastAccessTime( $userName, $authToken ) ;

	function removeObsoleteTokens() ;

	// --------------- USER PREFERENCE FUNCTIONS -------------------------------
	function loadUserPreferences( $userName ) ;

	function saveUserPreference( $userName, $key, $value ) ;

	// --------------- USER ENTITLEMENT FUNCTIONS ------------------------------
	function getUserRoles( $userName ) ;

	function getUserEntitlements( $userName, $roles=NULL ) ;
}

class UserDAOImpl extends AbstractDAO implements UserDAO {

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
			                   "where token = '$authToken'" ) ;

		parent::executeUpdate( "update user.user " .
			                   "set last_access_time = NOW() " .
			                   "where name = '$userName'" ) ;
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
			" )" 
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

		$roles = parent::getResultAsArray(
			"select distinct value from user.entitlement " .
			"where " .
				"value_type='ROLE' and " .
				"entity_type='USER' and " .
				"entity_name='$userName'"
		) ;

		if( count( $roles ) > 0 ) {
			$this->collectNestedRoles( $roles, $roles ) ;
		}

		return $roles ;
	}

	private function collectNestedRoles( &$allRoles, $thisLevelRoles ) {
		
		$childRoles = parent::getResultAsArray(
			"select distinct value from user.entitlement " .
			"where " .
				"value_type='ROLE' and " .
				"entity_type='ROLE' and " .
				"entity_name in ('" . implode( "','", $thisLevelRoles ) . "')" 
		) ;

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

	function getUserEntitlements( $userName, $roles=NULL ) {

		if( $roles == NULL ) {
			$roles = $this->getUserRoles( $userName ) ;
		}

		$query = 
		"select value " .
		"from user.entitlement " .
		"where " .
			"entity_type='USER' and " .
			"value_type='PATTERN' and " .
			"entity_name = '$userName' " .
		"union " .
		"select value " .
		"from user.entitlement " .
		"where " .
			"entity_type='ROLE' and " .
			"value_type='PATTERN' and " .
			"entity_name in ( '" . implode( "','", $roles ) . "')" .
		"union " .
		"select a.pattern " .
		"from user.entitlement_pattern_alias a, user.entitlement e " .
		"where " .
			"a.alias = e.value and " .
			"e.value_type = 'ALIAS' and " .
			"e.entity_name in ( '$userName', '" . implode( "','", $roles ) . "' ) " ;

		$entitlements = parent::getResultAsArray( $query ) ;
		return array_unique( $entitlements ) ;
	}
}

?>