<?php
// Error reporting management
ini_set( 'display_errors', 'On' ) ;
error_reporting( E_ALL | E_STRICT ) ;

define( "PHP_SELF",             $_SERVER['PHP_SELF'] ) ;
define( "DOCUMENT_ROOT",        $_SERVER['DOCUMENT_ROOT'] ) ;

define( "LOG_FILE_PATH",        DOCUMENT_ROOT . "/var/log/php.log" ) ;

define( "DB_HOST",              "localhost" ) ;
define( "DB_USER",              "root" ) ;
define( "DB_PASSWORD",          getenv( "DB_PASSWORD") ) ;
define( "DB_SCHEMA",            "study" ) ;

#define( "LOGIN_PAGE_PATH",              "/lib-app/php/web/default_login_page.php" ) ;
define( "LOGIN_PAGE_PATH",              "/apps/_common/php/login.php" ) ;
define( "POST_LOGOUT_PAGE_PATH",        "/lib-app/php/web/test_post_logout_page.php" ) ;
define( "ERROR_PAGE_INCLUDE_PATH",      DOCUMENT_ROOT . "/lib-app/php/web/error.php" ) ;

define( "NUM_DAYS_TO_REMEMBER_USER", 30 ) ;
define( "NUM_DAYS_TO_REMEMBER_INACTIVE_USER", 7 ) ;
define( "NUM_HOURS_FOR_SESSION_AUTH_TOKEN_TO_BECOME_OBSOLETE", 1 ) ;

$APP_CONFIG_DATA = <<< EOT
{
	"default_landing_page" : "/lib-app/php/web/default_landing_page.php",

	"test_app" : {
		"default_landing_page" : "/apps/test_app/php/index.php"
	}
}
EOT;

?>