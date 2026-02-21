<?php

namespace App\Core;

abstract class Middleware
{
    /**
     * O método principal do middleware.
     *
     * @return void
     */
    abstract public function handle(): void;
}
