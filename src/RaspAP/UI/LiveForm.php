<?php
/**
 * Live Form Submission
 *
 * @description A class providing the backend framework of the live form submission feature
 * @author      Shawn Holmes <sherlock656@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

namespace RaspAP\UI;

class LiveForm
{
    public const SESSION_STATUS_MESSAGES = 'liveFormStatusMessages';

    // statuses
    public const START = 'START';
    public const RUNNING = 'RUNNING';
    public const FAILED = 'FAILED';
    public const COMPLETE = 'COMPLETE';

    protected $status = 'INIT';
    protected $progress = 0;
    protected $messageHistory = [];

    // used to enforce a min processing time to make
    // sure the frontend doesn't show to quick
    private $start_time;
    private $end_time;
    protected const MIN_PROC_TIME = 3.0;

    /**
     * Use this function to initialize the ajax live form file
     */
    public function initAjax() {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');

        ini_set('zlib.output_compression', 'Off');
        ini_set('output_buffering', '0');
        ob_implicit_flush(true);
        while (ob_get_level() > 0) ob_end_flush();
    }

    /**
     * This function should be run early on to indicate
     * the start of the stream of information to the frontend
     */
    public function sendStartMessage() {
        $this->status = SELF::START;
        $this->progress = 0;
        $this->start_time = microtime(true);

        $this->sendMessage();
    }

    /**
     * This function should to indicate to the frontend that a
     * critical failure occurred. If trying to indicate that portion
     * of the processing was not successful, use `sendLiveFormMessage` indicating so.
     */
    public function sendFailedMessage() {
        $this->status = SELF::FAILED;
        $this->progress = 100;
        $this->checkRuntimeDelay();

        $this->sendMessage();
        exit;
    }

    /**
     * This function should the last to be run to indicate
     * to the frontend that processing is complete
     */
    public function sendCompleteMessage() {
        $this->status = SELF::COMPLETE;
        $this->progress = 100;
        $this->checkRuntimeDelay();

        $this->sendMessage();
        exit;
    }

    private function checkRuntimeDelay() {
        $this->end_time = microtime(true);

        $duration = $this->end_time - $this->start_time;
        if ($duration < SELF::MIN_PROC_TIME) {
            sleep(SELF::MIN_PROC_TIME - $duration);
        }
    }

    /**
     * Sends progress update message to the frontend
     */
    public function sendUpdateMessage($message, $progress = null) {
        if ($this->status != SELF::RUNNING) $this->status = SELF::RUNNING;
        if ($progress != null) $this->progress = $progress;
        array_push($this->messageHistory, $message);

        $this->sendMessage($message);
    }

    /**
     * Save a message to session that will be displayed
     * on the refresh of the responsible page
     */
    public function saveStatusMessage($message, $level, $withHistory = false) {
        if ($withHistory) {
            $historyMessage = "<div>$message<button type=\"button\" class=\"btn btn-sm btn-outline-secondary ms-2\" data-bs-toggle=\"collapse\" data-bs-target=\"#liveFromAlertHistory\">Show History</button></div>";
            $historyMessage .= "<div id=\"liveFromAlertHistory\" class=\"collapse mt-3\"><div class=\"card card-body\"><pre class=\"mb-0\">";
            foreach ($this->messageHistory as $history) {
                $historyMessage .= $history . "\n";
            }
            $historyMessage .= "</pre></div></div>";
        }

        $statusMessage = [
            'message' => $historyMessage ?? $message,
            'level' => $level
        ];

        if (!isset($_SESSION[SELF::SESSION_STATUS_MESSAGES])) {
            $_SESSION[SELF::SESSION_STATUS_MESSAGES] = [];
        }

        $_SESSION[SELF::SESSION_STATUS_MESSAGES][] = $statusMessage;
    }

    static function loadStatusMessages($status) {
        if (isset($_SESSION[SELF::SESSION_STATUS_MESSAGES])) {
            foreach ($_SESSION[SELF::SESSION_STATUS_MESSAGES] as $statusMessage) {
                $status->addMessage($statusMessage['message'], $statusMessage['level']);
            }

            unset($_SESSION['liveFormStatusMessages']);
        }
    }

    private function sendMessage($message = null) {
        $data = [
            "status" => $this->status,
            "progress" => $this->progress,
        ];

        if (isset($message)) {
            $data['message'] = $message;
        }

        echo "data: " . json_encode($data) . "\n\n";
        flush();
    }
}
