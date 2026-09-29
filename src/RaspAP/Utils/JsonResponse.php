<?php

/**
 * Utility class
 *
 * @description An class providing basic utility functions 
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

declare(strict_types=1);

namespace RaspAP\Utils;

class JsonResponse
{
    public int $code;
    public array|string $message;

    public function __construct(int $code, array|string $message) {
        $this->code = $code;
        $this->message = $message;
    }

    public function toJson(): string {
        return json_encode(['return' => $this->code, 'output' => $this->message]);
    }

    public static function success(string $message = 'OK'): self
    {
        return new self(0, $message);
    }

    public static function error(string $message = 'Error', int $code = 1): self
    {
        return new self($code, $message);
    }
}

