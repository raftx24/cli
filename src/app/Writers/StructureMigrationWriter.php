<?php

namespace LaravelEnso\Cli\app\Writers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use LaravelEnso\Helpers\app\Classes\Obj;

class StructureMigrationWriter
{
    private $choices;
    private $params;

    public function __construct(Obj $choices, Obj $params)
    {
        $this->choices = $choices;
        $this->params = $params;
    }

    public function run()
    {
        [$from, $to] = $this->replaceFromTo();

        if (! File::isDirectory($this->path())) {
            File::makeDirectory($this->path(), 0755, true);
        }

        File::put(
            $this->path().$this->name(),
            str_replace($from, $to, $this->stub('migration'))
        );
    }

    private function replaceFromTo()
    {
        $array = [
            '${Entity}' => Str::plural($this->entity()),
            '${menu}' => $this->menu(),
            '${parentMenu}' => $this->parentMenu(),
            '${permissions}' => $this->permissions(),
        ];

        return [
            array_keys($array),
            array_values($array),
        ];
    }

    private function menu()
    {
        if ($this->choices->has('menu')) {
            $menu = $this->choices->get('menu');

            $stub = str_replace(
                $this->mapping($menu->keys()),
                $this->writableValues($menu->values()),
                $this->stub('menu')
            );
        }

        return $stub ?? 'null';
    }

    private function parentMenu()
    {
        if ($this->choices->has('menu')) {
            $stub = str_replace(
                '${parentMenu}',
                $this->choices->get('menu')->get('parentMenu'),
                $this->stub('parentMenu')
            );
        }

        return isset($stub) && $stub ? $stub : null;
    }

    private function permissions()
    {
        if ($this->choices->has('permissions')) {
            $stub = $this->choices->get('permissions')
                ->filter()
                ->keys()
                ->reduce(function ($content, $permission) {
                    [$from, $to] = $this->permissionReplaceFromTo();
                    $stub = $this->stub('permissions'.DIRECTORY_SEPARATOR.$permission);

                    return $content.PHP_EOL.str_replace($from, $to, $stub);
                }, '');
        }

        return isset($stub)
            ? '['.$stub.PHP_EOL.'    ]'
            : 'null';
    }

    private function permissionReplaceFromTo()
    {
        $array = [
            '${permissionGroup}' => $this->choices->get('permissionGroup')->get('name'),
            '${model}' => strtolower(str_replace('_', ' ', Str::snake($this->model()))),
        ];

        return [
            array_keys($array),
            array_values($array),
        ];
    }

    private function model()
    {
        return $this->choices->has('model')
            ? $this->choices->get('model')->get('name')
            : null;
    }

    private function entity()
    {
        return $this->choices->has('model')
            ? ucfirst($this->choices->get('model')->get('name'))
            : ucfirst($this->choices->get('menu')->get('name'));
    }

    private function mapping(Obj $keys)
    {
        return $keys->map(function ($key) {
            return '${'.$key.'}';
        })->toArray();
    }

    private function writableValues(Obj $values)
    {
        return $values->map(function ($value) {
            if (is_bool($value)) {
                return $value
                    ? 'true'
                    : 'false';
            }

            return is_string($value) && empty($value)
                ? null
                : $value;
        })->toArray();
    }

    private function name()
    {
        return now()->format('Y_m_d_His')
            .'_create_structure_for_'
            .Str::snake(Str::plural($this->entity()))
            .'.php';
    }

    public function path()
    {
        return $this->params->get('root')
            .'database'
            .DIRECTORY_SEPARATOR
            .'migrations'
            .DIRECTORY_SEPARATOR;
    }

    private function stub($stub)
    {
        return File::get(
            __DIR__.DIRECTORY_SEPARATOR.'stubs'
            .DIRECTORY_SEPARATOR.'structure'
            .DIRECTORY_SEPARATOR.$stub.'.stub'
        );
    }
}
