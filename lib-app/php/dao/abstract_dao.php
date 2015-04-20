<?php

abstract class AbstractDAO {

	private $logger ;

	function __construct() {
		$this->logger = Logger::getLogger( __CLASS__ ) ;
	}

	function processDatabaseError( $query ) {
	    global $dbConn ;
	    $this->logger->error( "DB Error - Query = " . $query ) ;
	    throw new Exception( "Database Error - " . $dbConn->error ) ;
	}

	private function executeIUDStatement( $sql, 
										  $sqlType,
		                                  $minAffectedRowsToCheck=1,
		                                  $successMessage="Execution successful." ) {
	    global $dbConn ;

	    $this->logger->debug( "Firing $sqlType query = $sql" ) ;
	    if( $result = $dbConn->query( $sql ) ) {
	        if( $dbConn->affected_rows >= $minAffectedRowsToCheck ) {
	            $this->logger->debug( "\t" . $successMessage . " Num affected rows = " . $dbConn->affected_rows ) ;
	        }
	        else {
	        	$this->logger->debug( "WARNING:: " . $dbConn->affected_rows . 
	        		            " rows affected. Less than expected." ) ;
	        }
        }
	    else {
			$this->processDatabaseError( $sql ) ;
	    }
	}

	protected function executeInsert( $sql,
		                              $minInsertedRecordsToCheck=1,
		                              $successMessage="Insert successful" ) {
		$this->executeIUDStatement( $sql, "insert" ) ;
	}

	protected function executeUpdate( $sql, 
		                              $minUpdatedRecordsToCheck=1, 
		                              $successMessage="Update successful." ) {
		$this->executeIUDStatement( $sql, "update" ) ;
	}

	protected function executeDelete( $sql, 
		                              $minDeletedRecordsToCheck=1, 
		                              $successMessage="Delete successful." ) {
		$this->executeIUDStatement( $sql, "delete" ) ;
	}

	public function executeSelect( $sql, 
		                           $minSelectedRecordsToCheck=1,
		                           $successMessage="Select successful." ) {

	    global $dbConn ;
	    $result ;

	    $this->logger->debug( "Firing select query = $sql" ) ;
	    if( $result = $dbConn->query( $sql ) ) {
	    	if( $result->num_rows < $minSelectedRecordsToCheck ) {
	        	$this->logger->debug( "WARNING:: " . $result->num_rows . 
	        		            " rows selected. Less than expected." ) ;
	    	}
        }
	    else {
	        $this->processDatabaseError( $sql ) ;
	    }

	    return $result ;
	}

	protected function selectSingleValue( $query, $defaultValue=NULL ) {

	    $singleValue = $defaultValue ;
	    $result = $this->executeSelect( $query ) ;

    	if( $result->num_rows != 0 ) {
    		$singleValue = $result->fetch_array()[0] ;
    		$this->logger->debug( "\tValue from database is " . $singleValue ) ;
    	}
	    return $singleValue ;
	}

	protected function getResultAsArray( $query, $minSelectedRecordsToCheck=1 ) {

	    $retVal = array() ;
	    $result = $this->executeSelect( $query, $minSelectedRecordsToCheck ) ;
	    while( $row = $result->fetch_array() ) {
	    	array_push( $retVal, $row[0] ) ;
	    }
	    return $retVal ;
	}

	protected function getResultAsMap( $query ) {

	    $retVal = array() ;
	    $result = $this->executeSelect( $query ) ;
	    while( $row = $result->fetch_array() ) {
	    	$retVal[ $row[0] ] = $row[1] ;
	    }
	    return $retVal ;
	}
}

?>