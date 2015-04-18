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

		$this->parseOpType( trim( $selComponents[0] ) ) ;
		$this->resourceType = trim( $selComponents[1] ) ;
		$this->pattern = trim( $selComponents[2] ) ;
	}

	private function parseOpType( $type ) {

		if( !( ( $type == self::OP_INCLUDE ) ||
			   ( $type == self::OP_EXCLUDE ) ||
			   ( $type == self::OP_INCLUDE_OVERRIDE ) ||
			   ( $type == self::OP_EXCLUDE_OVERRIDE ) ) ) {

			throw new EntitlementException( EntitlementException::INVALID_ENTITLEMENT_PATTERN,
				            $type . " is not a valid selector operation." ) ;
		}
		$this->opType = $type ;
	}

	static function compareTo( $aSelector, $anotherSelector ) {

		if( $aSelector->getResourceType() != $anotherSelector->getResourceType() ) {
			return strcmp( $aSelector->getResourceType(), 
				           $anotherSelector->getResourceType() ) ;
		}
		else {
			if( $aSelector->getOpType() == $anotherSelector->getOpType() ) {
				return strcmp( $aSelector->getPattern(), 
					           $anotherSelector->getPattern() ) ;
			}
			else {
				return self::getOpPriorityForDisplay( $aSelector->getOpType() ) -
					   self::getOpPriorityForDisplay( $anotherSelector->getOpType() ) ;
			}
		}
		return 0 ;
	}

	static private function getOpPriorityForDisplay( $type ) {
		switch( $type ) {
			case Selector::OP_INCLUDE: return 1 ;
			case Selector::OP_INCLUDE_OVERRIDE: return 2 ;
			case Selector::OP_EXCLUDE: return 3 ;
			case Selector::OP_EXCLUDE_OVERRIDE: return 4 ;
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

	private $access ;
	private $opName ;
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

	static function fromAccessFlagAndOpName( $access, $opName ) {
		$op = new Operation( self::MAGIC_NO ) ;
		$op->access = ( $access ) ? Operation::OP_ACCESS : Operation::OP_FORBID ;
		$op->opName = $opName ;
		return $op ;		
	}

	private function parseRawOpString( $rawOp ) {

		$components = explode( self::PATTERN_SEPARATOR, $rawOp ) ;

		if( sizeof( $components ) == 1 ) {
			$this->access = self::OP_ACCESS ;
			$this->opName = $components[0] ;
		}
		else if( sizeof( $components ) == 2 ) {
			$this->access = $components[0] ;
			$this->opName = $components[1] ;
			if( !( $this->access == self::OP_ACCESS || 
				   $this->access == self::OP_FORBID ) ) {

				throw new EntitlementException( EntitlementException::INVALID_ENTITLEMENT_PATTERN,
					   "Access format in '$this->access' is invalid.") ;
			}
		}
		else {
			throw new EntitlementException( EntitlementException::INVALID_ENTITLEMENT_PATTERN,
				                  "Access format '$this->access' is invalid.") ;
		}
	}

	function isForbiddenOp() {
		return $this->access == self::OP_FORBID ;
	}

	function getOpName() {
		return $this->opName ;
	}

	function __toString() {
		return str_pad( $this->access, 3, " ", STR_PAD_LEFT ) . ":" . $this->opName ;
	}
}

class AccessPrivilege {

	private $opsMap ;
	private $logger ;

	function __construct() {
		$this->logger = \Logger::getLogger( __CLASS__ ) ;
		$this->opsMap = array() ;
	}

	function addPriviledge( $op ) {

		$countContainer = &$this->getCountContainer( $op->getOpName() ) ;
		if( $op->isForbiddenOp() ) {
			$countContainer[0]-- ;
		}
		else {
			$countContainer[1]++ ;
		}
	}

	function &getCountContainer( $opName ) {

		$countContainer ;
		if( !array_key_exists( $opName, $this->opsMap ) ) {
			$this->opsMap[ $opName ] = array( 0, 0 ) ;
		}
		return $this->opsMap[ $opName ] ;
	}

	function hasAccess( $opName ) {
		$countContainer = &$this->getCountContainer( $opName ) ;
		$count = $countContainer[0] + $countContainer[1] ;
		return ( $count > 0 ) ? true : false ;
	}

	function getPrivileges() {

		$ops = array() ;
		foreach( $this->opsMap as $key => $countContainer ) {

			$hasAccess = ( $countContainer[0] + $countContainer[1] ) > 0 ;
			$op = Operation::fromAccessFlagAndOpName( $hasAccess, $key ) ;
			array_push( $ops, $op ) ;
		}
		return $ops ;
	}

	function merge( $anotherAccessPrivilege ) {

		if( $anotherAccessPrivilege != null ) {
			foreach( $anotherAccessPrivilege->getPrivileges() as $op ) {
				$this->addPriviledge( $op ) ;
			}
		}
	}
}

class Entitlement {

	private $alias ;
	private $selectors ;
	private $accessPrivileges ;
	private $childEntitlements ;
	private $parent ;
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
		$this->accessPrivileges = new AccessPrivilege() ;
		$this->childEntitlements = array() ;
		$this->parent = NULL ;
	}

	function setParent( &$parent ) { $this->parent = $parent ; }

	function &getParent() { return $this->parent ; }

	function addRawSelector( $selectorString ) {
		$this->addSelector( new Selector( $selectorString ) ) ;
	}

	function addSelector( $selector ) {

		$container = &$this->selectors[ $selector->getOpType() ] ;
		if( !in_array( $selector, $container ) ) {
			array_push( $container, $selector ) ;
			usort( $container, array( 'sandy\phpfw\entitlement\Selector', 
				                      'compareTo' ) ) ;
		}
	}

	function addPermittedOp( $op ) {
		if( is_array( $op ) ) {
			foreach( $op as $o ) {
				if( $o instanceof Operation ) {
					$this->accessPrivileges->addPriviledge( $o ) ;
				}
				else {
					$this->accessPrivileges->addPriviledge( Operation::fromRawOp( $o ) ) ;
				}
			}
		}
		else {
			if( $op instanceof Operation ) {
				$this->accessPrivileges->addPriviledge( $op ) ;
			}
			else {
				$this->accessPrivileges->addPriviledge( Operation::fromRawOp( $op ) ) ;
			}
		}
	}

	function addChildEntitlement( $entitlement ) {
		if( !$this->canChildCauseInfiniteRecursion( $entitlement ) ) {
			array_push( $this->childEntitlements, $entitlement ) ;
			$entitlement->setParent( $this ) ;
			return true ;
		}
		else {
			$this->logger->debug( "Not adding entitlement. Can cause recursion." ) ;
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
	function getAccessPrivileges() { return $this->accessPrivileges ; }

	function computeAccessPrivilege( $resType, $path ) {

		// $guardComponents = explode( Selector::PATTERN_SEPARATOR, $guard ) ;
		// if( count( $guardComponents ) != 2 ) {
		// 	throw new EntitlementException( EntitlementException::INVALID_ENTITLEMENT_GUARD,
		// 		     "$guard is invalid. Either resource type or path is missing." ) ;
		// }
		// $resType = $guardComponents[0] ;
		// $path    = $guardComponents[1] ;

		$privilege = new AccessPrivilege() ;
		foreach( $this->childEntitlements as $childEntitlement ) {
			$privilege->merge( $childEntitlement->computeAccessPrivilege( $resType, $path ) ) ;
		}
		$privilege->merge( $this->computeSelfPrivileges( $resType, $path ) ) ;
		return $privilege ;
	}

	private function computeSelfPrivileges( $resType, $path ) {

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
		
		if( $match ) {
			$this->logger->debug( "Matched. Returning this.accessPrivileges" ) ;
			return $this->accessPrivileges ;
		}
		$this->logger->debug( "Did not match selectors." ) ;
		return null ;
	}

	private function match( $resType, $path, $selectorList ) {
		foreach( $selectorList as $selector ) {
			if( $selector->matches( $resType, $path ) ) {
				return true ;
			}
		}
		return false ;
	}

	function toString( $indent="\t" ) {

		$str = "" ;
		$str .= $indent . "Entitlement [$this->alias]\n" ;

		if( sizeof( $this->selectors ) > 0 ) {
			$str .= $indent . "  Selectors\n" ;
			foreach( array( Selector::OP_INCLUDE, Selector::OP_INCLUDE_OVERRIDE,
				            Selector::OP_EXCLUDE, Selector::OP_EXCLUDE_OVERRIDE ) 
				     as $opType ) {

				$selectorsForOpType = $this->selectors[ $opType ] ;
				foreach( $selectorsForOpType as $selector ) {
					$str .= $indent . "    " . $selector . "\n" ;
				}
			}
		}

		if( sizeof( $this->accessPrivileges->getPrivileges() ) > 0 ) {
			$str .= $indent . "  Permitted operations\n" ;
			foreach( $this->accessPrivileges->getPrivileges() as $op ) {
				$str .= $indent . "    $op\n" ;
			}
		}

		if( sizeof( $this->childEntitlements ) > 0 ) {
			$str .= $indent . "  Child entitlements\n" ;
			foreach( $this->childEntitlements as $childEntitlement ) {
				$str .= $indent . "  " . $childEntitlement->toString( $indent . "\t" ) ;
			}
		}

		return $str ;
	}

	function __toString() {
		return "Printing entitlement\n" . $this->toString( "" ) ;
	}
}

?> 