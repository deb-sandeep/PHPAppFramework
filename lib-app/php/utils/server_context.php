<?php

class ServerContext {

	const KEY_LANDING_PAGE = "landing_page" ;
	const KEY_LOGOUT_PAGE  = "logout_page" ;
	const KEY_LOGIN_PAGE   = "login_page" ;
	const UNAUTH_REDIR_PAGE= "unauth_redir_page" ;

	static $logger ;
	static $defaultLandingPage = NULL ;
	static $unauthRedirPage = NULL ;
	static $logoutPage = NULL ;
	static $loginPage  = NULL ;
	static $appConfigs = NULL ;

	static function setAppConfigs( $configJSONData ) {
		
		self::$appConfigs = json_decode( $configJSONData ) ;
		if( json_last_error() != JSON_ERROR_NONE ) {
			throw new Exception( "Applications Configuration JSON format is " .
				                 "incorrect. Please check." ) ;
		}

		self::parseConfiguration() ;
	}

	private static function parseConfiguration() {

		self::$defaultLandingPage = Utils::getAttributeValue( self::$appConfigs,
			                                self::KEY_LANDING_PAGE, true ) ;

		self::$unauthRedirPage = Utils::getAttributeValue( self::$appConfigs,
			                                self::UNAUTH_REDIR_PAGE, true ) ;

		self::$logoutPage = Utils::getAttributeValue( self::$appConfigs,
			                                self::KEY_LOGOUT_PAGE, true ) ;

		self::$loginPage = Utils::getAttributeValue( self::$appConfigs,
			                                self::KEY_LOGIN_PAGE, true ) ;
	}

	static function getLandingPage( $appName=NULL ) {
		
		$landingPage = NULL ;
		if( $appName == NULL ) {
			$landingPage = self::$defaultLandingPage ;
		}
		else {
			if( property_exists( self::$appConfigs, $appName ) ) {
				$appConfig = self::$appConfigs->{ $appName } ;
				if( property_exists( $appConfig, self::KEY_LANDING_PAGE ) ) {
					$landingPage = $appConfig->landing_page ;
				}
				else {
					$landingPage = self::$defaultLandingPage ;
				}
			}
			else {
				throw new Exception( "$appName is not configured for Server." ) ;
			}
		}
		return $landingPage ;
	}

	static function getLogoutPage() {
		return self::$logoutPage ;
	}

	static function getLoginPage() {
		return self::$loginPage ;
	}

	static function getUnauthRedirPage() {
		return self::$unauthRedirPage ;
	}
}

ServerContext::$logger = Logger::getLogger( "ServerContext" ) ;

?>