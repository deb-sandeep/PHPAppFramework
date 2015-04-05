<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/dao/user_dao.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/vo/user.php" ) ;

require_once( DOCUMENT_ROOT . "/lib-app/php/interceptors/interceptor.php" ) ;

class UserPreferenceInterceptor extends Interceptor {

	function __construct() {
        array_push( $GLOBALS[ 'interceptor_chain' ], $this ) ;
	}

	function intercept() {

		global $logger ;
		
		$userDAO = new UserDAOImpl() ;
		$userPrefs = ExecutionContext::getCurrentUser()->preferences ;

		$result = $userDAO->loadUserPreferences( ExecutionContext::getCurrentUser()->userName ) ;
		if( $result->num_rows > 0 ) {
		    while( $row = $result->fetch_array() ) {

		    	$key   = $row[ 'key' ] ;
		    	$value = $row[ 'value' ] ;

		    	$logger->debug( "Setting preference $key = $value" ) ;
		    	$userPrefs->setPreference( $key, $value ) ;
		    }
		}
	}	
}

new UserPreferenceInterceptor() ;

?>