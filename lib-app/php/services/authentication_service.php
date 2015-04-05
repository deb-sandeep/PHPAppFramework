<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/services/service.php" ) ;
require_once( DOCUMENT_ROOT . "/lib-app/php/dao/user_dao.php" ) ;

class AuthenticationException extends Exception {

	const USER_NOT_PRESENT = "USER_NOT_PRESENT" ;
	const PASSWORD_INVALID = "PASSWORD_INVALID" ;
	const USER_DEACTIVATED = "USER_DEACTIVATED" ;
	const TOKEN_INVALID    = "TOKEN_INVALID" ;

	function __construct( $code ) {
		$this->code = $code ;
	}

	public function __toString() {
		switch( $this->code ) {
			case self::USER_NOT_PRESENT:
				return "User is not registered with the system." ;

			case self::PASSWORD_INVALID:
				return "Invalid password" ;

			case self::USER_DEACTIVATED:
				return "User has been deactivated." ;

			case self::TOKEN_INVALID:
				return "Authentication token is invalid." ;

			default:
				return "Unknown authentication exception" ;
		}
	}
}

interface AuthenticationService extends Service {

	/**
	 * This function validates the credentials provided as inputs. In case of
	 * succssful validation, this function does not do anything visible (neither
	 * returns any value or throws exception.)
	 *
	 * @throws AuthenticationException In case of validation failures, this
	 *         method throws an AuthenticationException with the message 
	 *         signifying the cause of exception. The code of the exception
	 *         tells the specific type of exception.
	 */
	public function validateCredentials( $userName, $password ) ;

	/**
	 * This function validates if the given authentication token is valid. 
	 *
	 * @return The name of the user to which the token belongs
	 * @throws AuthenticationException In case the authentication token is invalid.
	 */
	public function validateAuthenticationToken( $tokenValue ) ;

	/**
	 * @return A unique authentication token by which the particular user will
	 *         be authenticated in subsequent requests.
	 */
	public function getNewAuthenticationTokenForUser( $userName, $tokenType ) ;

	public function deleteAuthenticationToken( $token ) ;

	public function updateLastAccessTime( $userName, $token ) ;
}

class AuthenticationServiceImpl implements AuthenticationService {

	var $userDAO ;

	function __construct() {
		$this->userDAO = new UserDAOImpl() ;
	}

	public function validateCredentials( $userName, $password ) {

		global $logger ;

		if( !isset( $password ) || empty( $password ) || $password == NULL ) {
			throw new AuthenticationException( AuthenticationException::PASSWORD_INVALID ) ;
		}

		$storedPassword = $this->userDAO->getUserPassword( $userName ) ;
		if( $storedPassword == NULL ) {
			throw new AuthenticationException( AuthenticationException::USER_NOT_PRESENT ) ;
		}
		else if( $storedPassword != $password ) {
			throw new AuthenticationException( AuthenticationException::PASSWORD_INVALID ) ;
		}
		$logger->debug( "Password for user '$userName' is valid." ) ;
	}

	public function getNewAuthenticationTokenForUser( $userName, $tokenType ) {

		global $logger ;

		$preHashString = $userName . time() . rand() ;
		$authenticationToken = md5( $preHashString ) ;

		$this->userDAO->saveNewAuthenticationToken( $userName, $authenticationToken, $tokenType ) ;

		$logger->debug( "New authentication token '$authenticationToken' generated for user '$userName'" ) ;
		return $authenticationToken ;
	}

	public function deleteAuthenticationToken( $token ) {
		$this->userDAO->deleteAuthenticationToken( $token ) ;
	}

	public function validateAuthenticationToken( $authToken ) {

		global $logger ;

		$this->userDAO->removeObsoleteTokens() ;
		$userName = $this->userDAO->getUserNameForToken( $authToken ) ;
		if( $userName == NULL ) {
			throw new AuthenticationException( AuthenticationException::TOKEN_INVALID )	;
		}
		
		$logger->debug( "Token '$authToken' is registered for user '$userName'" ) ;
		return $userName ;
	}

	public function updateLastAccessTime( $userName, $token ) {
		$this->userDAO->updateLastAccessTime( $userName, $token ) ;
	}
}

?>