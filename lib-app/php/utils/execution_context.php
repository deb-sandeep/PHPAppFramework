<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/vo/user.php" ) ;

class ExecutionContext {

	static $logger ;
	static private $requestType = "WEB" ;
	static private $currentUser = NULL ;
	static private $currentUserName = NULL ;

	// A key unique to the execution context. This is derived from the
	// authentication token for the user, but can't help deduce the authentication
	// token. This key can be used to create isolation contexts, for example in
	// session specific cache keys.
	static private $uniqueKey = NULL ;

	static function setRequestType( $type ) {
		self::$requestType = $type ;
	}

	static function getRequestType() {
		return self::$requestType ;
	}

	static function isWebRequest() {
		return self::$requestType == REQUEST_TYPE_WEB ;
	}

	static function isAPIRequest() {
		return self::$requestType == REQUEST_TYPE_API ; 
	}

	static function setCurrentUser( &$user ) {
		self::$currentUser = $user ;
	}

	static function &getCurrentUser() {
		return self::$currentUser ;
	}

	static function setCurrentUserName( $userName ) {
		self::$currentUserName = $userName ;
	}

	static function getCurrentUserName() {
		return self::$currentUserName ;
	}

	static function getUserPreference( $key, $defaultValue = NULL ) {
		return self::$currentUser->getPreference( $key, $defaultValue ) ;
	}

	static function setUniqueKey( $uniqueKey ) {
		self::$uniqueKey = $uniqueKey ;
	}

	static function getUniqueKey() {
		return self::$uniqueKey ;
	}
}

ExecutionContext::$logger = Logger::getLogger( "ExecutionContext" ) ;

?>