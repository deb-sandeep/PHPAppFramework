<?php

abstract class Interceptor {

	abstract protected function intercept() ;

	public function canInterceptRequest() {
		
		if( ExecutionContext::getCurrentUserName() != NULL ) {
			return true ;
		} 
		return false ;
	}
}

?>