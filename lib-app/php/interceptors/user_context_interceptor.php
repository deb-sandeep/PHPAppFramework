<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/dao/user_dao.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/vo/user.php" ) ;

require_once( DOCUMENT_ROOT . "/lib-app/php/interceptors/interceptor.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/utils/cache.php" ) ;

class UserContextInterceptor extends Interceptor {

	private $logger ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
        array_push( $GLOBALS[ 'interceptor_chain' ], $this ) ;
	}

	function intercept() {

		$userName = ExecutionContext::getCurrentUserName() ;
		$user = Cache::getUserObject( "USER_OBJ" ) ;
		if( $user == NULL ) {
			$userDAO = new UserDAOImpl() ;
			$user = new User( $userName ) ;

			$map = $userDAO->loadUserPreferences( $userName ) ;
			foreach ($map as $key => $value) {
		    	$this->logger->debug( "Setting preference $key = $value" ) ;
		    	$user->setPreference( $key, $value ) ;
			}

			$roles = $userDAO->getUserRoles( $userName )	;
			$user->addRoles( $roles ) ;

			$ent = $userDAO->getEntitlementsForUser( $userName ) ;
			$user->setEntitlement( $ent ) ;

			Cache::setUserObject( "USER_OBJ", $user ) ;
		}
		else {
			$this->logger->debug( "Got user object from cache" ) ;
		}
		
		ExecutionContext::setCurrentUser( $user ) ;
	}	
}

new UserContextInterceptor() ;

?>