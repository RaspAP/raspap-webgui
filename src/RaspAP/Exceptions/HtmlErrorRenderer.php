<?php

/**
 * HTML error renderer class
 *
 * @description An HTML error renderer for RaspAP
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 * @see
 */

declare(strict_types=1);

namespace RaspAP\Exceptions;

class HtmlErrorRenderer
{

    public function __construct()
    {
        $this->charset = 'UTF-8';
        $this->projectDir = dirname(__DIR__, 3);
        $this->template = '/templates/exception.php';
        $this->debug = true;
    }

    public function render($exception)
    {
        $message = $exception->getMessage();
        $code = $exception->getCode();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = $this->getExceptionTraceAsString($exception); 

        header('Content-Type: text/html; charset='.$this->charset);
        if ($this->debug) {
            header('X-Debug-Exception: '. rawurlencode($message));
            header('X-Debug-Exception-File: '. rawurlencode($file).':'.$line);
        }
        $__template_data = compact(
            "message",
            "code",
            "file",
            "line",
            "trace"
        );
        if (is_array($__template_data)) {
            extract($__template_data);
        } 
        $file = $this->projectDir . $this->template;
        ob_start();
        include $file;
        echo ob_get_clean();
    }

    /**
     * Improved exception trace
     * @param object $e     : exception
     * @param array $seen   : passed to recursive calls to accumulate trace lines already seen
     * @return array of strings, one entry per trace line
     * @see https://github.com/php/php-src/blob/master/Zend/zend_exceptions.c
     */
    public function getExceptionTraceAsString($e, $seen = null) {

        $starter = $seen ? 'Thrown by: ' : '';
        $result = array();
        if (!$seen) $seen = array();
        $trace  = $e->getTrace();
        $prev   = $e->getPrevious();
        $result[] = sprintf('%s%s: %s', $starter, get_class($e), $e->getMessage());
        $file = $e->getFile();
        $line = $e->getLine();

        while (true) {
            $current = "$file:$line";
            if (is_array($seen) && in_array($current, $seen)) {
                $result[] = sprintf(' ... %d more', count($trace)+1);
                break;
            }
            $result[] = sprintf(' at %s%s%s(%s%s%s)',
                count($trace) && array_key_exists('class', $trace[0]) ? str_replace('\\', '.', $trace[0]['class']) : '',
                count($trace) && array_key_exists('class', $trace[0]) && array_key_exists('function', $trace[0]) ? '.' : '',
                count($trace) && array_key_exists('function', $trace[0]) ? str_replace('\\', '.', $trace[0]['function']) : '(main)',
                $line === null ? $file : basename($file),
                $line === null ? '' : ':',
                $line === null ? '' : $line);

            if (is_array($seen)) {
                $seen[] = "$file:$line";
            }
            if (!count($trace)) {
                break;
            }
            $file = array_key_exists('file', $trace[0]) ? $trace[0]['file'] : 'Unknown Source';
            $line = array_key_exists('file', $trace[0]) && array_key_exists('line', $trace[0]) && $trace[0]['line'] ? $trace[0]['line'] : null;
            array_shift($trace);
        }
        $result = join(PHP_EOL, $result);
        if ($prev) {
            $result  .= PHP_EOL . getExceptionTraceAsString($prev, $seen);
        }
        return $result;
    }

}

