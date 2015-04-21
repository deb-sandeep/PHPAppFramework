<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/dao/user_dao.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/vo/user.php" ) ;

require_once( DOCUMENT_ROOT . "/lib-app/php/interceptors/interceptor.php" ) ;

class UserContextInterceptor extends Interceptor {

	private $logger ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
        array_push( $GLOBALS[ 'interceptor_chain' ], $this ) ;
	}

	function intercept() {

		$userName = ExecutionContext::getCurrentUserName() ;

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

		ExecutionContext::setCurrentUser( $user ) ;
	}	
}

new UserContextInterceptor() ;

?>