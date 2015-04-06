<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/dao/user_dao.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/vo/user.php" ) ;

require_once( DOCUMENT_ROOT . "/lib-app/php/interceptors/interceptor.php" ) ;

class UserPreferenceInterceptor extends Interceptor {

	private $logger ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
        array_push( $GLOBALS[ 'interceptor_chain' ], $this ) ;
	}

	function intercept() {

		$userDAO = new UserDAOImpl() ;
		$user = ExecutionContext::getCurrentUser() ;

		$result = $userDAO->loadUserPreferences( $user->getUserName() ) ;
		if( $result->num_rows > 0 ) {
		    while( $row = $result->fetch_array() ) {

		    	$key   = $row[ 'key' ] ;
		    	$value = $row[ 'value' ] ;

		    	$this->logger->debug( "Setting preference $key = $value" ) ;
		    	$user->setPreference( $key, $value ) ;
		    }
		}
	}	
}

new UserPreferenceInterceptor() ;

?>