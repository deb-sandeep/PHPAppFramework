<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/utils/a12n_utils.php" ) ;

class AccessFlags {

	private $opType ;
	private $isReadPermitted ;
	private $isWritePermitted ;
	private $isExecutePermitted ;

	function __construct( $opType, $read, $write, $exec ) {
		$this->opType = $opType ;
		$this->isReadPermitted = $read ;
		$this->isWritePermitted = $write ;
		$this->isExecutePermitted = $exec ;
	}

	function isReadPermitted()    { return $this->isReadPermitted;    }
	function isWritePermitted()   { return $this->isWritePermitted;   }
	function isExecutePermitted() { return $this->isExecutePermitted; }

	function hasPrivileges() {
		return $this->isReadPermitted || 
		       $this->isWritePermitted || 
		       $this->isExecutePermitted ;
	}

	function superimpose( $anotherAccessFlag ) {

		if( !is_null( $anotherAccessFlag->isReadPermitted ) ) {
			$this->isReadPermitted = $anotherAccessFlag->isReadPermitted ;
		}
		if( !is_null( $anotherAccessFlag->isWritePermitted ) ) {
			$this->isWritePermitted = $anotherAccessFlag->isWritePermitted ;
		}
		if( !is_null( $anotherAccessFlag->isExecutePermitted ) ) {
			$this->isExecutePermitted = $anotherAccessFlag->isExecutePermitted ;
		}
	}

	function __toString() {

		$str = "" ;
		if( $this->opType == Entitlement::OP_INCLUDE ) {

			if( $this->isReadPermitted    ) { $str .= "r"; } ;
			if( $this->isWritePermitted   ) { $str .= "w"; } ;
			if( $this->isExecutePermitted ) { $str .= "x"; } ;
		}
		else if( $this->opType == Entitlement::OP_INCLUDE_OVERRIDE ) {

			if     ( is_null( $this->isReadPermitted ) ) { $str .= "."; }
			else if( $this->isReadPermitted )            { $str .= "+"; }
			else                                         { $str .= "-"; }

			if     ( is_null( $this->isWritePermitted ) ){ $str .= "."; }
			else if( $this->isWritePermitted )           { $str .= "+"; }
			else                                         { $str .= "-"; }

			if     ( is_null($this->isExecutePermitted) ){ $str .= "."; }
			else if( $this->isExecutePermitted )         { $str .= "+"; }
			else                                         { $str .= "-"; }
		}
		else {
			$str = "XXX" ;
		}

		return $str ;
	}
}

class Entitlement {

	const OP_INCLUDE          = "+" ;
	const OP_EXCLUDE          = "-" ;
	const OP_INCLUDE_OVERRIDE = "(+)" ;
	const OP_EXCLUDE_OVERRIDE = "(-)" ;

	const PATTERN_COMPONENT_SEPARATOR = ":" ;
	const PATTERN_NUM_PARTS = 4 ;

	private $logger ;

	private $opType ;
	private $resourceType ;
	private $accessFlags ;
	private $pattern ;


	function __construct( $entString ) {

		$this->logger = Logger::getLogger( __CLASS__ ) ;
		$this->parseEntitlementString( $entString ) ;
	}	

	private function parseEntitlementString( $entString ) {

		$this->logger->debug( "Parsing entitlement string '$entString'" ) ;
		$entComponents = explode( self::PATTERN_COMPONENT_SEPARATOR, $entString ) ;

		if( sizeof( $entComponents ) != self::PATTERN_NUM_PARTS ) {
			throw new A12NException( A12NException::INVALID_ENTITLEMENT_PATTERN,
				   "Entitlement string '$entString' does not have all parts.") ;
		}

		$this->parseOpType( trim( $entComponents[0] ) ) ;
		$this->parseAccessFlags( trim( $entComponents[1] ) ) ;
		$this->resourceType = trim( $entComponents[2] ) ;
		$this->pattern = trim( $entComponents[3] ) ;
	}

	private function parseOpType( $type ) {

		if( !( ( $type == self::OP_INCLUDE ) ||
			   ( $type == self::OP_EXCLUDE ) ||
			   ( $type == self::OP_INCLUDE_OVERRIDE ) ||
			   ( $type == self::OP_EXCLUDE_OVERRIDE ) ) ) {

			throw new A12NException( A12NException::INVALID_ENTITLEMENT_PATTERN,
				            $type . " is not a valid entitlement operation." ) ;
		}
		$this->opType = $type ;
	}

	private function parseAccessFlags( $privString ) {

		$isReadPermitted    = false ;
		$isWritePermitted   = false ;
		$isExecutePermitted = false ;

		$chars = str_split( $privString ) ;

		if( $this->isIncludeOp() ) {

			if( sizeof( $chars ) == 1 && $chars[0] == "" ) {
				$isReadPermitted    = true ;
				$isWritePermitted   = true ;
				$isExecutePermitted = true ;
			}
			else {
				if( !preg_match( "!^[rR]?[wW]?[xX]?$!", $privString ) ) {
					throw new A12NException( 
							A12NException::INVALID_ENTITLEMENT_PATTERN,
				           	$privString . " should be ^[rR]?[wW]?[xX]?$" ) ;
				}
				else {
					$isReadPermitted    = false ;
					$isWritePermitted   = false ;
					$isExecutePermitted = false ;
					if( in_array( "r", $chars ) || in_array( "R", $chars ) ) {
						$isReadPermitted = true ;
					}
					if( in_array( "w", $chars ) || in_array( "W", $chars ) ) {
						$isWritePermitted = true ;
					}
					if( in_array( "x", $chars ) || in_array( "X", $chars ) ) {
						$isExecutePermitted = true ;
					}
				}
			}
		}
		else if( $this->isIncludeOverrideOp() ) {

			if( sizeof( $chars ) == 1 && $chars[0] == "" ) {
				$privString = "---" ;
				$chars = str_split( $privString ) ;
				$this->logger->debug( "Priv string = $privString" ) ;
			}

			if( !preg_match( "!^[\+\-\.]{3}$!", $privString ) ) {
				throw new A12NException( 
						A12NException::INVALID_ENTITLEMENT_PATTERN,
			           	$privString . " should be ^[\+\-\.]{3}$" ) ;
			}
			else {
				$isReadPermitted    = NULL ;
				$isWritePermitted   = NULL ;
				$isExecutePermitted = NULL ;

				if     ( $chars[0] == "+" ) { $isReadPermitted = true ; }
				else if( $chars[0] == "-" ) { $isReadPermitted = false ; }
				
				if     ( $chars[1] == "+" ) { $isWritePermitted = true ; }
				else if( $chars[1] == "-" ) { $isWritePermitted = false ; }

				if     ( $chars[2] == "+" ) { $isExecutePermitted = true ; }
				else if( $chars[2] == "-" ) { $isExecutePermitted = false ; }
			}
		}

		$this->accessFlags = new AccessFlags( $this->opType, $isReadPermitted, 
			                          $isWritePermitted, $isExecutePermitted ) ;
	}

	function match( $guardPath ) {
		if( StringUtils::matchSimplePattern( $this->pattern, $guardPath ) ) {
			return clone $this->accessFlags ;
		}
		return null ;
	}

	function getOpType()          { return $this->opType;             }
	function getResourceType()    { return $this->resourceType;       }
	function getPattern()         { return $this->pattern;            }
	function isIncludeOp()        { return $this->opType == self::OP_INCLUDE; }
	function isExcludeOp()        { return $this->opType == self::OP_EXCLUDE; }
	function isIncludeOverrideOp(){ return $this->opType == self::OP_INCLUDE_OVERRIDE; }
	function isExcludeOverrideOp(){ return $this->opType == self::OP_EXCLUDE_OVERRIDE; }

	function isReadPermitted() { 
		return $this->accessFlags->isReadPermitted();    
	}

	function isWritePermitted() { 
		return $this->accessFlags->isWritePermitted();   
	}

	function isExecutePermitted() { 
		return $this->accessFlags->isExecutePermitted() ; 
	}

	function __toString() {

		return $this->opType . ":" . $this->accessFlags 
		                     . ":" . $this->resourceType
		                     . ":" . $this->pattern ;
	}
}

?>