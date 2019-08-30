<?php

namespace LaravelEnso\Cli\tests\units\Writers;

use Faker\Factory;
use LaravelEnso\Cli\app\Writers\RouteGenerator;
use Tests\TestCase;
use Illuminate\Support\Facades\File;
use LaravelEnso\Helpers\app\Classes\Obj;
use LaravelEnso\Cli\app\Writers\FormWriter;

class RouteGeneratorTest extends TestCase
{
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
                'name' => 'testModel'
            ],
            'model' => [
                'name' => 'testModel'
            ],
            'permissions' => [
                'index' => 'index', 'create' => 'create', 'store' => 'store', 'edit' => 'edit',
                'exportExcel' => 'exportExcel' , 'destroy' => 'destroy', 'initTable' => 'initTable',
                'tableData' => 'tableData', 'update' => 'update','options' => 'options', 'show' => 'show',
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
    public function can_create_route_group()
    {
        $this->choices->get('permissionGroup')->put('name','a.b.c');

        $result = (new RouteGenerator($this->choices, $this->params))->run();

        $this->assertContains('Route::namespace(\'A\\B\\C\')', $result);
        $this->assertContains('->prefix(\'a/b/c\')->as(\'a.b.c.\')', $result);
    }

    /** @test */
    public function can_create_routes()
    {
        $result = (new RouteGenerator($this->choices, $this->params))->run();

        $this->assertRoutes($result);
    }

    /** @test */
    public function can_create_routes_for_package()
    {
        $this->choices->put('package', new Obj())->get('package')->put('name', 'testPackage');

        (new RouteGenerator($this->choices, $this->params))->run();

        $this->assertRoutes(File::get($this->root.DIRECTORY_SEPARATOR.'routes/api.php'));
    }

    /**
     * @param $result
     */
    private function assertRoutes($result): void
    {
        $this->assertContains("Route::get('', 'Index')->name('index');", $result);
        $this->assertContains("Route::get('create', 'Create')->name('create');", $result);
        $this->assertContains("Route::get('{testModel}/edit', 'Edit')->name('edit');", $result);
        $this->assertContains("Route::get('options', 'Options')->name('options');", $result);
        $this->assertContains("Route::patch('{testModel}', 'Update')->name('update');", $result);
        $this->assertContains("Route::post('', 'Store')->name('store');", $result);
        $this->assertContains("Route::delete('{testModel}', 'Destroy')->name('destroy');", $result);
        $this->assertContains("Route::get('initTable', 'InitTable')->name('initTable');", $result);
        $this->assertContains("Route::get('tableData', 'TableData')->name('tableData');", $result);
        $this->assertContains("Route::get('exportExcel', 'ExportExcel')->name('exportExcel');", $result);
        $this->assertContains("Route::get('{testModel}', 'Show')->name('show');", $result);
    }
}
