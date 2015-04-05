<?php

abstract class API {

	protected $executionStatus    = "SUCCESS" ;
	protected $executionStatusMsg = NULL ;

	protected $requestPayload = NULL ;

	function __construct() {
	}

	public function setRequestPayload( $requestPayload ) {
		$this->requestPayload = $requestPayload ;
	}

	public function preExecute() {}

	public function execute() {} 

	public function postExecute() {}

	public function getExecutionStatus() {
		return $this->executionStatus ;
	}

	public function getExecutionStatusMessage() {
		return $this->executionStatusMsg ;
	}

	public abstract function getResponse() ;
}

?>