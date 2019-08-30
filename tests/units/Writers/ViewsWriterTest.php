<?php

namespace LaravelEnso\Cli\tests\units\Writers;

use Faker\Factory;
use LaravelEnso\Cli\app\Writers\ValidatorWriter;
use LaravelEnso\Cli\app\Writers\ViewsWriter;
use LaravelEnso\Cli\tests\Helpers\CliAsserts;
use Tests\TestCase;
use Illuminate\Support\Facades\File;
use LaravelEnso\Helpers\app\Classes\Obj;
use LaravelEnso\Cli\app\Writers\TableWriter;

class ViewsWriterTest extends TestCase
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
                'edit' => 'edit', 'create' => 'create', 'index' => 'index',
                'show' => 'show',
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
    public function can_create_views()
    {
        (new ViewsWriter($this->choices, $this->params))->run();


        $this->assertDirectoryExists($this->root.'resources/js/pages/group/testModels');
        $this->choices->get('permissions')->each(function($perm){
            $this->assertViewPageFileContains("name: '".ucfirst($perm)."',", ucfirst($perm));
        });

    }
}
