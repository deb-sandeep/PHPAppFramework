<?php

class ServerContext {

	const KEY_DEF_LANDING_PAGE = "default_landing_page" ;

	static $logger ;
	static $defaultLandingPage = NULL ;
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

		self::$defaultLandingPage = self::getAttributeValue( self::$appConfigs,
			                                self::KEY_DEF_LANDING_PAGE, true ) ;
	}

	static function getDefaultLandingPage( $appName=NULL ) {
		
		$landingPage = NULL ;
		if( $appName == NULL ) {
			$landingPage = self::$defaultLandingPage ;
		}
		else {
			if( property_exists( self::$appConfigs, $appName ) ) {
				$appConfig = self::$appConfigs->{ $appName } ;
				if( property_exists( $appConfig, self::KEY_DEF_LANDING_PAGE ) ) {
					$landingPage = $appConfig->default_landing_page ;
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

	private static function getAttributeValue( $obj, $attributeName, 
											   $mandatory=false,
											   $defaultValue=NULL ) {

		if( !property_exists( $obj, $attributeName ) ) {
			if( $mandatory ) {
				throw new Exception( "Mandatory attribute $attributeName not " .
					                 "found in " . json_encode( $obj ) ) ;
			}
			return $defaultValue ;
		}

		return $obj->{ $attributeName } ;
	}
}

ServerContext::$logger = Logger::getLogger( "ServerContext" ) ;

?>