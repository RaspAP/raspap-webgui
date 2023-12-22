<?php
require_once 'src/RaspAP/Exceptions/ExceptionHandler.php';

// Import the ExceptionHandler class
use RaspAP\Exceptions\ExceptionHandler;

set_exception_handler(['RaspAP\Exceptions\ExceptionHandler', 'handleException']);
register_shutdown_function(['RaspAP\Exceptions\ExceptionHandler', 'handleFatalError']);
?>

