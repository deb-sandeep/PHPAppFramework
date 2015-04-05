<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/utils/http_utils.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/services/authentication_service.php" ) ;

require_once( DOCUMENT_ROOT . "/lib-app/php/interceptors/interceptor.php" ) ;

abstract class AuthenticationInterceptor extends Interceptor {

	const COOKIE_PARAM_AUTH_TOKEN = "auth_token" ;
}

class WebAuthenticationInterceptor extends Interceptor {

	const SESSION_PARAM_REQ_PAGE = "authentication.requested_page" ;
	const SESSION_PARAM_ERR_MSGS = "authentication.error_messages" ;

	const REQ_PARAM_LOGIN       = "login" ;
	const REQ_PARAM_PASSWORD    = "password" ;
	const REQ_PARAM_REMEMBER_ME = "remember_me" ;

	const REQ_TYPE_UNAUTHENTICATED = "REQ_TYPE_UNAUTHENTICATED" ;
	const REQ_TYPE_PWD_AUTH        = "REQ_TYPE_PWD_AUTH" ;
	const REQ_TYPE_TOKEN_AUTH      = "REQ_TYPE_TOKEN_AUTH" ;
	const REQ_TYPE_LOGIN_PAGE_LOAD = "REQ_TYPE_LOGIN_PAGE_LOAD" ;
	const REQ_TYPE_LOGOUT          = "REQ_TYPE_LOGOUT" ;

	private $authenticationService ;

	private $userName ;
	private $authToken ;

	function __construct() {
        array_push( $GLOBALS[ 'interceptor_chain' ], $this ) ;
        $this->authenticationService = new AuthenticationServiceImpl() ;
	}

	function canInterceptRequest() {
		return ExecutionContext::isWebRequest() ;
	}

	function intercept() {

		global $db, $logger ;

		$requestType = $this->getRequestType() ;
		$logger->debug( "Request type is " . $requestType ) ;

		switch( $requestType ) {

			case self::REQ_TYPE_LOGIN_PAGE_LOAD:
				$logger->debug( "Interception action - Pass throught" ) ;
				break ;

			case self::REQ_TYPE_UNAUTHENTICATED:
				$logger->debug( "Interception action - Redirecting to login page" ) ;
				$this->processUnauthenticatedRequest() ;
				break ;

			case self::REQ_TYPE_PWD_AUTH:
				$logger->debug( "Interception action - Validating credentials" ) ;
				$this->processLoginPasswordAuthentication() ;
				$this->updateLastAccessTime() ;
				break ;

			case self::REQ_TYPE_TOKEN_AUTH:
				$logger->debug( "Interception action - Validating token" ) ;
				$this->processTokenAuthentication() ;
				$this->updateLastAccessTime() ;
				break ;

			case self::REQ_TYPE_LOGOUT:
				$logger->debug( "Interception action - processing logout" ) ;
				$this->processLogout() ;
				break ;
		}
	}

	private function updateLastAccessTime() {

		global $logger ;

		$logger->debug( "Setting last update time." ) ;
		$this->authenticationService->updateLastAccessTime( $this->userName,
			                                                $this->authToken ) ;
	}

	private function getRequestType() {

		// Note that ordering of the conditional blocks is important. The check
		// for logout will have to preceed the auth token, else the request will
		// land up in the logout.php without the interceptor processing the
		// logout trigger.
		if( PHP_SELF == LOGIN_PAGE_PATH ) {
			return self::REQ_TYPE_LOGIN_PAGE_LOAD ;
		}
		else if( PHP_SELF == LOGOUT_SERVICE ) {
			return self::REQ_TYPE_LOGOUT ;
		}
		else if( HTTPUtils::isRequestParameterSet( self::REQ_PARAM_LOGIN ) ) {
			return self::REQ_TYPE_PWD_AUTH ;
		}
		else if( HTTPUtils::isCookiePresent( 
			          AuthenticationInterceptor::COOKIE_PARAM_AUTH_TOKEN ) ) {
			return self::REQ_TYPE_TOKEN_AUTH ;
		}

		return self::REQ_TYPE_UNAUTHENTICATED ;
	}

	private function processUnauthenticatedRequest() {

		$this->saveRequestedPageDetailsInSession() ;
		HTTPUtils::redirectTo( LOGIN_PAGE_PATH ) ;
	}

	private function processLoginPasswordAuthentication() {

		global $logger ;

		$this->userName = HTTPUtils::getRequestParameterValue( self::REQ_PARAM_LOGIN ) ;
		$password = HTTPUtils::getRequestParameterValue( self::REQ_PARAM_PASSWORD ) ;

		try {
			$this->authenticationService
			     ->validateCredentials( $this->userName, $password ) ;
			$this->clearRequestedPageDetailsInSession() ;
			$this->setAuthenticationTokenCookie() ;
			ExecutionContext::setCurrentUser( $this->userName ) ;
		}
		catch( AuthenticationException $e ) {

			$logger->error( "Invalid login/password. Message = " . $e ) ;
			$this->setErrorMessageInSession( $e ) ;
			HTTPUtils::redirectTo( LOGIN_PAGE_PATH ) ;
		}
	}

	private function setAuthenticationTokenCookie() {

		global $logger ;

		$lifeInDays = 0 ;
		$tokenType  = "SESSION" ;

		if( HTTPUtils::isRequestParameterPresent( self::REQ_PARAM_REMEMBER_ME ) ) {
			$lifeInDays = NUM_DAYS_TO_REMEMBER_USER ;
			$tokenType = "REMEMBER_ME" ;
		}
		$this->authToken = $this->authenticationService
		                        ->getNewAuthenticationTokenForUser
		                          ( $this->userName, $tokenType ) ;

		$logger->debug( "Setting authentication token cookie. Cookie type = $tokenType" ) ;
		HTTPUtils::setCookie( AuthenticationInterceptor::COOKIE_PARAM_AUTH_TOKEN, 
			                  $this->authToken, $lifeInDays ) ;
	}

	private function processTokenAuthentication() {

		global $logger ;

		try {
			$this->authToken = HTTPUtils::getCookieValue( 
				          AuthenticationInterceptor::COOKIE_PARAM_AUTH_TOKEN ) ;
			$logger->debug( "Authentication token received = $this->authToken" ) ;
			$this->userName = $this->authenticationService
			                       ->validateAuthenticationToken( $this->authToken ) ;
			ExecutionContext::setCurrentUser( $this->userName ) ;
		}
		catch( AuthenticationException $e ) {

			$logger->error( "Invalid token. Message = " . $e ) ;
			$this->setErrorMessageInSession( $e ) ;
		    $this->saveRequestedPageDetailsInSession() ;
		    $this->deleteAuthenticationTokenCookie() ;
			HTTPUtils::redirectTo( LOGIN_PAGE_PATH ) ;
		}
	}

	private function processLogout() {

		global $logger ;

		$this->authToken = HTTPUtils::getCookieValue( 
						  AuthenticationInterceptor::COOKIE_PARAM_AUTH_TOKEN ) ;
		if( $this->authToken != NULL ) {
			$logger->debug( "Deleting authentication token cookie." ) ;
			$this->deleteAuthenticationTokenCookie() ;
			$this->authenticationService
			     ->deleteAuthenticationToken( $this->authToken ) ;
		}

		$logger->debug( "Invalidating session." ) ;
		HTTPUtils::invalidateSession() ;
		
		$logger->debug( "Redirecting user to post logout page." ) ;
		HTTPUtils::redirectTo( POST_LOGOUT_PAGE_PATH ) ;
	}

	private function deleteAuthenticationTokenCookie() {
		HTTPUtils::deleteCookie( AuthenticationInterceptor::COOKIE_PARAM_AUTH_TOKEN ) ;
	}

	private function saveRequestedPageDetailsInSession() {
		HTTPUtils::setValueInSession( self::SESSION_PARAM_REQ_PAGE, PHP_SELF ) ;
	}

	private function clearRequestedPageDetailsInSession() {
		HTTPUtils::eraseKeyFromSession( self::SESSION_PARAM_REQ_PAGE ) ;
	}

	private function setErrorMessageInSession( $message ) {

		$msgArray = &HTTPUtils::getValueFromSession( self::SESSION_PARAM_ERR_MSGS ) ;
		
		if( $msgArray == NULL ) {
			HTTPUtils::setValueInSession( self::SESSION_PARAM_ERR_MSGS, array() ) ;
			$msgArray = &HTTPUtils::getValueFromSession( self::SESSION_PARAM_ERR_MSGS ) ;
		}
		array_push( $msgArray, $message ) ;
	}
}

class APIAuthenticationInterceptor extends AuthenticationInterceptor {

	private $authenticationService ;

	private $authToken ;
	private $userName ;

	function __construct() {
        array_push( $GLOBALS[ 'interceptor_chain' ], $this ) ;
        $this->authenticationService = new AuthenticationServiceImpl() ;
	}

	function canInterceptRequest() {
		return ExecutionContext::isAPIRequest() ;
	}

	function intercept() {

		global $db, $logger ;

		try {
			$this->authToken = $this->validateAuthTokenCookiePresent() ;
			$logger->debug( "Authentication token received = $this->authToken" ) ;

			$this->userName = $this->authenticationService
			                       ->validateAuthenticationToken( $this->authToken ) ;

			$this->updateLastAccessTime() ;
			ExecutionContext::setCurrentUser( $this->userName ) ;
		}
		catch( AuthenticationException $e ) {
			$logger->error( "Invalid token. Message = " . $e ) ;
			APIInvoker::writeErrorResponse( "Authentication failed. Message $e" ) ;
		}

		if( PHP_SELF != API_GATEWAY_SERVICE_PATH ) {
			$logger->error( "API request is not being sent to API gateway" ) ;
			APIInvoker::writeErrorResponse( "API request is not sent to the API gateway." ) ;
		}
	}

	private function validateAuthTokenCookiePresent() {
		if( HTTPUtils::getCookieValue( self::COOKIE_PARAM_AUTH_TOKEN ) == NULL ) {
			throw new AuthenticationException( AuthenticationException::TOKEN_INVALID ) ;
		}
		return HTTPUtils::getCookieValue( self::COOKIE_PARAM_AUTH_TOKEN ) ;
	}

	private function updateLastAccessTime() {

		global $logger ;

		$logger->debug( "Setting last update time." ) ;
		$this->authenticationService->updateLastAccessTime( $this->userName,
			                                                $this->authToken ) ;
	}
}

new WebAuthenticationInterceptor() ;
new APIAuthenticationInterceptor() ;

?>
