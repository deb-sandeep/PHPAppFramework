<?php

class HTTPUtils {

	static function isRequestParameterSet( $paramName ) {

		if( isset( $_REQUEST[ $paramName ] ) ) {
			if( !empty( $_REQUEST[ $paramName ] ) ) {
				return true ;
			}
		}
		return false ;	
	}

	static function isRequestParameterPresent( $paramName ) {
		return isset( $_REQUEST[ $paramName ] ) ;
	}

	static function getRequestParameterValue( $paramName, $defaultVal = NULL ) {
		if( isset( $_REQUEST[ $paramName ] ) ) {
			return $_REQUEST[ $paramName ] ;
		}
		return $defaultVal ;
	}

	static function &getValueFromSession( $keyName, $defaultVal = NULL ) {
		if( isset( $_SESSION[ $keyName ] ) ) {
			return $_SESSION[ $keyName ] ;
		}
		return $defaultVal ;
	}

	static function setValueInSession( $keyName, $value ) {
		$_SESSION[ $keyName ] = $value ;
	}

	static function eraseKeyFromSession( $keyName ) {
		unset( $_SESSION[ $keyName ] ) ;
	}

	static function invalidateSession() {

		// Unset all of the session variables.
		$_SESSION = array();

		// If it's desired to kill the session, also delete the session cookie.
		// Note: This will destroy the session, and not just the session data!
		if( ini_get("session.use_cookies") ) {
		    $params = session_get_cookie_params() ;
		    setcookie( session_name(), '', time() - 42000,
		               $params["path"], $params["domain"],
		               $params["secure"], $params["httponly"] );
		}

		// Finally, destroy the session.
		session_destroy();		
	}

	static function isCookiePresent( $cookieName ) {
		return isset( $_COOKIE[ $cookieName ] ) ;
	}

	static function setCookie( $name, $value, $durationInDays ) {
		setcookie( $name, $value, time() + 60*60*24*$durationInDays, "/" ) ;
	}

	static function deleteCookie( $name ) {
		HTTPUtils::setCookie( $name, "", -1 ) ;
	}

	static function getCookieValue( $cookieName, $defaultVal = NULL ) {
		if( HTTPUtils::isCookiePresent( $cookieName ) ) {
			return $_COOKIE[ $cookieName ] ;
		}
		return $defaultVal ;
	}

	static function redirectTo( $pagePath ) {
		header( "Location: " . $pagePath ) ;
		die() ;
	}
}

?>