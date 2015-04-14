<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/vo/user.php" ) ;

class ExecutionContext {

	static private $requestType = "WEB" ;
	static private $currentUser = NULL ;
	static $logger ;

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

	static function setCurrentUser( $userName ) {
		self::$currentUser = new User( $userName ) ;
	}

	static function getUserPreference( $key, $defaultValue = NULL ) {
		return self::$currentUser->getPreference( $key, $defaultValue ) ;
	}

	static function &getCurrentUser() {
		return self::$currentUser ;
	}
}

ExecutionContext::$logger = Logger::getLogger( "ExecutionContext" ) ;

?>