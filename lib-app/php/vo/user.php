<?php

class User {

	const INCLUSION_RIGHTS_KEY = "inclusion.rights" ;
	const EXCLUSION_RIGHTS_KEY = "exclusion.rights" ;

	private $logger ;

	private $userName = NULL ;
	private $preferences = NULL ;
	private $rights = NULL ;

	function __construct( $name ) {

		$this->logger = Logger::getLogger( __CLASS__ ) ;
		
		$this->userName = $name ;
		$this->preferences = array() ;
		$this->rights = array() ;
	}

	function getUserName() {
		return $this->userName ;
	}

	function getPreference( $key, $defaultValue=NULL ) {
		if( array_key_exists( $key, $this->preferences ) ) {
			return $this->preferences[ $key ] ;
		}
		return $defaultValue ;
	}

	function getPreferences() {
		return $this->preferences ;
	}

	function getInclusionRights( $rightType ) {

		$inclusionRights = NULL ;
		if( array_key_exists( $rightType, $this->rights ) ) {
			$rightTypeContainer = $this->rights[ $rightType ] ;
			$inclusionRights = $rightTypeContainer[ self::INCLUSION_RIGHTS_KEY ] ;
		}
		return $inclusionRights ;
	}

	function getExclusionRights( $rightType ) {

		$exclusionRights = NULL ;
		if( array_key_exists( $rightType, $this->rights ) ) {
			$rightTypeContainer = $this->rights[ $rightType ] ;
			$exclusionRights = $rightTypeContainer[ self::EXCLUSION_RIGHTS_KEY ] ;
		}
		return $exclusionRights ;
	}

	// --------------------- Setters -------------------------------------------
	function setPreference( $key, $value ) {
		$this->preferences[ $key ] = $value ;
	}

	function addRight( $rightType, $isInclusionRight, $rightPattern ) {
		
		$rightTypeContainer = NULL ;
		$rightContainer     = NULL ;

		if( !array_key_exists( $rightType, $this->rights ) ) {
			
			$rightTypeContainer = array() ;
			$rightTypeContainer[ self::INCLUSION_RIGHTS_KEY ] = array() ;
			$rightTypeContainer[ self::EXCLUSION_RIGHTS_KEY ] = array() ;

			$this->rights[ $rightType ] = &$rightTypeContainer ;
		}
		else {
			$rightTypeContainer = &$this->rights[ $rightType ] ;
		}

		if( $isInclusionRight ) {
			$this->logger->debug( "Adding inclusion right - $rightPattern" ) ;
			$rightContainer = &$rightTypeContainer[ self::INCLUSION_RIGHTS_KEY ] ;
		}
		else {
			$this->logger->debug( "Adding exclusion right - $rightPattern" ) ;
			$rightContainer = &$rightTypeContainer[ self::EXCLUSION_RIGHTS_KEY ] ;
		}

		if( !in_array( $rightPattern, $rightContainer ) ) {
			array_push( $rightContainer, $rightPattern ) ;
		}
	}
}

?>