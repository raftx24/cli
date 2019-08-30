<?php

namespace LaravelEnso\Cli\app\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use LaravelEnso\Helpers\app\Classes\Obj;
use LaravelEnso\Cli\app\Writers\FormWriter;
use LaravelEnso\Cli\app\Writers\TableWriter;
use LaravelEnso\Cli\app\Writers\ViewsWriter;
use LaravelEnso\Cli\app\Writers\RoutesWriter;
use LaravelEnso\Cli\app\Writers\OptionsWriter;
use LaravelEnso\Cli\app\Writers\PackageWriter;
use LaravelEnso\Cli\app\Writers\ValidatorWriter;
use LaravelEnso\Cli\app\Writers\ModelAndMigrationWriter;
use LaravelEnso\Cli\app\Writers\StructureMigrationWriter;

class Structure
{
    private $choices;
    private $params;
    private $isPackage;

    public function __construct(Obj $choices, Obj $params)
    {
        $this->choices = $choices;
        $this->params = $params;
        $this->isPackage = (bool) optional($this->choices->get('package'))->get('name');
        $this->prepareModel();
    }

    public function handle()
    {
        if ($this->isPackage) {
            $this->params->set('root', $this->packageRoot());
            $this->params->set('namespace', $this->packageNamespace());
            $this->writePackage();
        }

        $this->writeStructure();

        if (! $this->choices->has('files')) {
            return;
        }

        $this->writeModelAndMigration()
            ->writeRoutes()
            ->writeViews()
            ->writeForm()
            ->writeTable()
            ->writeOptions();
    }

    private function writeStructure()
    {
        App::makeWith(StructureMigrationWriter::class,
            ['choices' => $this->choices,'params' => $this->params]
        )->run();

        return $this;
    }

    public function writePackage()
    {
        App::makeWith(PackageWriter::class,
            ['choices' => $this->choices,'params' => $this->params]
        )->run();

        return $this;
    }

    private function writeModelAndMigration()
    {
        if ($this->choices->get('files')->has('model')
            || $this->choices->get('files')->has('table migration')) {
            App::makeWith(ModelAndMigrationWriter::class,
                ['choices' => $this->choices,'params' => $this->params]
            )->run();
        }

        return $this;
    }

    private function writeRoutes()
    {
        if ($this->choices->get('files')->has('routes')) {
            App::makeWith(RoutesWriter::class,
                ['choices' => $this->choices,'params' => $this->params]
            )->run();
        }

        return $this;
    }

    private function writeViews()
    {
        if ($this->choices->get('files')->has('views')) {
            App::makeWith(ViewsWriter::class,
                ['choices' => $this->choices,'params' => $this->params]
            )->run();
        }

        return $this;
    }

    private function writeForm()
    {
        if ($this->choices->get('files')->has('form')) {
            App::makeWith(FormWriter::class,
                ['choices' => $this->choices,'params' => $this->params]
            )->run();

            App::makeWith(ValidatorWriter::class,
                ['choices' => $this->choices,'params' => $this->params]
            )->run();
        }

        return $this;
    }

    private function writeTable()
    {
        if ($this->choices->get('files')->has('table')) {
            App::makeWith(TableWriter::class,
                ['choices' => $this->choices,'params' => $this->params]
            )->run();
        }

        return $this;
    }

    private function writeOptions()
    {
        if ($this->choices->get('files')->has('options')) {
            App::makeWith(OptionsWriter::class,
                ['choices' => $this->choices,'params' => $this->params]
            )->run();
        }

        return $this;
    }

    private function prepareModel()
    {
        $model = $this->choices->get('model');

        if (! Str::contains($model->get('name'), DIRECTORY_SEPARATOR) && ! $this->isPackage) {
            $model->set('namespace', 'App');

            return;
        }

        $segments = collect(explode(DIRECTORY_SEPARATOR, $model->get('name')));
        $model->set('name', $segments->pop());
        $model->set('namespace', $this->modelNamespace($segments));
        $model->set('path', $segments->implode(DIRECTORY_SEPARATOR));
    }

    private function packageNamespace()
    {
        return collect(explode(DIRECTORY_SEPARATOR, $this->packageRoot().'app'.DIRECTORY_SEPARATOR))
            ->reduce(function ($namespace, $word) {
                if (collect(['src', 'vendor'])->contains($word)) {
                    return $namespace;
                }

                if ($word === 'app') {
                    return $namespace->push($word);
                }

                return $namespace->push(ucfirst(Str::camel($word)));
            }, collect())->implode('\\');
    }

    private function packageRoot()
    {
        return 'vendor'.DIRECTORY_SEPARATOR
        .Str::kebab($this->choices->get('package')->get('vendor'))
        .DIRECTORY_SEPARATOR
        .Str::kebab($this->choices->get('package')->get('name'))
        .DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR;
    }

    private function modelNamespace($segments)
    {
        if ($this->isPackage) {
            return $segments->implode('\\')
                ? $this->packageNamespace().$segments->implode('\\')
                : collect(explode('\\', $this->packageNamespace()))
                    ->slice(0, -1)
                    ->implode('\\')
                    .$segments->implode('\\');
        }

        return 'App\\'.$segments->implode('\\');
    }
}
