<?php

require_once( DOCUMENT_ROOT . "/lib-app/php/api/api.php" ) ;

class TestAPI extends API {

	function __construct() {}

	public function getResponse() {
		return array(
			"message" => "Hello World!"
		) ;
	}
}

?>