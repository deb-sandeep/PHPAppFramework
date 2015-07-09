<?php

// Post login, this service should be used to access and modify user preferences.
// For reasons of optimization, the user profile is cached in memory and any
// changes to its attributes needs to be synched both at database and cache levels.
// This service provides a one stop shop for internal management of preferneces.
//
// Note that this service will fail if called without a valid user context. i.e
// ExecutionContext::getCurrentUser should return a valid user instance.

require_once( DOCUMENT_ROOT . "/lib-app/php/services/service.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/dao/user_dao.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/utils/cache.php" ) ;

class UserPreferenceService implements Service {

	private $logger ;
	private $userDAO ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
		$this->userDAO = new UserDAOImpl() ;
	}

	function getUserPreference( $key, $defaultValue = NULL ) {
		return $this->getCurrentUser()->getPreference( $key, $defaultValue ) ;
	}

	function getAllUserPreferences() {
		return $this->getCurrentUser()->getPreferences() ;
	}

	function saveUserPreferences( $keyValueAssocArray ) {

		$curUserName = ExecutionContext::getCurrentUserName() ;
		foreach( $keyValueAssocArray as $key => $value) {
			$this->userDAO->saveUserPreference( $curUserName, $key, $value ) ;
		}
		$this->refreshInMemoryCopy() ;
	}

	function saveUserPreference( $key, $value ) {
		$this->userDAO->saveUserPreference( ExecutionContext::getCurrentUserName(), 
			                                $key, $value ) ;
		$this->refreshInMemoryCopy() ;
	}

	function deleteUserPreferences( $keyArray ) {

		$curUserName = ExecutionContext::getCurrentUserName() ;
		foreach( $keyArray as $key ) {
			$this->userDAO->deleteUserPreference( $curUserName, $key ) ;
		}		
		
		$this->refreshInMemoryCopy() ;
	}

	function deleteUserPreference( $key ) {
		$this->userDAO->deleteUserPreference( ExecutionContext::getCurrentUserName(),
			                                  $key ) ;
		$this->refreshInMemoryCopy() ;
	}

	private function refreshInMemoryCopy() {

		$this->reloadUserPreferencesFromDB() ;
		Cache::setUserObject( "USER_OBJ", ExecutionContext::getCurrentUser() ) ;
	}

	private function reloadUserPreferencesFromDB() {

		$map  = $this->userDAO->loadUserPreferences( ExecutionContext::getCurrentUserName() ) ;
		$user = $this->getCurrentUser() ;

		foreach ( $map as $key => $value ) {
	    	$this->logger->debug( "Setting preference $key = $value" ) ;
	    	$user->setPreference( $key, $value ) ;
		}
	}

	private function getCurrentUser() {
		$user = ExecutionContext::getCurrentUser() ;
		if( $user == NULL ) {
			throw new Exception( "No current user found." ) ;
		}
		return $user ;
	}
}

?>