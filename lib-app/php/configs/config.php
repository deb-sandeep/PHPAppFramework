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

define( "ERROR_PAGE_INCLUDE_PATH", DOCUMENT_ROOT . "/lib-app/php/web/default_error_page.php" ) ;

define( "NUM_DAYS_TO_REMEMBER_USER", 30 ) ;
define( "NUM_DAYS_TO_REMEMBER_INACTIVE_USER", 7 ) ;
define( "NUM_HOURS_FOR_SESSION_AUTH_TOKEN_TO_BECOME_OBSOLETE", 1 ) ;

$APP_CONFIG_DATA = <<< EOT
{
	"landing_page" : "/apps/_common/php/default_landing_page.php",
	"login_page"   : "/apps/_common/php/login.php",
	"logout_page"  : "/apps/_common/php/logout.php",

	"test_app" : {
		"landing_page" : "/apps/test_app/php/index.php"
	},

	"jove_notes" : {
		"landing_page" : "/apps/jove_notes/ng/dashboard/index.php"
	}
}
EOT;

require_once( DOCUMENT_ROOT . "/lib-app/php/configs/default_entitlement_rules.php" ) ;
?>