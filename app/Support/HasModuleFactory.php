<?php

namespace App\Support;

trait HasModuleFactory
{
    protected static function newFactory()
    {
        $factory = '\\Database\\Factories\\'.class_basename(static::class).'Factory';

        return $factory::new();
    }
}
