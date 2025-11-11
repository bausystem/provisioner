<?php

namespace Bausystem\Provisioner;

use Blocks\System\Helper\CommandLineOutput;
use Blocks\System\SimpleLogger;

/**
 * A class for catching all the regular exceptions and displaying them to the
 * user and/or writing to the logs.
 *
 * It's called ErrorHandler, because it's catching application's errors, not
 * PHP errors. PHP errors should not be caught here. Application's errors are
 * done through Exceptions.
 */
class ErrorHandler {
    private static SimpleLogger $logger;

    public static function init( $logger ) {
        self::$logger = $logger;
    }

    public static function exceptionHandler( \Throwable $e ) {
        $error_type = is_object( $e ) ? get_class( $e ) : '<Unknown error>';

        $error_message = !empty( $e->getMessage() ) ? $e->getMessage() : '—';

        $error_file = !empty( $e->getFile() ) ? $e->getFile() : '<unknown file>';

        $error_line = !empty( $e->getLine() ) ? $e->getLine() : '<unknown line>';

        $backtrace_items = $e->getTrace();

        self::$logger->add( self::getLogRecord( $error_type, $error_message, $error_file, $error_line, $backtrace_items ) );

        if ( 'RuntimeException' == $error_type ) {
            self::displayRuntimeException( $error_message );
        }
        else {
            self::displayException( $error_type, $error_message, $error_file, $error_line, $backtrace_items );
        }
    }

    private static function displayRuntimeException( string $error_message ) {
        CommandLineOutput::error( 'Error occured. '.$error_message );
    }

    private static function displayException( string $error_type, string $error_message, string $error_file, string $error_line, array $backtrace_items ) {
        $output = 'Error occured'.PHP_EOL;

        if ( !empty( $error_type ) ) {
            $output .= "Type: {$error_type}".PHP_EOL;
        }

        if ( !empty( $error_message ) ) {
            $output .= "Message: {$error_message}".PHP_EOL;
        }

        if ( !empty( $error_file ) ) {
            $output .= "File: {$error_file}".PHP_EOL;
        }

        if ( !empty( $error_line ) ) {
            $output .= "Line: {$error_line}".PHP_EOL;
        }

        $backtrace_lines = [];

        foreach ( $backtrace_items as $backtrace_item ) {
            $backtrace_lines[] = '    '.( isset( $backtrace_item['file'] ) ? $backtrace_item['file'] : '<unknown file>' ).':'.( isset( $backtrace_item['line'] ) ? $backtrace_item['line'] : '<unknown line>' ).' calling '.$backtrace_item['function'].'()';
        }

        $backtrace_text = join( PHP_EOL, $backtrace_lines );

        if ( !empty( $backtrace_lines ) ) {
            $output .= 'Backtrace: '.PHP_EOL.$backtrace_text;
        }

        CommandLineOutput::error( $output );
    }

    private static function getLogRecord( $error_type, $error_message, $error_file, $error_line, $backtrace_items ) {
        $log_record = 'Error occured';

        if ( !empty( $error_type ) ) {
            $log_record .= "; Type: {$error_type}";
        }

        if ( !empty( $error_message ) ) {
            $log_record .= "; Message: {$error_message}";
        }

        if ( !empty( $error_file ) ) {
            $log_record .= "; File: {$error_file}";
        }

        if ( !empty( $error_line ) ) {
            $log_record .= "; Line: {$error_line}";
        }

        $backtrace_lines = [];

        foreach ( $backtrace_items as $backtrace_item ) {
            $backtrace_lines[] = ''.( isset( $backtrace_item['file'] ) ? $backtrace_item['file'] : '<unknown file>' ).':'.( isset( $backtrace_item['line'] ) ? $backtrace_item['line'] : '<unknown line>' ).' calling '.$backtrace_item['function'].'()';
        }

        $backtrace_text = join( ' | ', $backtrace_lines );

        if ( !empty( $backtrace_lines ) ) {
            $log_record .= "; Backtrace: {$backtrace_text}";
        }

        return str_replace( PHP_EOL, ' | ', $log_record );
    }
}
