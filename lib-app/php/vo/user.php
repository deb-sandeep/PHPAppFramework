<?php

class User {

	public $userName = NULL ;
	public $preferences = NULL ;

	function __construct( $name ) {
		$this->userName = $name ;
		$this->preferences = new UserPreference() ;
	}
}

class UserPreference {

	private $nvpMap ;

	function __construct() {
		$this->nvpMap = array() ;
	}

	function setPreference( $key, $value ) {
		$this->nvpMap[ $key ] = $value ;
	}

	function getPreferences() {
		return $this->nvpMap ;
	}

	function get( $key, $defaultValue=NULL ) {
		if( array_key_exists( $key, $this->nvpMap ) ) {
			return $this->nvpMap[ $key ] ;
		}
		return $defaultValue ;
	}
}

?>