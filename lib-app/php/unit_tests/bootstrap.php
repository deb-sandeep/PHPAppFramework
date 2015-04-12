<?php
ini_set( 'display_errors', 'On' ) ;
error_reporting( E_ALL | E_STRICT ) ;

require_once( 'log4php/Logger.php' ) ;
Logger::configure( '../configs/log4php-config.xml' ) ;
$logger = Logger::getLogger( "GLOBAL_UT_LOGGER" ) ;

define( "DOCUMENT_ROOT", "/home/sandeep/projects/source/PHPAppFramework" ) ;

$dbConn = NULL ;


?>