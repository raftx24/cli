<?php

namespace LaravelEnso\Cli\tests\units\Writers;

use Faker\Factory;
use LaravelEnso\Cli\app\Writers\OptionsWriter;
use LaravelEnso\Cli\tests\Helpers\CliAsserts;
use Tests\TestCase;
use Illuminate\Support\Facades\File;
use LaravelEnso\Helpers\app\Classes\Obj;

class OptionsWriterTest extends TestCase
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
        ]);
    }

    protected function tearDown() :void
    {
        parent::tearDown();

        File::deleteDirectory($this->root);
    }

    /** @test */
    public function can_create_controller()
    {
        (new OptionsWriter($this->choices, $this->params))->run();

        $this->assertControllerContains('class Options extends Controller', 'Options');
        $this->assertControllerContains('protected $model = TestModel::class;',
            'Options');
    }

}
