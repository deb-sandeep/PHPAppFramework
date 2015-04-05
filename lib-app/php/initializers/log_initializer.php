<?php
require_once( 'log4php/Logger.php' ) ;

require_once( DOCUMENT_ROOT . "/lib-app/php/initializers/" . "initializer.php" ) ;

class LogInitializer extends Initializer {

    function __construct() {
        array_push( $GLOBALS[ 'initializer_chain' ], $this ) ;
    }

    public function initialize() {

        global $logger ;

        Logger::configure( array(
                'rootLogger' => array(
                    'level' => LOG_LEVEL,
                    'appenders' => array('default'),
                ),

                'appenders' => array(
                    'default' => array(
                        'class' => 'LoggerAppenderFile',
                        'layout' => array(
                            'class' => 'LoggerLayoutPattern',
                            'params' => array(
                                'conversionPattern' => LOG_PATTERN
                            )
                        ),
                        'params' => array(
                            'file' => LOG_FILE_PATH,
                            'append' => true
                        )
                    )
                )
            )
        ) ;

        $logger = Logger::getLogger( PHP_SELF ) ;
        $logger->debug( "---------------------------------------------------------------------------\n" ) ;
        $logger->debug( "\tLogging initialized." ) ;
    }
}

new LogInitializer() ;

?>