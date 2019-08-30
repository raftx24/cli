<?php

namespace LaravelEnso\Cli\tests\units\Writers;

use LaravelEnso\Cli\tests\Helpers\CliAsserts;
use Tests\TestCase;
use Faker\Factory;
use Illuminate\Support\Facades\File;
use LaravelEnso\Helpers\app\Classes\Obj;
use LaravelEnso\Cli\app\Writers\PackageWriter;

class PackageWriterTest extends TestCase
{
    use CliAsserts;

    private $root;
    private $choices;
    private $params;
    private $faker;
    private $vendor;
    private $name;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();

        $this->name = $this->faker->word;
        $this->vendor = $this->faker->word;
        $this->root = 'cli_tests_tmp/';

        $this->choices = new Obj([
            'package' => [
                'vendor' => $this->vendor,
                'name' => $this->name,
            ],
        ]);

        $this->params = new Obj([
            'root' => $this->root,
            'namespace' => $this->vendor.'\\'.$this->name.'\\app\\', //in the package writer assumed that every namespace ended with \\app\\
        ]);
    }

    protected function tearDown() :void
    {
        parent::tearDown();

        File::deleteDirectory($this->root);
    }


    /** @test */
    public function can_create_directory()
    {
        (new PackageWriter($this->choices, $this->params))->run();

        $this->assertDirectoryExists($this->root);
    }

    /** @test */
    public function can_create_composer()
    {
        (new PackageWriter($this->choices, $this->params))->run();

        $this->assertFileContains($this->vendor.'/'.$this->name, 'composer.json');
        $this->assertFileContains($this->vendor.'\\\\'.$this->name, 'composer.json');
        $this->assertFileContains($this->vendor.'\\\\'.$this->name, 'composer.json');
    }

    /** @test */
    public function can_create_readme()
    {
        (new PackageWriter($this->choices, $this->params))->run();

        $this->assertFileContains($this->vendor.' - '.$this->name, 'README.md');
    }

    /** @test */
    public function can_create_licence()
    {
        (new PackageWriter($this->choices, $this->params))->run();

        $this->assertFileContains(now()->format('Y').' '.$this->vendor, 'LICENSE');
    }


    /** @test */
    public function can_create_config()
    {
        $this->choices->get('package')->put('config', true);
        (new PackageWriter($this->choices, $this->params))->run();

        $this->assertFileExists($this->root.'config/'.$this->name.'.php');
    }

    /** @test */
    public function can_create_provider()
    {
        $this->choices->get('package')->put('providers', true);
        (new PackageWriter($this->choices, $this->params))->run();

        $namespace = 'namespace '.$this->vendor.'\\'.$this->name;
        $this->assertFileContains($namespace,'AppServiceProvider.php');
        $this->assertFileContains($namespace,'AuthServiceProvider.php');
    }

    //private function assertFileContains($needle, $filePath)
    //{
    //    $this->assertContains($needle, File::get($filePath));
    //}
}
