<?php

class Utils {

	static $logger ;

	static function getAttributeValue( $obj, $attributeName, 
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

Utils::$logger = Logger::getLogger( "Utils" ) ;

?>