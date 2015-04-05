<?php

abstract class Interceptor {

	abstract protected function intercept() ;

	public function canInterceptRequest() {
		
		if( ExecutionContext::getCurrentUser() != NULL ) {
			return true ;
		} 
		return false ;
	}
}

?>