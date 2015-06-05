<?php

class JSONUtils {

	static $logger ;

	static function getJSONErrorMessage( $jsonLastError ) {

		$message = "" ;

	    switch( $jsonLastError ) {
	        case JSON_ERROR_NONE:
	            $message = ' - No errors';
	        break;
	        case JSON_ERROR_DEPTH:
	            $message = ' - Maximum stack depth exceeded';
	        break;
	        case JSON_ERROR_STATE_MISMATCH:
	            $message = ' - Underflow or the modes mismatch';
	        break;
	        case JSON_ERROR_CTRL_CHAR:
	            $message = ' - Unexpected control character found';
	        break;
	        case JSON_ERROR_SYNTAX:
	            $message = ' - Syntax error, malformed JSON';
	        break;
	        case JSON_ERROR_UTF8:
	            $message = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
	        break;
	        default:
	            $message = ' - Unknown error';
	        break;
	    }

	    return $message ;
    }
}

StringUtils::$logger = Logger::getLogger( "JSONUtils" ) ;

?>