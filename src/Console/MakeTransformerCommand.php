<?php

namespace jfadich\EloquentResources\Console;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;

/**
 * Command to generate a new transformer class
 *
 * Options
 * -m= Specific model to transform
 *
 */
class MakeTransformerCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:transformer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new transformer class';

    /**
     * The type of class being generated
     *
     * @var string
     */
    protected $type = 'Transformer';

    /**
     * Set the base class then call the parent
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        $this->parentClass = config('transformers.classes.transformer');

        parent::__construct($files);
    }
    /**
     * Add the transformed model to the constructor and imports
     *
     * @param  string $name
     * @return string
     */
    protected function prepClass(&$stub, $name)
    {
        return parent::prepClass($stub, $name)->replaceModel($stub);
    }

    /**
     * Replace the model name in class
     *
     * @param $stub
     * @return $this
     */
    protected function replaceModel(&$stub)
    {
        $model = $this->parseModel();

        $this->imports[] = $model['namespace'] . '\\' . $model['class'];

        $modelVar = '$' . lcfirst($model['class']);

        $stub = str_replace(
            'DummyModelVariable', $modelVar, $stub
        );

        $stub = str_replace(
            'DummyModelClass', $model['class'], $stub
        );

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
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'Define the model to transform.']
        ];
    }

    /**
     * Parse the provided model and return an array with the class and namespace.
     *
     * @return array|null
     */
    private function parseModel()
    {
        if (!$model = $this->option('model'))
            $model = config('transformers.classes.model');

        if (!starts_with($model, config('transformers.namespaces.models')))
            $model = config('transformers.namespaces.models') .'\\'. $model;

        $namespace = explode('\\', $model);

        return [
            'class' => array_pop($namespace),
            'namespace' => implode('\\', $namespace)
        ];
    }
}