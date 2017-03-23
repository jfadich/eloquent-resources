<?php

namespace jfadich\JsonResponder\Console;

use Illuminate\Console\GeneratorCommand as LaravelGenerator;
use Illuminate\Filesystem\Filesystem;
use jfadich\JsonResponder\Exceptions\GeneratorException;

abstract class GeneratorCommand extends LaravelGenerator
{
    /**
     * Classes that need to be imported into the generated class.
     *
     * @var array
     */
    protected $imports = [];

    /**
     * Base class the the generated class extends.
     *
     * @var string
     */
    protected $parentClass;

    /**
     * Base namespace for the generated class.
     *
     * @var string
     */
    protected $namespace;

    /**
     * Array of traits to the included in the class.
     *
     * @var array
     */
    private $traits = [];

    /**
     * Create a new controller creator command instance.
     *
     * @param Filesystem $files
     * @throws \Exception
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct($files);

        if ($this->parentClass === null)
            throw new GeneratorException('Parent class not set');

        $parentClass = explode('\\', $this->parentClass);

        $this->type = array_pop($parentClass);
        $this->namespace = config('transformers.namespaces.'.strtolower($this->type).'s');
    }

    /**
     * Build the class with the given name.
     *
     * @param  string $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        return $this->prepClass($stub, $name)->replaceImports($stub, $name)->replaceTraits($stub)->replaceClass($stub, $name);
    }

    /**
     * Set the namespace.
     * Also acts as a hook for child generators to have access to the stub
     *
     * @param $stub
     * @param $name
     * @return $this
     */
    protected function prepClass(&$stub, $name)
    {
        return $this->replaceNamespace($stub, $name);
    }


    /**
     * Replace dummy trait on model with registered traits
     *
     * @param $stub
     * @return $this
     */
    protected function replaceTraits(&$stub)
    {
        if (empty($this->traits)) {
            $stub = str_replace(
                'DummyTraits', '', $stub
            );
        } else {
            $stub = str_replace(
                'DummyTraits',
                'use ' . implode(',', $this->traits) . ';' . PHP_EOL,
                $stub
            );
        }

        return $this;
    }

    /**
     * Add trait name to trait list and add full trait name to imports
     *
     * @param $trait
     */
    protected function addTrait($trait)
    {
        $this->traits[] = class_basename($trait);
        $this->imports[] = $trait;
    }

    /**
     * Add use statements to the top of the class.
     *
     * @param $stub
     * @param $name
     * @return $this
     */
    protected function replaceImports(&$stub, $name)
    {
        if ($this->parentClass !== null && count(explode('\\', $name)) > count(explode('\\', $this->parentClass)))
            array_unshift($this->imports, $this->parentClass);

        $importString = '';

        foreach ($this->imports as $import) {
            $importString .= "use $import;" . PHP_EOL;
        }

        $stub = str_replace(
            'DummyImport', $importString, $stub
        );

        return $this;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $this->namespace !== null ? $this->namespace : $rootNamespace;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return base_path("vendor/jfadich/json-responder/stubs/{$this->type}.stub");
    }
}