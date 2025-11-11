<?php

namespace Bausystem\Provisioner;

use Blocks\System\SimpleLogger;
use Bausystem\Provisioner\ErrorHandler;

class Bausystem {

    public static function init( $config = null ) {
        define( 'START_TIME', microtime(true) );

        if ( version_compare( phpversion(), '8.2.0', '<' ) == true ) {
            trigger_error('PHP 8.2 or newer is required. Your PHP version is: ' . phpversion() . '. Exiting.', E_USER_ERROR);
            exit();
        }

        if ( strpos( php_sapi_name(), 'cli' ) === false ) {
            trigger_error('This script must be invoked from the command line and cannot be run by a web-server', E_USER_ERROR);
            exit();
        }

        $vendor_string_pos = strpos(__DIR__, '/vendor/');

        if ($vendor_string_pos === false) {
            trigger_error("Fatal error: your __DIR__ doesn't contain \"/vendor/\" string. You are either using a different path for your vendor folder which is not supported or rather your directory structure is unexpected", E_USER_ERROR);
            exit();
        }

        $script_root = substr(__DIR__, 0, $vendor_string_pos);
        $script_root = rtrim($script_root, '/');
        
        // Make server root available globally
        define('SCRIPT_ROOT', $script_root);

        // This is highly unlikely, but still
        if ( ini_get('register_globals') ) {
            trigger_error('Your PHP environment has register_globals enabled. Disable it on this server before running the web-site.', E_USER_ERROR);
            exit();
        }

        if ( ini_get('magic_quotes_gpc') ) {
            trigger_error('Your PHP environment has magic_quotes_gpc enabled. This is not allowed.', E_USER_ERROR);
            exit();
        }

        if ( !ini_get('date.timezone') ) {
            date_default_timezone_set('UTC');
        }

        ini_set('default_charset', 'UTF-8');
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');

        set_time_limit( 60 * 60 * 24 );

        if ( isset( $config, $config['log_path'] ) ) {
            $log_path = $config['log_path'];
        }
        else {
            $log_path = null;
        }

        $logger = new SimpleLogger( $log_path );

        ErrorHandler::init( $logger );

        set_exception_handler( 'Bausystem\Provisioner\ErrorHandler::exceptionHandler' );
    }

}
