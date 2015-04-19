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

	static function isArrayEmpty( $array ) {

		return ( count( $array ) == 0 || 
			     ( count( $array ) == 1 && 
			       trim( $array[0] )  == "" 
			     ) 
			   ) ;
	}
}

Utils::$logger = Logger::getLogger( "Utils" ) ;

?>