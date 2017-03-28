<?php

namespace jfadich\JsonResponder\Console;

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
     * Set the base class then call the parent
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        $this->parentClass = config('transformers.baseTransformer');

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
        if (!$model = $this->parseModel())
            $model = ['class' => 'Model', 'namespace' => 'Illuminate\Database\Eloquent'];

        $this->imports[] = $model['namespace'] . '\\' . $model['class'];

        $modelVar = $model['class'] . ' $' . lcfirst($model['class']);

        $stub = str_replace(
            'DummyModel', $modelVar, $stub
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
            return null;

        if (!starts_with($model, config('transformers.namespaces.models')))
            $model = config('transformers.namespaces.models') .'\\'. $model;

        $namespace = explode('\\', $model);

        return [
            'class' => array_pop($namespace),
            'namespace' => implode('\\', $namespace)
        ];
    }
}