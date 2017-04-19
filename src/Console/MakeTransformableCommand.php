<?php

namespace jfadich\EloquentResources\Console;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;

/**
 * Command to generate a new presenter class
 */
class MakeTransformableCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:transformable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Transformable Model class';

    /**
     * The type of class being generated
     *
     * @var string
     */
    protected $type = 'Model';

    /**
     * Set the base class then call the parent
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        $this->parentClass = config('transformers.classes.model');

        parent::__construct($files);
    }

    /**
     * Execute the console command. Generate a transformer if the -api switch is on
     *
     * @return void
     */
    public function fire()
    {
        if ($this->option('present')) {
            $this->addTrait(\jfadich\EloquentResources\Traits\Presentable::class);
        }

        if (parent::fire() !== false) {
            $this->call('make:transformer', ['name' => $this->argument('name') . 'Transformer', '-m' => $this->parseName($this->argument('name'))]);

            if ($this->option('present')) {
                $this->call('make:presenter', ['name' => $this->argument('name') . 'Presenter']);
            }
        }
    }

    /**
     * Add the $table property to the class
     *
     * @param  string $name
     * @return string
     */
    protected function prepClass(&$stub, $name)
    {
        return parent::prepClass($stub, $name)->replaceTable($stub);
    }

    /**
     * Replace the table for the given stub.
     *
     * @param  string $stub
     * @return $this
     */
    protected function replaceTable(&$stub)
    {
        if (!$this->option('table')) {
            $stub = str_replace(
                'DummyTable', "//", $stub
            );
        } else {
            $stub = str_replace(
                'DummyTable', "protected \$table='{$this->option('table')}';", $stub
            );
        }

        return $this;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['table', 't', InputOption::VALUE_OPTIONAL, 'Set the table for the model.'],
            ['present', 'p', InputOption::VALUE_NONE, 'Generate a Presenter for the model.']
        ];
    }
}