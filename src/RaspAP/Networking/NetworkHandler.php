<?php

/**
 * NetworkHandler class
 *
 * @description Class interface for network handlers (mobile devices, other network devices)
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

declare(strict_types=1);

namespace RaspAP\Networking;

use RaspAP\Utils\JsonResponse;

interface NetworkHandler
{
    public function handle(array $post): JsonResponse;
}

