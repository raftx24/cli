<?php

namespace LaravelEnso\Cli\app\Services;

use Illuminate\Support\Str;
use LaravelEnso\Cli\app\Writers\RouteGenerator;
use LaravelEnso\Cli\app\Services\Helpers\Symbol;

class Generator
{
    private $choices;

    public function __construct(Choices $choices)
    {
        $this->choices = $choices;
    }

    public function handle()
    {
        if ($this->choices->needsValidation() && $this->failsValidation()) {
            $this->outputErrors();

            return false;
        }

        $this->filterUnconfigured()
            ->write()
            ->output();

        $this->choices->clear();

        return true;
    }

    private function failsValidation()
    {
        if (! $this->choices->isConfigured()) {
            $this->console()->error('There is nothing configured yet!');
            $this->console()->line('');

            sleep(1);

            return true;
        }

        $this->choices->setValidator((new Validator($this->choices))->run());

        return $this->choices->validator()->fails();
    }

    private function outputErrors()
    {
        if (! $this->choices->validator()) {
            return;
        }

        $this->console()->warn('Your configuration has errors:');
        $this->console()->line('');

        $this->choices->validator()->errors()
                ->each(function ($errors, $type) {
                    $this->console()->info($type.' '.Symbol::exclamation());

                    $errors->each(function ($error) {
                        $this->console()->warn('    '.$error);
                    });
                });

        $this->console()->line('');

        sleep(1);
    }

    private function filterUnconfigured()
    {
        $this->choices->keys()->each(function ($key) {
            if ($this->choices->configured()->first(function ($attribute) use ($key) {
                return Str::camel($attribute) === $key;
            }) === null) {
                $this->choices->forget($key);
            }
        });

        if ($this->choices->hasFiles()) {
            $this->choices->files()->each(function ($chosen, $type) {
                if (! $chosen) {
                    $this->choices->files()->forget($type);
                }
            });
        }

        return $this;
    }

    private function write()
    {
        (new Structure($this->choices))->handle();

        return $this;
    }

    private function output()
    {
        if ($this->choices->has('permissions')) {
            $routes = (new RouteGenerator($this->choices))->handle();

            if ($routes) {
                $this->console()->info('Copy and paste the following code into your api.php routes file:');
                $this->console()->line('');
                $this->console()->warn($routes);
                $this->console()->line('');
            }
        }

        if ((bool) optional($this->choices->get('package'))->get('config')) {
            $this->console()->info("Your package is created, you can start playing. Don't forget to run `git init` in the package root folder!");
            $this->console()->warn('Add your package namespace and path inside your `composer.json` file under the `psr-4` key while developing.');
        }

        if ((bool) optional($this->choices->get('package'))->get('providers')) {
            $this->console()->warn('Remember to add the package`s service provider to the `config/app.php` list of providers.');
        }

        $this->console()->line('');
    }

    private function console()
    {
        return $this->choices->console();
    }
}
