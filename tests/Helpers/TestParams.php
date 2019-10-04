<?php

namespace LaravelEnso\Cli\tests\Helpers;

use Illuminate\Support\Facades\File;
use LaravelEnso\Helpers\app\Classes\Obj;

class TestParams
{
    public static function load()
    {
        return new Obj(json_decode(
            File::get(__DIR__.'/stubs/testParams.stub')
        ));
    }
}
