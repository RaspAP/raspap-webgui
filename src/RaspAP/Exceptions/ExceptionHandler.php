<?php

/**
 * Exception handler class
 *
 * @description A simple exception handler for RaspAP
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 * @see
 */

declare(strict_types=1);

namespace RaspAP\Exceptions;

use RaspAP\Exceptions\HtmlErrorRenderer;

class ExceptionHandler
{

    public function __construct()
    {
        $this->setExceptionHandler();
        $this->setShutdownHandler();
    }

    public static function handleException($exception)
    {
        $errorMessage = (
            '[' . date('Y-m-d H:i:s') . '] ' .
            $exception->getMessage() . ' in ' .
            $exception->getFile() . ' on line ' .
            $exception->getLine() . PHP_EOL
        );
        // Log the exception
        error_log($errorMessage, 3, RASPI_ERROR_LOG);

        $renderer = new HtmlErrorRenderer();
        $renderer->render($exception);
    }

    public static function handleShutdown()
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $errorMessage = (
                '[' . date('Y-m-d H:i:s') . '] ' .
                $error['message'] . ' in ' .
                $error['file'] . ' on line ' .
                $error['line'] . PHP_EOL
            );
            error_log($errorMessage, 3, RASPI_ERROR_LOG);

            $renderer = new HtmlErrorRenderer();
            $renderer->render($exception);
        }
    }

    protected function setExceptionHandler()
    {
        set_exception_handler(array($this, 'handleException'));
    }

    protected function setShutdownHandler()
    {
        register_shutdown_function(array($this, 'handleShutdown'));
    }
}

