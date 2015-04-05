<?php

require_once( $_SERVER['DOCUMENT_ROOT']."/lib-app/php/page_preprocessor.php" ) ;
require_once( $_SERVER['DOCUMENT_ROOT']."/lib-app/php/api/api_invoker.php" ) ;

$invoker = new APIInvoker() ;
try {
	$invoker->handleRequest() ;
}
catch( Exception $e ) {
	APIInvoker::writeErrorResponse( "Unanticipated API exception. $e" ) ;
}

?>