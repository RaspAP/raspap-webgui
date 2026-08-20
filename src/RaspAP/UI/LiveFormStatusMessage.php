<?php
/**
 * Live Form Status Message Class
 *
 * @description A class to override the functions of the StatusMessage class to send messages to the frontend in real time as a form is being processed.
 * @author      Shawn Holmes <sherlock656@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */
namespace RaspAP\UI;

use RaspAP\Messages\StatusMessage;

class LiveFormStatusMessage extends StatusMessage
{
    private $liveForm;

    public function __construct($liveForm) {
        $this->liveForm = $liveForm;
    }

    public function addMessage($message, $level = 'success', $dismissable = true) {
        $this->liveForm->sendUpdateMessage($message);
    }
}
