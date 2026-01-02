<?php

namespace App\Actions;

interface Action
{
    /**
     * Execute the action.
     *
     * @param mixed ...$args
     * @return mixed
     */
    public function execute(...$args);
}
