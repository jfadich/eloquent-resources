<?php

namespace jfadich\EloquentResources;

use jfadich\EloquentResources\Exceptions\InvalidResourceTypeException;
use jfadich\EloquentResources\Contracts\Transformable;

class TransformationManager
{
    /**
     * @var array
     */
    protected $transformers = [];

    /**
     * @var array
     */
    protected $types = [];

    /**
     * @var string
     */
    protected $modelNamespace = 'App';

    /**
     * @var string
     */
    protected $transformerNamespace = 'App\\Transformers';

    /**
     * @var string
     */
    protected $presentersNamespace = 'App\\Presenters';

    /**
     * TransformationManager constructor.
     * @param string $model
     * @param string $transformer
     * @param string $presenter
     */
    public function __construct($model = null, $transformer = null, $presenter = null)
    {
        if($model !== null)
            $this->modelNamespace = $model;

        if($transformer !== null)
            $this->transformerNamespace = $transformer;

        if($presenter !== null)
            $this->presentersNamespace = $presenter;
    }

    /**
     * Reverse of getResourceType. Return the Class name from the given type.
     *
     * @param $typeString
     * @return mixed|string
     * @throws InvalidResourceTypeException
     */
    public function getClassFromResourceType($typeString)
    {
        if( !($class = array_search($typeString, $this->types)) ) {
            $type = explode('-', $typeString);
            $class = $this->modelNamespace;

            foreach ($type as $namespace) {
                $class .= '\\' . studly_case($namespace);
            }

            if (!class_exists($class)) {
                throw new InvalidResourceTypeException("Invalid model type: {$typeString}");
            }

            $this->types[$class] = $typeString;
        }

        return $class;
    }

    /**
     * Generate type string from class name
     *
     * @param $class
     * @return array|mixed|string
     */
    public function getResourceTypeFromClass($class)
    {
        if( !array_key_exists($class, $this->types) ) {
            $namespace = str_replace("$this->modelNamespace\\", '', $class);
            $namespace = explode('\\', $namespace);

            $type = [];
            foreach ($namespace as $segment) {
                $type[] = snake_case($segment);
            }

            $this->types[$class] = implode('-', $type);
        }

        return $this->types[$class];
    }

    /**
     * Get transformer from the model.
     *
     * @param Transformable|string $model
     * @return Transformer
     */
    public function getTransformer($model)
    {
        $class = is_string($model) ? $model : get_class($model);
        $type = $this->getResourceTypeFromClass($class);

        if(array_key_exists($type, $this->transformers))
            return $this->transformers[$type];

        $transformer = str_replace($this->modelNamespace, $this->transformerNamespace, $class) . 'Transformer';

        if (class_exists($transformer)) {
            $this->transformers[$type] = new $transformer;
        } else {
            $this->transformers[$type] = function ($model) use($type) {
                return [
                    'id' => $model->present('id'),
                    'type' => $type
                ];
            };
        }

        return $this->transformers[$type];
    }
}