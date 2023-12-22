<?php

/**
 * Exception handler class
 *
 * @description  
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 * @see
 */

declare(strict_types=1);

namespace RaspAP\Exceptions;

class ExceptionHandler {
    public static function handleException($exception) {
        // Log the exception to a file or a service
        $errorMessage = '[' . date('Y-m-d H:i:s') . '] ' . $exception->getMessage() . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine() . PHP_EOL;
        error_log($errorMessage, 3, 'error.log');

        echo '<h3>An error occured</h3>';
        echo '<p>'.$errorMessage.'</p>';
 
        //header('Location: error_page.php');
    }

    public static function handleFatalError() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            // Log the fatal error
            $errorMessage = '[' . date('Y-m-d H:i:s') . '] ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line'] . PHP_EOL;
            error_log($errorMessage, 3, 'error.log');

            // Return HTTP 500 status header
            //http_response_code(500);

            echo '<h3>A Fatal error occured</h3>';
            echo '<p>'.$errorMessage.'</p>';
            exit;
        }
    }

    public static function handleShutdown() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $errorMessage = '[' . date('Y-m-d H:i:s') . '] ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line'] . PHP_EOL;
            error_log($errorMessage, 3, 'error.log');

            echo '<h3>Executing shutdown</h3>';
            echo '<p>'.$errorMessage.'</p>';
 
            // header('Location: error_page.php');
        }
    }
}

