<?php
require_once( $_SERVER['DOCUMENT_ROOT']."/lib-app/php/page_preprocessor.php" ) ;

$requestedPage = HTTPUtils::getValueFromSession( 
	                   WebAuthenticationInterceptor::SESSION_PARAM_REQ_PAGE, "" ) ;
?>
<html>
	<head>
		<title>Login</title>
	</head>
	<body>
		<form action="<?php echo $requestedPage ?>" method="POST">
		<table align="center">
			<?php
				$errMessages = HTTPUtils::getValueFromSession( 
					           WebAuthenticationInterceptor::SESSION_PARAM_ERR_MSGS ) ;
				if( $errMessages != NULL ) {
					echo "<tr><td colspan='3'><font color='red'><ul>" ;
					foreach ( $errMessages as $message ) {
						echo "<li>$message</li>" ;
					}
					echo "</font></ul></td></tr>" ;
				}
				HTTPUtils::eraseKeyFromSession( 
					           WebAuthenticationInterceptor::SESSION_PARAM_ERR_MSGS ) ;
			?>
			<tr>
				<td>Login</td>
				<td> : </td>
				<td><input type="text" name="login"/></td>
			</tr>
			<tr>
				<td>Password</td>
				<td> : </td>
				<td><input type="password" name="password"/></td>
			</tr>
			<tr>
				<td>Remember me</td>
				<td> : </td>
				<td><input type="checkbox" name="remember_me" checked/></td>
			</tr>
			<tr>
				<td colspan="3" align="center"><input type="submit"/></td>
			</tr>
		</table>
		</form>
	</body>
</html>	
