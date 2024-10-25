<?php

/**
 * Status message class
 *
 * @description Status message class for RaspAP
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

namespace RaspAP\Messages;

class StatusMessage
{
    public $messages = array();

    public function addMessage($message, $level = 'success', $dismissable = true)
    {
        $status = '<div class="alert alert-'.$level;
        if ($dismissable) {
            $status .= ' alert-dismissible';
        }
        $status .= ' fade show" role="alert">'. _($message);
        if ($dismissable) {
            $status .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="close"></button>';
        }
        $status .= '</div>';

        array_push($this->messages, $status);
    }

    public function showMessages($clear = true)
    {
        foreach ($this->messages as $message) {
            echo $message;
        }
        if ($clear) {
            $this->messages = array();
        }
    }
}
