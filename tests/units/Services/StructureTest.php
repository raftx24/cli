<?php

namespace LaravelEnso\Cli\tests\units\Services;

use Composer\Package\Package;
use Faker\Factory;
use Illuminate\Support\Facades\App;
use LaravelEnso\Cli\app\Services\Structure;
use LaravelEnso\Cli\app\Writers\FormWriter;
use LaravelEnso\Cli\app\Writers\ModelAndMigrationWriter;
use LaravelEnso\Cli\app\Writers\OptionsWriter;
use LaravelEnso\Cli\app\Writers\PackageWriter;
use LaravelEnso\Cli\app\Writers\RoutesWriter;
use LaravelEnso\Cli\app\Writers\StructureMigrationWriter;
use LaravelEnso\Cli\app\Writers\TableWriter;
use LaravelEnso\Cli\app\Writers\ValidatorWriter;
use LaravelEnso\Cli\app\Writers\ViewsWriter;
use LaravelEnso\Helpers\app\Classes\Obj;
use Tests\TestCase;

class StructureTest extends TestCase
{

    private $root;
    private $choices;
    private $params;
    private $faker;

    private $spy;
    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();

        $this->root = 'cli_tests_tmp/';

        $this->choices = new Obj([
            'permissionGroup' => [
                'name' => 'group.testModels'
            ],
            'model' => [
                'name' => 'testModel',
                'namespace' => 'Classes'
            ],
            'permissions' => [
            ]
        ]);

        $this->params = new Obj([
            'root' => $this->root,
            'namespace' => 'App\\'
        ]);


        $this->initSpies();
    }

    /** @test */
    public function can_set_namespace_in_model_without_namespace()
    {
        $this->choices->put('files', new Obj(['model'=> true]));
        $this->choices->put('model', new Obj(['name' => 'model']));

        (new Structure($this->choices,$this->params))->handle();

        $args = $this->spy->get(ModelAndMigrationWriter::class);
        $this->assertEquals($args['choices']->get('model')->get('namespace'), 'App');
        $this->assertEquals($args['choices']->get('model')->get('name'), 'model');
    }

    /** @test */
    public function can_set_namespace_in_model_with_namespace()
    {
        $this->choices->put('files', new Obj(['model'=> true]));
        $this->choices->put('model', new Obj(['name' => 'namespace/model']));

        (new Structure($this->choices,$this->params))->handle();

        $args = $this->spy->get(ModelAndMigrationWriter::class);
        $this->assertEquals($args['choices']->get('model')->get('namespace'), 'App\namespace');
        $this->assertEquals($args['choices']->get('model')->get('name'), 'model');
    }

    /** @test */
    public function can_set_namespace_and_path_in_model_with_namespace()
    {
        $this->choices->put('files', new Obj(['model' => true]));
        $this->choices->put('model', new Obj(['name' => 'namespace/model']));

        (new Structure($this->choices,$this->params))->handle();

        $choices = $this->spy->get(ModelAndMigrationWriter::class)['choices'];
        $this->assertEquals($choices->get('model')->get('namespace'), 'App\namespace');
        $this->assertEquals($choices->get('model')->get('name'), 'model');
        $this->assertEquals($choices->get('model')->get('path'), 'namespace');
    }

    /** @test */

    public function can_set_root_and_namespace_in_packages()
    {
        $this->choices->put('package', new Obj(['name' => 'package','vendor'=>'user']));

        (new Structure($this->choices,$this->params))->handle();

        $choices = $this->spy->get(PackageWriter::class)['params'];
        $this->assertEquals($choices->get('root'), 'vendor/user/package/src/');
        $this->assertEquals($choices->get('namespace'), 'User\Package\app\\');
    }

    /** @test */

    public function can_call_writers()
    {
        $this->choices->put('files', new Obj([
            'form' => true,'model' => true,'table' => true,'options' => true,
            'routes' => true, 'views' => true,
        ]));

        (new Structure($this->choices,$this->params))->handle();

        $result = collect([
            TableWriter::class, ViewsWriter::class, RoutesWriter::class, OptionsWriter::class,
            ValidatorWriter::class, ModelAndMigrationWriter::class, StructureMigrationWriter::class, FormWriter::class,
        ])->filter(function($writer){
            $this->assertEquals($this->spy->get($writer)['choices'], $this->choices,$writer.' called with wrong choices');
            $this->assertEquals($this->spy->get($writer)['params'], $this->params, $writer.' called with wrong params');

            return false;
        });

        $this->assertEmpty($result);
    }

    protected function initSpies(): void
    {
        $this->spy = new Obj();

        collect([
            TableWriter::class, ViewsWriter::class, RoutesWriter::class, OptionsWriter::class, PackageWriter::class,
            ValidatorWriter::class, ModelAndMigrationWriter::class, StructureMigrationWriter::class, FormWriter::class,
        ])->each(function ($writer) {
            App::bind($writer, function ($choices, $args) use ($writer) {
                $this->spy->put($writer, $args);

                return new DummyWriter();
            });
        });
    }
}

class DummyWriter {
    public function run()
    {
    }
}
