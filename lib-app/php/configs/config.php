<?php
// Error reporting management
ini_set( 'display_errors', 'On' ) ;
error_reporting( E_ALL | E_STRICT ) ;

// Constants derived from $_GLOBALS values
define( "PHP_SELF",             $_SERVER['PHP_SELF'] ) ;
define( "DOCUMENT_ROOT",        $_SERVER['DOCUMENT_ROOT'] ) ;

// Logging subsystem parameters
define( "LOG_LEVEL",            "debug" ) ;
//define( "LOG_PATTERN",          "%message [%date{H:i:s} %file(%L)] %newline" ) ;
define( "LOG_PATTERN",          "%message%newline" ) ;
define( "LOG_FILE_PATH",        DOCUMENT_ROOT . "/var/log/php.log" ) ;

define( "DB_HOST",              "localhost" ) ;
define( "DB_USER",              "root" ) ;
define( "DB_PASSWORD",          getenv( "DB_PASSWORD") ) ;
define( "DB_SCHEMA",            "study" ) ;

define( "LOGIN_PAGE_PATH",      "/lib-app/php/web/login.php" ) ;
define( "POST_LOGOUT_PAGE_PATH","/test.php" ) ;
define( "ERROR_PAGE_PATH",      DOCUMENT_ROOT . "/lib-app/php/web/error.php" ) ;

define( "NUM_DAYS_TO_REMEMBER_USER", 30 ) ;
define( "NUM_DAYS_TO_REMEMBER_INACTIVE_USER", 7 ) ;
define( "NUM_HOURS_FOR_SESSION_AUTH_TOKEN_TO_BECOME_OBSOLETE", 1 ) ;

$API_INCLUDE_FOLDER_LIST = array(
	DOCUMENT_ROOT . "/lib-app/php/api/"
) ;

?>