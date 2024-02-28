<?php

/**
 * DotEnv parser/writer class
 *
 * @description Reads and sets key/value pairs to .env
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

declare(strict_types=1);

namespace RaspAP\DotEnv;

class DotEnv
{
    protected $envFile;
    protected $data = [];

    public function __construct($envFile = '.env')
    {
        $this->envFile = $envFile;
    }

    public function load()
    {
        if (file_exists($this->envFile)) {
            $this->data = parse_ini_file($this->envFile);
            foreach ($this->data as $key => $value) {
                if (!getenv($key)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                }
            }
        } else {
            throw new Exception(".env file '{$this->envFile}' not found.");
        }
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
        putenv("$key=$value");
        $this->store($key, $value);
    }

    public function get($key)
    {
        return getenv($key);
    }

    public function getAll()
    {
        return $this->data;
    }

    public function unset($key)
    {
        unset($_ENV[$key]);
        return $this;
    }

    private function store($key, $value)
    {
        $content = file_get_contents($this->envFile);
        $content = preg_replace("/^$key=.*/m", "$key=$value", $content, 1, $count);
        if ($count === 0) {
            // if key doesn't exist, append it
            $content .= "$key=$value\n";
        }
        file_put_contents($this->envFile, $content);
    }
}

