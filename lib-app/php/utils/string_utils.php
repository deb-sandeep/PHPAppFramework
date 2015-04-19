<?php

class StringUtils {

	static $logger ;

	private static function convertToPregPattern( $input ) {

		self::$logger->debug( "Input = $input" ) ;
		$returnVal = preg_quote( $input ) ;
		$returnVal = str_replace( "\*\*/", ".*",    $returnVal ) ;
		$returnVal = str_replace( "\*\*",  ".*",    $returnVal ) ;
		$returnVal = str_replace( "\*",    "[^/]*", $returnVal ) ;
		$returnVal = str_replace( "\?",    ".?",    $returnVal ) ;
		$returnVal = "!^" . $returnVal .   "$!" ;
		self::$logger->debug( "Preg pattern = $returnVal" ) ;

		return $returnVal ;
	}

	static function matchSimplePattern( $simplePattern, $stringToMatch ) {

		$pregPattern = self::convertToPregPattern( $simplePattern ) ;
		self::$logger->debug( "String to match = '$stringToMatch' " ) ;
		self::$logger->debug( "Pattern         = '$pregPattern' " ) ;

		$matchResult = false ;
		if( preg_match( $pregPattern, $stringToMatch ) ) {
			$matchResult = true ;
		}	
		self::$logger->debug( "Match result    = $matchResult" ) ;
		return $matchResult ;
	}

	static function isEmptyOrNull( $string ) {

		if( is_null( $string ) || strlen( trim( $string ) ) == 0 ) return true ;
		return false ;
	}
}

StringUtils::$logger = Logger::getLogger( "StringUtils" ) ;

?>