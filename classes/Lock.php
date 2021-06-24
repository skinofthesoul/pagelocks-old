<?php

namespace Grav\Plugin\PageLocks;

class Lock {
    public string $email = '';
    public string $fullname = '';
    public int $timestamp = 0;

    public function __construct(array $args = [])
    {
        foreach ($args as $key => $value) {
            $this->{$key} = $value;
        }
    }
}
