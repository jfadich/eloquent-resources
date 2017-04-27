<?php

namespace jfadich\EloquentResources;

use Illuminate\Database\Eloquent\{
    Relations\Relation,
    Builder,
    Model
};

use jfadich\EloquentResources\{
    Contracts\Presentable,
    Exceptions\InvalidModelRelation,
    Exceptions\InvalidResourceTypeException,
    Exceptions\MissingTransformerException,
    Contracts\Transformable
};

use League\Fractal\{
    Pagination\IlluminatePaginatorAdapter,
    Resource\Collection,
    Manager as Fractal,
    Resource\Item,
    ParamBag
};

use Illuminate\{
    Contracts\Pagination\LengthAwarePaginator,
    Http\Request
};


class ResourceManager
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
     * @var Fractal
     */
    protected $fractal;

    /**
     * Array of related objects that should be included in the request
     *
     * @var array
     */
    protected $includes = [];

    /**
     * @var Request
     */
    private $request;

    /**
     * @param Fractal $fractal
     * @param Request $request
     * @param array $namespaces
     * @internal param string $model
     * @internal param string $transformer
     * @internal param string $presenter
     */
    public function __construct(Fractal $fractal, Request $request, array $namespaces = [])
    {
        $this->fractal = $fractal;
        $this->request = $request;
        $this->fractal->setRecursionLimit(config('transformers.parameters.includes.max'));

        $includesName = config('transformers.parameters.includes.name');
        if($request->has($includesName))
            $this->fractal->parseIncludes($request->get($includesName));

        if(array_key_exists('models', $namespaces))
            $this->modelNamespace = $namespaces['models'];

        if(array_key_exists('transformers', $namespaces))
            $this->transformerNamespace = $namespaces['transformers'];

        if(array_key_exists('presenters', $namespaces))
            $this->presentersNamespace = $namespaces['presenters'];
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
        $class = is_string($class) ? $class : get_class($class);

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
            $this->transformers[$type] = new class extends Transformer {
                public function transform(Presentable $model)
                {
                    return $this->prepModel($model);
                }
            };
        }

        return $this->transformers[$type];
    }

    /**
     * Get the eagerload array. If sort/order params are provided apply them to the eagerload constraint
     *
     * @param Transformer $transformer
     * @param $model
     * @return array
     * @throws InvalidModelRelation
     */
    protected function getEagerLoad(Transformer $transformer, $model)
    {
        $eager = [];
        $includes = $this->getIncludes($transformer->getLazyIncludes());

        foreach($includes as $include) {
            if(!method_exists($model, $include)) {
                throw new InvalidModelRelation("'$include' cannot be eager loaded. If the include is not an Eloquent relation try adding it to the 'lazyIncludes' array on the transformer");
            }

            $relatedModel = $model->{$include}()->getRelated();
            $relatedTransformer = $this->getTransformer($relatedModel);

            $params = $relatedTransformer->parseParams($this->fractal->getIncludeParams($include));
            $order = $params['order'] ?? null;

            if (is_array($order) && count($order) === 2 && in_array($order[1], ['desc', 'asc'])) {
                $eager[$include] = function($query) use($order) {
                    return $query->orderBy($order[0], $order[1]);
                };
            } else {
                $eager[] = $include;
            }
        }

        return $eager;
    }

    public function resolveCollectionResource($collection, $callback = null, $meta = [])
    {
        list($collection, $callback, $meta) = $this->resolveQuery($collection, $callback, $meta);

        // If a query builder instance is given set the eager loads and paginate the data.
        if ($collection instanceof Builder || $collection instanceof Relation) {
            $config = config('transformers.parameters');

            if($this->request->has($config['sort']['name'])) {
                $sort = explode('|', $this->request->get($config['sort']['name']));

                $column = !empty($sort[0]) ? $sort[0] : null;
                $order  = !empty($sort[1]) && in_array($sort[1], ['asc', 'desc']) ? $sort[1] : 'asc';

                if($column !== null && $callback instanceof Transformer) {
                    $params = $callback->parseParams(new ParamBag([
                        $config['sort']['name'] => [$column, $order]
                    ]));

                    $collection = $collection->orderBy($params['order'][0], $params['order'][1]);
                }
            }

            $collection = $collection->paginate($this->getResourceCount());
        }

        $resource = new Collection($collection->all(), $callback);

        if (!empty($meta)) {
            foreach ($meta as $k => $v) {
                $resource->setMetaValue($k, $v);
            }
        }

        // Set the pagination details
        if ($collection instanceof LengthAwarePaginator) {
            $collection->appends($this->request->except('page'));

            $resource->setPaginator(new IlluminatePaginatorAdapter($collection));
        }

        return $this->fractal->createData($resource);
    }

    public function resolveItemResource($item, $callback = null, $meta = [])
    {
        list($item, $callback, $meta) = $this->resolveQuery($item, $callback, $meta);

        // If a query builder instance is given set the eager loads and paginate the data.
        if ($item instanceof Builder || $item instanceof Relation) {
            $item = $item->firstOrFail();
        }

        if($callback === null) {
            throw new MissingTransformerException('Collection callback not provided.');
        }

        $resource = new Item($item, $callback);

        if (!empty($meta)) {
            foreach ($meta as $k => $v) {
                $resource->setMetaValue($k, $v);
            }
        }

        return $this->fractal->createData($resource);
    }

    protected function resolveQuery($resource, $callback = null, $meta = [])
    {
        // If a query builder instance is given set the eager loads and paginate the data.
        $isQuery = $resource instanceof Builder || $resource instanceof Relation;

        if ($isQuery) {
            // If the given $callback is not callable check for a meta array
            if(is_array($callback) && empty($meta)) {
                $meta = $callback;
            }

            $model = $resource->getModel();
        } else {
            $model = $resource;
        }

        if(!is_callable($callback)) {
            if(!$model instanceof Transformable)
                throw new MissingTransformerException('Provided model must be an instance of Transformable');

            $callback = $this->getTransformer($model);
        }

        if($callback instanceof Transformer) {
            $eager = $this->getEagerLoad($callback, $model);
        } else {
            $eager = $this->getIncludes();
        }

        // Eager load the included relationships. If a model is given, make sure the related models aren't already loaded
        if( !empty($eager) ) {
            if ($isQuery) {
                $resource = $resource->with($eager);
            } elseif( $resource instanceof Model ) {
                if( empty($resource->getRelations()) ) {
                    $resource = $resource->load($this->getEagerLoad($callback, $model));
                }
            }
        }

        if($callback === null || (!is_callable($callback) && !$callback instanceof Transformer)) {
            throw new MissingTransformerException('Resource callback not provided.');
        }

        return [$resource, $callback, $meta];
    }

    /**
     * Get the array of requested includes
     *
     * @param array $except
     * @return array
     */
    public function getIncludes($except = [])
    {
        if(empty($except))
            return $this->fractal->getRequestedIncludes();

        return array_except($this->fractal->getRequestedIncludes(), $except);
    }

    /**
     * Get the per page count from the request, or the default
     *
     * @return int
     */
    public function getResourceCount()
    {
        $config = config('transformers.parameters.count');

        return $this->request->get($config['name'], $config['default']);
    }
}