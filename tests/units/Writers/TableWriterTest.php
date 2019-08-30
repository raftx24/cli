<?php

namespace LaravelEnso\Cli\tests\units\Writers;

use Faker\Factory;
use LaravelEnso\Cli\tests\Helpers\CliAsserts;
use Tests\TestCase;
use Illuminate\Support\Facades\File;
use LaravelEnso\Helpers\app\Classes\Obj;
use LaravelEnso\Cli\app\Writers\TableWriter;

class TableWriterTest extends TestCase
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
    public function can_create_template()
    {
        (new TableWriter($this->choices, $this->params))->run();

        $this->assertTableTemplateContains('"routePrefix": "group.testModels"');
        $this->assertTableTemplateContains('"routePrefix": "group.testModels"');
        $this->assertTableTemplateContains('"name": "Test Model"');
        $this->assertTableTemplateContains('"data": "test_models.id"');
    }


    /** @test */
    public function can_create_builder()
    {
        (new TableWriter($this->choices, $this->params))->run();

        $this->assertTableBuilderContains('class TestModelTable extends Table');
        $this->assertTableBuilderContains('class TestModelTable extends Table');
        $this->assertTableBuilderContains('test_models.id as "dtRowId", test_models.id');
    }

    /** @test */
    public function can_create_controller()
    {
        $this->choices->put('permissions',new Obj([
            'initTable' => 'initTable', 'tableData' => 'tableData'
        ]));

        (new TableWriter($this->choices, $this->params))->run();

        $this->assertControllerContains('class InitTable extends Controller', 'InitTable');
        $this->assertControllerContains('use Init;', 'InitTable');
        $this->assertControllerContains('protected $tableClass = TestModelTable::class;', 'InitTable');
    }
}
