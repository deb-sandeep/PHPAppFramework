<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/utils/" . "execution_context.php" ) ;

class Cache {

	const CACHE_HOLD_TIME_IN_SEC = 600 ;

	static $logger ;
	static $cache = NULL ;

	static function initialize() {
		self::$cache = new Memcached() ;
		self::$cache->addServer( "127.0.0.1", 11211 ) ;
	}

	static function setUserObject( $key, $obj, 
		                           $expiry = self::CACHE_HOLD_TIME_IN_SEC ) {
		self::$cache->set( self::getQualifiedUserKey( $key ), $obj, $expiry ) ;
	}

	static function getUserObject( $key ) {
		$obj = self::$cache->get( self::getQualifiedUserKey( $key ) ) ;
		if( $obj == FALSE ) {
			return NULL ;
		}
		return $obj ;
	}

	private static function getQualifiedUserKey( $key ) {
		return ExecutionContext::getCurrentUserName() ."_".
		       ExecutionContext::getUniqueKey() ."_".
		       $key ;	
	}
}

?>