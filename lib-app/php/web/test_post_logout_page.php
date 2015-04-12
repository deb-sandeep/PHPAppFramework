<?php
require_once( $_SERVER['DOCUMENT_ROOT']."/lib-app/php/page_preprocessor.php" ) ;
?>
<html>
<head>
	<script src="/lib-ext/jquery/jquery-2.1.1.min.js"></script>
	<script>
	function sendAsyncRequest( apiPath, payload ) {

        var serializedRequest = JSON.stringify( payload ) ;

        $.ajax({
            type: 'POST',
            url: apiPath,
            data: serializedRequest,
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

		sendAsyncRequest( "/api/Greetings", payload ) ;
	}
	</script>
</head>
<body>
	Hello there! <?php echo ExecutionContext::getCurrentUser()->getUserName() ?>
	<p>
	<a href="<?php echo LOGOUT_SERVICE ?>">Logout</a><p>
	<ul>
	<?php
	echo "<h3>User preferences</h3>" ;
	echo "<p>" ;
	foreach ( ExecutionContext::getCurrentUser()->getPreferences() as $key => $value) {
		echo "<li>$key = $value</li>" ;
	}
	echo "<p>" ;
	echo "<h3>User entitlements</h3>" ;
	foreach ( ExecutionContext::getCurrentUser()->getAllEntitlementsAsStringArray() as $entitlement) {
		echo "<li>$entitlement</li>" ;
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