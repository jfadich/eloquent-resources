<?php

namespace jfadich\JsonResponder;

use jfadich\JsonResponder\Contracts\Transformable;
use jfadich\JsonResponder\Exceptions\InvalidResourceTypeException;

class TransformationManager
{
    protected $transformers = [];

    protected $modelNamespace = 'App';

    protected $transformerNamespace = 'App\\Transformers';

    protected $presentersNamespace = 'App\\Presenters';

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
     * @param $type
     * @return mixed|string
     * @throws InvalidResourceTypeException
     */
    public function getClassFromResourceType($type)
    {
        $type = explode('-', $type);
        $class = $this->modelNamespace;

        foreach ($type as $namespace) {
            $class .= '\\' . studly_case($namespace);
        }

        if (!class_exists($class)) {
            throw new InvalidResourceTypeException('Invalid model type');
        }

        return $class;
    }

    /**
     * Generate type string from class name
     *
     * @param $class
     * @return array|mixed|string
     */
    public  function getResourceTypeFromClass($class)
    {
        $namespace = str_replace("$this->modelNamespace\\", '', $class);
        $namespace = explode('\\', $namespace);

        $type = [];
        foreach ($namespace as $segment) {
            $type[] = snake_case($segment);
        }

        return implode('-', $type);
    }

    /**
     * Get transformer from the model.
     * If the associated transformed is not in the default namespace, refer to the $transformer property.
     *
     * @return Transformer
     */
    public function getTransformer(Transformable $model)
    {
        $class = get_class($model);
        $type = $this->getResourceTypeFromClass($class);

        if(array_key_exists($type, $this->transformers))
            return $this->transformers[$type];

        $transformer = str_replace($this->modelNamespace, $this->transformerNamespace, $class) . 'Transformer';

        if (class_exists($transformer)) {
            $this->transformers[$type] = app($transformer);
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