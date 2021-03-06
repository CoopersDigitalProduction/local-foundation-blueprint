<?php

namespace DeliciousBrains\WPMDB\Container\Dotenv\Repository\Adapter;

interface ReaderInterface extends \DeliciousBrains\WPMDB\Container\Dotenv\Repository\Adapter\AvailabilityInterface
{
    /**
     * Get an environment variable, if it exists.
     *
     * @param string $name
     *
     * @return \PhpOption\Option<string|null>
     */
    public function get($name);
}
