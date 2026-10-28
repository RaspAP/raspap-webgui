<?php

/**
 * Login attempt limit
 *
 * @description Counts the failed logins of each client address. After
 *              $maxFailures failed logins, the client must wait $lockTime
 *              seconds after its last failed login. The counts are kept in
 *              the private cache directory (get_private_cache_dir()). Without
 *              that directory, there is no limit.
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

declare(strict_types=1);

namespace RaspAP\Auth;

class LoginLimiter
{
    private ?string $file;
    private int $maxFailures;
    private int $lockTime;

    public function __construct(?string $dir, int $maxFailures, int $lockTime)
    {
        $this->file = $dir === null ? null : $dir . '/login-failures.json';
        $this->maxFailures = max(1, $maxFailures);
        $this->lockTime = max(1, $lockTime);
    }

    /*
     * Returns the number of seconds that the client must wait, 0 if none
     * @var string $client
     * return int
     */
    public function waitTime(string $client): int
    {
        $entry = $this->read()[$client] ?? null;
        if ($entry === null || $entry['count'] < $this->maxFailures) {
            return 0;
        }
        return max(0, $entry['last'] + $this->lockTime - time());
    }

    /*
     * Counts a failed login of the client
     * @var string $client
     */
    public function failure(string $client): void
    {
        $this->update(function (array $data) use ($client): array {
            $count = ($data[$client]['count'] ?? 0) + 1;
            $data[$client] = ['count' => $count, 'last' => time()];
            return $data;
        });
    }

    /*
     * Removes the failed logins of the client, after a good login
     * @var string $client
     */
    public function success(string $client): void
    {
        $this->update(function (array $data) use ($client): array {
            unset($data[$client]);
            return $data;
        });
    }

    /*
     * Reads the counts, without the entries that are older than $lockTime
     * return array
     */
    private function read(): array
    {
        if ($this->file === null || !is_file($this->file) || is_link($this->file)) {
            return [];
        }
        $data = json_decode((string)@file_get_contents($this->file), true);
        return $this->prune(is_array($data) ? $data : []);
    }

    private function prune(array $data): array
    {
        $now = time();
        foreach ($data as $client => $entry) {
            if (!is_array($entry) || !isset($entry['count'], $entry['last']) ||
                $entry['last'] + $this->lockTime < $now) {
                unset($data[$client]);
            }
        }
        return $data;
    }

    /*
     * Changes the counts under an exclusive lock
     * @var callable $change gets the counts and returns the new counts
     */
    private function update(callable $change): void
    {
        if ($this->file === null || is_link($this->file)) {
            return;
        }
        $fp = @fopen($this->file, 'c+');
        if ($fp === false) {
            return;
        }
        if (flock($fp, LOCK_EX)) {
            $data = json_decode((string)stream_get_contents($fp), true);
            $data = $change($this->prune(is_array($data) ? $data : []));
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, (string)json_encode($data));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }
}
