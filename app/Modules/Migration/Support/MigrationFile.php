<?php

namespace App\Modules\Migration\Support;

class MigrationFile
{
    public string $name;
    public string $path;

    public function __construct($name, $path)
    {
        $this->name = $name;
        $this->path = $path;
    }

    public function require()
    {
        return require $this->path;
    }
}
