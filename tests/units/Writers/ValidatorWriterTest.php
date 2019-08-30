<?php

namespace LaravelEnso\Cli\tests\units\Writers;

use Faker\Factory;
use LaravelEnso\Cli\app\Writers\ValidatorWriter;
use LaravelEnso\Cli\tests\Helpers\CliAsserts;
use Tests\TestCase;
use Illuminate\Support\Facades\File;
use LaravelEnso\Helpers\app\Classes\Obj;
use LaravelEnso\Cli\app\Writers\TableWriter;

class ValidatorWriterTest extends TestCase
{
    use CliAsserts;

    private $root;
    private $choices;
    private $params;
    private $faker;

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
                'name' => 'testModel'
            ],
            'permissions' => [

            ]
        ]);

        $this->params = new Obj([
            'root' => $this->root,
            'namespace' => 'App\\'
        ]);
    }

    protected function tearDown() :void
    {
        parent::tearDown();

        File::deleteDirectory($this->root);
    }

    /** @test */
    public function can_create_store()
    {
        $this->choices->put('permissions', new Obj(['store' => 'store']));
        (new ValidatorWriter($this->choices, $this->params))->run();

        $this->assertValidatorContains('namespace App\\Http\\Requests\\Group\\TestModels;', 'ValidateTestModelStore');
        $this->assertValidatorContains('class ValidateTestModelStore extends FormRequest', 'ValidateTestModelStore');
    }
    /** @test */
    public function can_create_update()
    {
        $this->choices->put('permissions', new Obj(['update' => 'update']));
        (new ValidatorWriter($this->choices, $this->params))->run();

        $this->assertValidatorContains('namespace App\\Http\\Requests\\Group\\TestModels;', 'ValidateTestModelUpdate');
        $this->assertValidatorContains('class ValidateTestModelUpdate extends ValidateTestModelStore', 'ValidateTestModelUpdate');
    }
}
