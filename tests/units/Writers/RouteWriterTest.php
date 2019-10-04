<?php

namespace LaravelEnso\Cli\tests\units\Writers;

use Tests\TestCase;
use Illuminate\Support\Facades\File;
use LaravelEnso\Cli\tests\Helpers\Cli;
use LaravelEnso\Helpers\app\Classes\Obj;
use LaravelEnso\Cli\app\Writers\RoutesWriter;

class RouteWriterTest extends TestCase
{
    use Cli;

    private $root;
    private $choices;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = 'cli_tests_tmp/';

        $this->initChoices();
    }

    protected function tearDown() :void
    {
        parent::tearDown();

        File::deleteDirectory($this->root);
    }

    /** @test */
    public function can_create_route_directory()
    {
        (new RoutesWriter($this->choices))->handle();

        $this->assertDirectoryExists($this->root.'resources/js/routes/perm/group');
    }

    /** @test */
    public function can_create_index_route()
    {
        $this->setPermission('index');

        (new RoutesWriter($this->choices))->handle();

        $this->assertViewRouteContains([
            "name: 'perm.group.index'",
            'component: TestModelIndex',
            "title: 'Tests Models'",
        ], 'perm/group/index.js');
    }

    /** @test */
    public function can_create_show_route()
    {
        $this->setPermission('show');

        (new RoutesWriter($this->choices))->handle();

        $this->assertViewRouteContains([
            "name: 'perm.group.show'",
            'component: TestModelShow',
            "title: 'Tests Models Profile'"
        ], 'perm/group/show.js');
    }

    /** @test */
    public function can_create_edit_route()
    {
        $this->setPermission('edit');

        (new RoutesWriter($this->choices))->handle();

        $this->assertViewRouteContains([
            "name: 'perm.group.edit'",
            'component: TestModelEdit',
            "title: 'Edit Tests Models'"
        ], 'perm/group/edit.js');
    }

    /** @test */
    public function can_create_segment_route()
    {
        $this->setPermission('edit');

        (new RoutesWriter($this->choices))->handle();

        $this->assertViewRouteContains([
            "const routes = routeImporter(require.context('./perm', false, /.*\.js$/))",
            "path: '/perm'",
        ], 'perm.js');
    }

    /** @test */
    public function can_create_parent_segment_route()
    {
        $this->setPermission('edit');

        (new RoutesWriter($this->choices))->handle();

        $this->assertViewRouteContains([
            "const routes = routeImporter(require.context('./group', false, /.*\.js$/));",
            "path: 'group'",
            "breadcrumb: 'group'",
            "route: 'perm.group.index'",
        ], 'perm/group.js');
    }

    /** @test */
    public function cannot_create_non_route()
    {
        $this->setPermission('destroy');

        (new RoutesWriter($this->choices))->handle();

        $this->assertFileNotExists($this->viewRoutePath('perm/group/destroy.js'));
    }

    /** @test */
    public function cannot_create_route_for_false_permission()
    {
        $this->choices->put('permissions', collect(['show' => false]));

        (new RoutesWriter($this->choices))->handle();

        $this->assertFileNotExists($this->viewRoutePath('perm/group/show.js'));
    }

    /** @test */
    public function can_create_create_route()
    {
        $this->setPermission('create');

        (new RoutesWriter($this->choices))->handle();

        $this->assertViewRouteContains([
            "name: 'perm.group.create'",
            'component: TestModelCreate',
            "title: 'Create Tests Models'",
        ], 'perm/group/create.js');
    }

    protected function choices()
    {
        return new Obj([
            'permissionGroup' => ['name' => 'perm.group'],
            'model' => ['name' => 'testModel'],
            'permissions' => [],
        ]);
    }

    protected function params()
    {
        return new Obj(['root' => $this->root]);
    }
}
