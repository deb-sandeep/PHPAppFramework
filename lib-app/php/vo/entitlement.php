<?php
namespace sandy\phpfw\entitlement ;

require_once( DOCUMENT_ROOT . "/lib-app/php/utils/string_utils.php" ) ;

class EntitlementException extends \Exception {

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

class Selector {

	const OP_INCLUDE          = "+" ;
	const OP_EXCLUDE          = "-" ;
	const OP_INCLUDE_OVERRIDE = "(+)" ;
	const OP_EXCLUDE_OVERRIDE = "(-)" ;

	const PATTERN_SEPARATOR = ":" ;
	const PATTERN_NUM_PARTS = 3 ;

	private $logger ;

	private $opType ;
	private $resourceType ;
	private $pattern ;

	function __construct( $selectorStr ) {

		$this->logger = \Logger::getLogger( __CLASS__ ) ;
		$this->parseSelectorString( $selectorStr ) ;
	}	

	private function parseSelectorString( $selectorString ) {

		$selComponents = explode( self::PATTERN_SEPARATOR, $selectorString ) ;

		if( sizeof( $selComponents ) != self::PATTERN_NUM_PARTS ) {
			throw new EntitlementException( 
				 EntitlementException::INVALID_ENTITLEMENT_PATTERN,
				 "Selector string '$selectorString' does not have all parts.") ;
		}

		$this->opType       = $this->parseOpType( trim( $selComponents[0] ) ) ;
		$this->resourceType = trim( $selComponents[1] ) ;
		$this->pattern      = trim( $selComponents[2] ) ;
	}

	private function parseOpType( $type ) {

		if( !( ( $type == self::OP_INCLUDE ) ||
			   ( $type == self::OP_EXCLUDE ) ||
			   ( $type == self::OP_INCLUDE_OVERRIDE ) ||
			   ( $type == self::OP_EXCLUDE_OVERRIDE ) ) ) {

			throw new EntitlementException(
                            EntitlementException::INVALID_ENTITLEMENT_PATTERN,
				            $type . " is not a valid selector operation." ) ;
		}
		return $type ;
	}

	static function compareTo( $selA, $selB ) {

		if( $selA->getResourceType() != $selB->getResourceType() ) {
			return strcmp( $selA->getResourceType(), $selB->getResourceType() ) ;
		}
		else {
			if( $selA->getOpType() == $selB->getOpType() ) {
				return strcmp( $selA->getPattern(), $selB->getPattern() ) ;
			}
			else {
				return self::getOpPriorityForDisplay( $selA->getOpType() ) -
					   self::getOpPriorityForDisplay( $selB->getOpType() ) ;
			}
		}
		return 0 ;
	}

	static private function getOpPriorityForDisplay( $type ) {

		switch( $type ) {
			case Selector::OP_INCLUDE          : return 1 ;
			case Selector::OP_INCLUDE_OVERRIDE : return 2 ;
			case Selector::OP_EXCLUDE          : return 3 ;
			case Selector::OP_EXCLUDE_OVERRIDE : return 4 ;
		}
		return 0 ;
	}

	function matches( $resType, $path ) {
		if( $resType == $this->resourceType ) {
			return \StringUtils::matchSimplePattern( $this->pattern, $path ) ;
		}
		return false ;
	}

	function getOpType()          { return $this->opType;             }
	function getResourceType()    { return $this->resourceType;       }
	function getPattern()         { return $this->pattern;            }
	function isIncludeOp()        { return $this->opType == self::OP_INCLUDE; }
	function isExcludeOp()        { return $this->opType == self::OP_EXCLUDE; }
	function isIncludeOverrideOp(){ return $this->opType == self::OP_INCLUDE_OVERRIDE; }
	function isExcludeOverrideOp(){ return $this->opType == self::OP_EXCLUDE_OVERRIDE; }

	function __toString() {

		return str_pad( $this->opType, 3, " ", STR_PAD_LEFT ) 
					. ":" . $this->resourceType
		            . ":" . $this->pattern ;
	}
}

class Operation {

	const OP_ACCESS = "+" ;
	const OP_FORBID = "-" ;

	const PATTERN_SEPARATOR = ":" ;
	const MAGIC_NO = 3.421 ;

	private $forbiddenFlag ;
	private $opName ;
	private $access ;
	private $logger ;

	function __construct( $fromInside=0 ) {
		if( $fromInside != self::MAGIC_NO ) {
			throw new \Exception( "Don't instantiate Operation directly." ) ;
		}
		$this->logger = \Logger::getLogger( __CLASS__ ) ;
	}

	static function fromRawOp( $rawOp ) {
		$op = new Operation( self::MAGIC_NO ) ;
		$op->parseRawOpString( $rawOp ) ;
		return $op ;
	}

	static function fromAccessFlagAndOpName( $opName, $isForbidden=false ) {
		$op = new Operation( self::MAGIC_NO ) ;
		$op->forbiddenFlag = $isForbidden ;
		$op->opName = $opName ;
		return $op ;		
	}

	private function parseRawOpString( $rawOp ) {

		$components = explode( self::PATTERN_SEPARATOR, $rawOp ) ;

		if( sizeof( $components ) == 1 ) {

			$this->access        = self::OP_ACCESS ;
			$this->forbiddenFlag = false ;
			$this->opName        = trim( $components[0] ) ;
		}
		else if( sizeof( $components ) == 2 ) {

			$this->access = trim( $components[0] ) ;
			$this->opName = trim( $components[1] ) ;
			if( !( $this->access == self::OP_ACCESS || 
				   $this->access == self::OP_FORBID ) ) {

				throw new EntitlementException( 
					          EntitlementException::INVALID_ENTITLEMENT_PATTERN,
					          "Access format in '$this->access' is invalid.") ;
			}
			$this->forbiddenFlag = ( $this->access == self::OP_FORBID ) ? 
			                       true : false ;
		}
		else {
			throw new EntitlementException( 
				              EntitlementException::INVALID_ENTITLEMENT_PATTERN,
				              "Access format '$this->access' is invalid.") ;
		}
	}

	function isForbidden() { return $this->forbiddenFlag ; }

	function getOpName() { return $this->opName ; }

	function __toString() {
		return str_pad( $this->access, 3, " ", STR_PAD_LEFT ) . 
		       ":" . $this->opName ;
	}
}

class AccessPrivilege {

	const AP_ACCESS     = "+" ;
	const AP_FORBID     = "-" ;
	const AP_INDEFINITE = "-" ;
	const AP_CONFLICT   = "0" ;

	private $opsMap ;
	private $logger ;

	function __construct() {
		$this->logger = \Logger::getLogger( __CLASS__ ) ;
		$this->opsMap = array() ;
	}

	function addRawPrivilege( $opString ) {
		$this->addPrivilege( Operation::fromRawOp( $opString ) ) ;
	}

	function addPrivilege( $op ) {

		if( !($op instanceof Operation) ) {
			throw new Exception( "Invalid argument. Is not of type Operation." ) ;
		}

		$existingCount = 0 ;
		$opName = $op->getOpName() ;

		if( array_key_exists( $opName, $this->opsMap ) ) {
			$existingCount = $this->opsMap[ $opName ] ;
		}

		if( $op->isForbidden() ) {
			$this->opsMap[ $opName ] = $existingCount - 1 ;
		}
		else {
			$this->opsMap[ $opName ] = $existingCount + 1 ;
		}
	}

	function getAccessPrivilege( $opName ) {

		$priv = self::AP_INDEFINITE ;
		if( array_key_exists( $opName, $this->opsMap ) ) {
			if( $this->opsMap[ $opName ] > 0 ) {
				$priv = self::AP_ACCESS ;
			}
			else if( $this->opsMap[ $opName ] < 0 ) {
				$priv = self::AP_FORBID ;
			}
			else {
				$priv = self::AP_CONFLICT ;
			}
		}
		return $priv ;
	}

	function isForbidden( $opName ) { 
		return $this->getAccessPrivilege( $opName ) == self::AP_FORBID ;
	}

	function isAccessible( $opName ) { 
		return $this->getAccessPrivilege( $opName ) == self::AP_ACCESS ;
	}

	function isIndefinite( $opName ) { 
		return $this->getAccessPrivilege( $opName ) == self::AP_INDEFINITE ;
	}

	function isConflict( $opName ) { 
		return $this->getAccessPrivilege( $opName ) == self::AP_CONFLICT ;
	}

	function normalize() {

		foreach( $this->opsMap as $opName => $count ) {
			$existingCount = $this->opsMap[ $opName ] ;
			if( $existingCount > 0 ) {
				$this->opsMap[ $opName ] = 1 ;
			}
			else if( $existingCount < 0 ) {
				$this->opsMap[ $opName ] = -1 ;
			}
		}
	}

	function merge( $anotherAccessPrivilege ) {

		if( $anotherAccessPrivilege != null ) {
			foreach( $anotherAccessPrivilege->opsMap as $opName => $count ) {
				$newCount = $count ;
				if( array_key_exists( $opName, $this->opsMap ) ) {
					$newCount += $this->opsMap[ $opName ] ;
				}
				$this->opsMap[ $opName ] = $newCount ;
			}
		}
	}

	function __toString() {

		$str = "" ;
		foreach( $this->opsMap as $opName => $count ) {
			$str .= $this->getAccessPrivilege( $opName ) . ":" . $opName . "\n" ;
		}
		return $str ;
	}
}

class Entitlement {

	private $alias ;
	private $selectors ;
	private $privileges ;
	private $childEntitlements ;
	private $parent ;
	private $numSelectors = 0 ;
	private $logger ;

	function __construct( $alias=NULL ) {

		$this->logger = \Logger::getLogger( __CLASS__ ) ;
		$this->alias = $alias ;
		$this->selectors = array(
			Selector::OP_INCLUDE          => array() ,
			Selector::OP_EXCLUDE          => array() ,
			Selector::OP_INCLUDE_OVERRIDE => array() ,
			Selector::OP_EXCLUDE_OVERRIDE => array() 
		) ;
		$this->privileges        = array() ;
		$this->childEntitlements = array() ;
		$this->parent            = NULL ;
	}

	function setParent( &$parent ) { $this->parent = $parent ; }

	function &getParent() { return $this->parent ; }

	function addRawSelector( $selectorString ) {
		$this->addSelector( new Selector( $selectorString ) ) ;
	}

	function addSelector( $selector ) {

		$container = &$this->selectors[ $selector->getOpType() ] ;
		if( !in_array( $selector, $container ) ) {
			$this->numSelectors++ ;
			array_push( $container, $selector ) ;
			usort( $container, array( 'sandy\phpfw\entitlement\Selector', 
				                      'compareTo' ) ) ;
		}
	}

	function addPrivileges( $operations ) {

		foreach( $operations as $operation ) {
			$this->addPrivilege( $operation ) ;
		}
	}

	function addPrivilege( $operation ) {

		$opObj = NULL ;
		if( $operation instanceof Operation ) {
			$opObj = $operation ;
		}
		else if( is_string( $operation ) ) {
			$opObj = Operation::fromRawOp( $operation ) ;
		}
		else {
			throw new Exception( "operation is neither of type String or of " .
				                 "type Operation" ) ;
		}

		if( !in_array( $opObj, $this->privileges ) ) {
			array_push( $this->privileges, $opObj ) ;
		}
	}

	function addChildEntitlement( $entitlement ) {

		if( !$this->canChildCauseInfiniteRecursion( $entitlement ) ) {

			if( !in_array( $entitlement, $this->childEntitlements ) ) {
				array_push( $this->childEntitlements, $entitlement ) ;
				$entitlement->setParent( $this ) ;
				return true ;
			}
			else {
				$this->logger->debug( "Not adding entitlement. Already present" ) ;
				return false ;				
			}
		}
		else {
			$this->logger->debug( "Not adding entitlement. Will cause bad recursion" ) ;
			return false ;
		}
	}

	private function canChildCauseInfiniteRecursion( $potentialChild ) {

		$ancestor = $this ;
		while( $ancestor != NULL ) {
			if( $ancestor == $potentialChild ) {
				return true ;
			}
			$ancestor = $ancestor->getParent() ;
		}
		return false ;
	}

	function getAlias() { return $this->alias ; }
	function getSelectors() { return $this->selectors ; }
	function getPrivileges() { return $ths->privileges ; } 

	function computeAccessPrivilege( $resType, $path ) {

		$privilege = new AccessPrivilege() ;

		foreach( $this->childEntitlements as $childEnt ) {
			$childPriv = $childEnt->computeAccessPrivilege( $resType, $path ) ;
			$privilege->merge( $childPriv ) ;
		}
		
		if( $this->matchSelectors( $resType, $path ) ) {
			foreach( $this->privileges as $op ) {
				$privilege->addPrivilege( $op ) ;
			}
		}
		$privilege->normalize() ;

		return $privilege ;
	}

	private function matchSelectors( $resType, $path ) {

		$inclSels         = $this->selectors[ Selector::OP_INCLUDE ] ;
		$inclOverrideSels = $this->selectors[ Selector::OP_INCLUDE_OVERRIDE ] ;
		$exclSels         = $this->selectors[ Selector::OP_EXCLUDE ] ;
		$exclOverrideSels = $this->selectors[ Selector::OP_EXCLUDE_OVERRIDE ] ;

		$match = false ;
		if( $this->match( $resType, $path, $inclSels ) ) {
			$this->logger->debug( "Matched include selectors" ) ;

			$match = true ;
			if( $this->match( $resType, $path, $inclOverrideSels ) ) {
				$this->logger->debug( "Matched include override selectors" ) ;
				$match = false ;
			}

			if( $this->match( $resType, $path, $exclSels ) ) {
				$this->logger->debug( "Matched exclude selectors" ) ;
				$match = false ;
				if( $this->match( $resType, $path, $exclOverrideSels ) ) {
					$this->logger->debug( "Matched exclude override selectors" ) ;
					$match = true ;
				}
			}
		}
		$this->logger->debug( "We have a selector match" ) ;
		return $match ;
	}

	private function match( $resType, $path, $selectorList ) {
		foreach( $selectorList as $selector ) {
			if( $selector->matches( $resType, $path ) ) {
				return true ;
			}
		}
		return false ;
	}

	function toString( $indent="    " ) {

		$str = "" ;
		$str .= $indent . "-Entitlement [$this->alias]\n" ;

		if( $this->numSelectors > 0 ) {
			$str .= $indent . "  -Selectors\n" ;
			foreach( array( Selector::OP_INCLUDE, Selector::OP_INCLUDE_OVERRIDE,
				            Selector::OP_EXCLUDE, Selector::OP_EXCLUDE_OVERRIDE ) 
				     as $opType ) {

				$selectorsForOpType = $this->selectors[ $opType ] ;
				foreach( $selectorsForOpType as $selector ) {
					$str .= $indent . "    " . $selector . "\n" ;
				}
			}
		}

		if( sizeof( $this->privileges ) > 0 ) {
			$str .= $indent . "  -Permitted operations\n" ;
			foreach( $this->privileges as $op ) {
				$str .= $indent . "    $op\n" ;
			}
		}

		if( sizeof( $this->childEntitlements ) > 0 ) {
			$str .= $indent . "  -Child entitlements\n" ;
			foreach( $this->childEntitlements as $childEntitlement ) {
				$str .= $childEntitlement->toString( $indent . "    " ) ;
			}
		}

		return $str ;
	}

	function __toString() {
		return "Printing entitlement\n" . $this->toString( "" ) ;
	}
}

?> 