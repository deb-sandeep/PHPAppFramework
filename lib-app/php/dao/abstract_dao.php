<?php

abstract class AbstractDAO {

	function processDatabaseError( $query ) {
	    global $logger, $dbConn ;
	    $logger->error( "DB Error - Query = " . $query ) ;
	    throw new Exception( "Database Error - " . $dbConn->error ) ;
	}

	private function executeIUDStatement( $sql, 
										  $sqlType,
		                                  $minAffectedRowsToCheck=1,
		                                  $successMessage="Execution successful." ) {

	    global $logger, $dbConn ;

	    $logger->debug( "Firing $sqlType query = $sql" ) ;
	    if( $result = $dbConn->query( $sql ) ) {
	        if( $dbConn->affected_rows >= $minAffectedRowsToCheck ) {
	            $logger->debug( "\t" . $successMessage . " Num affected rows = " . $dbConn->affected_rows ) ;
	        }
	        else {
	        	$logger->debug( "WARNING:: " . $dbConn->affected_rows . 
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

	    global $logger, $dbConn ;
	    $result ;

	    $logger->debug( "Firing select query = $sql" ) ;
	    if( $result = $dbConn->query( $sql ) ) {
	    	if( $result->num_rows < $minSelectedRecordsToCheck ) {
	        	$logger->debug( "WARNING:: " . $dbConn->num_rows . 
	        		            " rows selected. Less than expected." ) ;
	    	}
        }
	    else {
	        $this->processDatabaseError( $sql ) ;
	    }

	    return $result ;
	}

	protected function selectSingleValue( $query, $defaultValue=NULL ) {

	    global $logger, $dbConn ;

	    $singleValue = $defaultValue ;
	    $result = $this->executeSelect( $query ) ;

    	if( $result->num_rows != 0 ) {
    		$singleValue = $result->fetch_array()[0] ;
    		$logger->debug( "\tValue from database is " . $singleValue ) ;
    	}
	    return $singleValue ;
	}
}

?>