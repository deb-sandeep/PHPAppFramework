<?php
require_once( $_SERVER['DOCUMENT_ROOT']."/lib-app/php/page_preprocessor.php" ) ;
?>
<html>
<head>
	<script src="/lib-ext/jquery/jquery-2.1.1.min.js"></script>
	<script>
	function sendAsyncRequest( apiName, payload ) {

        var reqAttributes = {} ;
        reqAttributes[ "apiName" ] = apiName ;
        reqAttributes[ "payload" ] = payload ;

        var serializedRequest = JSON.stringify( reqAttributes ) ;

        $.ajax({
            type: 'POST',
            url: '<?php echo API_GATEWAY_SERVICE_PATH ?>',
            data: {
                apiRequest : serializedRequest
            },
            headers: {
            	Accept: "application/json"
            },
            async:true
        })
        .done( function( responseStr ) {
        	responseDiv = document.getElementById( "response" ) ;
        	responseDiv.innerHTML = responseStr ;
        }) ;         
	}

	function sendRequest() {

		var payload = {} ;
		payload[ "name" ] = "Sandeep" ;

		sendAsyncRequest( "TestAPI", payload ) ;
	}
	</script>
</head>
<body>
	Hello there! <?php echo ExecutionContext::getCurrentUser()->userName ?>
	<p>
	<a href="<?php echo LOGOUT_SERVICE ?>">Logout</a><p>
	<ul>
	<?php
	echo ExecutionContext::getUserPreference( "default.font.size" ) ;
	echo "<p>" ;
	foreach ( ExecutionContext::getCurrentUser()->preferences->getPreferences() as $key => $value) {
		echo "<li>$key = $value</li>" ;
	}
	?>
	</ul>
	<p>
	<input type="button" onclick="sendRequest()" value="Ajax"/>
	<p>
	Server Response:<p>
	<div id="response"><?php echo ExecutionContext::getRequestType() ?></div>
</body>
</html>