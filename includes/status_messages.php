<?php
class StatusMessages
{
    public $messages = array();

    public function addMessage($message, $level = 'success', $dismissable = true)
    {
        $status = '<div class="alert alert-'.$level;
        if ($dismissable) {
            $status .= ' alert-dismissable';
        }
        $status .= '">'. _($message);
        if ($dismissable) {
            $status .= '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>';
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
