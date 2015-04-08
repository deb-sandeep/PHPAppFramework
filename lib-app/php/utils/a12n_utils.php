<?php

// A12N stands for Authorization - on the similar lines that Internationalization
// is abbreviated as I18N
class A12NException extends Exception {

	const INVALID_ENTITLEMENT_PATTERN = "Invalid entitlement pattern." ;
	const INVALID_ENTITLEMENT_GUARD   = "Invalid entitlement guard." ;

	function __construct( $code, $message="" ) {
		$this->code = $code ;
		$this->message = $message ;
	}

	public function __toString() {
		if( $this->message == "" ) {
			return $this->code ;
		}
		return $this->code . "::" . $this->message ;
	}	
}


class A12NUtils {

	const OP_INCLUDE          = "+" ;
	const OP_EXCLUDE          = "-" ;
	const OP_INCLUDE_OVERRIDE = "(+)" ;
	const OP_EXCLUDE_OVERRIDE = "(-)" ;

	const PATTERN_COMPONENT_SEPARATOR = ":" ;
	const NUM_PATTERN_COMPONENTS      = 3 ;
	const NUM_GUARD_COMPONENTS        = 2 ;

	static $logger ;

	/**
	 * @return An array of three elements
	 *         [0] - + | - | (+) | (-)
	 *         [1] - entitlement resource type
	 *         [2] - entitlement pattern 
	 */
	static function getPatternComponents( $pattern ) {

		$components = explode( self::PATTERN_COMPONENT_SEPARATOR, $pattern ) ;
		if( sizeof( $components ) < self::NUM_PATTERN_COMPONENTS ) {
			throw new A12NException( A12NException::INVALID_ENTITLEMENT_PATTERN ) ;
		}

		if( !( ( $components[0] == self::OP_INCLUDE ) ||
			   ( $components[0] == self::OP_EXCLUDE ) ||
			   ( $components[0] == self::OP_INCLUDE_OVERRIDE ) ||
			   ( $components[0] == self::OP_EXCLUDE_OVERRIDE ) ) ) {

			throw new A12NException( A12NException::INVALID_ENTITLEMENT_PATTERN,
				    $components[0] . " is not a valid entitlement operation." ) ;
		}
		return $components ;
	}

	/**
	 * @return An array of two elements
	 *         [0] - entitlement resource type
	 *         [1] - entitlement pattern 
	 */
	static function getGuardComponents( $pattern ) {

		$components = explode( self::PATTERN_COMPONENT_SEPARATOR, $pattern ) ;
		if( sizeof( $components ) < self::NUM_GUARD_COMPONENTS ) {
			throw new A12NException( A12NException::INVALID_ENTITLEMENT_GUARD ) ;
		}
		return $components ;
	}
}

A12NUtils::$logger = Logger::getLogger( "A12NUtils" ) ;

?>