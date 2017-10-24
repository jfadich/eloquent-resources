<?php

namespace jfadich\EloquentResources;

use Illuminate\Database\Eloquent\{
    Collection as EloquentCollection,
    Relations\Relation,
    Builder,
    Model
};

use jfadich\EloquentResources\{
    Contracts\Presentable,
    Exceptions\InvalidModelRelationException,
    Exceptions\InvalidResourceTypeException,
    Exceptions\MissingTransformerException,
    Exceptions\InvalidResourceException,
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
    Support\Collection as LaravelCollection,
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
        $this->fractal->setRecursionLimit(config('resources.parameters.includes.max'));

        if($serializer = config('resources.serializer'))
            $this->fractal->setSerializer(new $serializer);

        $includesName = config('resources.parameters.includes.name');
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
                public function transform($model)
                {
                    if(is_array($model))
                        return $model;

                    if($model instanceof Presentable)
                        return $this->prepModel($model);

                    if(method_exists($model, 'toArray'))
                        return $model->toArray();

                    return (array) $model;
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
     * @throws InvalidModelRelationException
     */
    public function getEagerLoad(Transformer $transformer, $model)
    {
        $eager = [];
        $includes = $this->getIncludes($transformer->getLazyIncludes());

        foreach($includes as $include) {
            $nested = collect(explode('.',$include));
            $params = $this->fractal->getIncludeParams($include);
            $order = $params->get(config('resources.parameters.sort.name'));

            if(!method_exists($model, $nested->first()) || $nested->isEmpty()) {
                throw new InvalidModelRelationException("'$include' cannot be eager loaded. If the include is not an Eloquent relation try adding it to the 'lazyIncludes' array on the transformer");
            }

            if(!$order) {
                $eager[] = $include;
                continue;
            }

            do {
                $nestedInclude = $nested->shift();

                $model = $model->{$nestedInclude}()->getRelated();
                $relatedTransformer = $this->getTransformer($model);
            } while($nested->isNotEmpty());

            $params = $relatedTransformer->parseParams($this->fractal->getIncludeParams($include));
            $order = $params['order'] ?? null;

            if (is_array($order)) {
                $eager[$include] = function($query) use($order) {
                    return $query->orderBy($order[0], $order[1]);
                };
            } else {
                $eager[] = $include;
            }
        }

        return $eager;
    }

    /**
     * Take an Eloquent query or collection, find the transformer then use fractal to build the resource array
     *
     * @param $collection
     * @param array $meta
     * @param Callable|array $callback
     * @return \League\Fractal\Scope
     * @throws InvalidResourceException
     */
    public function buildCollectionResource($collection, $meta = [], $callback = null)
    {
        if(empty($collection)) {
            return $this->fractal->createData(new Collection([], function() { } ));
        }

        list($collection, $callback) = $this->resolveCallback($collection, $callback);

        // If a query builder instance is given set the eager loads and paginate the data.
        if ($collection instanceof Builder || $collection instanceof Relation) {
            $config = config('resources.parameters');

            if($this->request->has($config['sort']['name'])) {
                $sort = explode('|', $this->request->get($config['sort']['name']));

                $column = !empty($sort[0]) ? $sort[0] : null;
                $order  = !empty($sort[1]) && in_array($sort[1], ['asc', 'desc']) ? $sort[1] : 'asc';

                if($column !== null) {
                    if($callback instanceof Transformer) {
                        // Use the transformer to parse any sort columns that don't match the DB schema
                        $params = $callback->parseParams(new ParamBag([
                            $config['sort']['name'] => [$column, $order]
                        ]));
                    } else {
                        $params = [ 'order' => [$column, $order] ];
                    }

                    $collection = $collection->orderBy($params['order'][0], $params['order'][1]);
                }
            }

            $count = $this->getResourceCount();

            if($count == 0) {
                $collection = $collection->get();
            } else {
                $collection = $collection->paginate($count);
            }
        }

        if(is_array($collection)) {
            $resources = $collection;
        } elseif(
            $collection instanceof EloquentCollection ||
            $collection instanceof LaravelCollection ||
            $collection instanceof LengthAwarePaginator) {
            $resources = $collection->all();
        } else {
            throw new InvalidResourceException('Resources must be an array or Laravel Collection');
        }

        $resource = new Collection($resources, $callback);

        if (!empty($meta)) {
            foreach ($meta as $k => $v) {
                $resource->setMetaValue($k, $v);
            }
        }

        if ($collection instanceof LengthAwarePaginator) {
            $collection->appends($this->request->except('page'));

            $resource->setPaginator(new IlluminatePaginatorAdapter($collection));
        }

        return $this->fractal->createData($resource);
    }

    /**
     * Take an Eloquent query or model, find the transformer then use fractal to build the resource
     *
     * @param $item
     * @param array $meta
     * @param Callable|array $callback
     * @return \League\Fractal\Scope
     */
    public function buildItemResource($item, $meta = [], $callback = null)
    {
        list($item, $callback) = $this->resolveCallback($item, $callback);

        if ($item instanceof Builder || $item instanceof Relation) {
            $item = $item->firstOrFail();
        }

        $resource = new Item($item, $callback);

        if (!empty($meta)) {
            foreach ($meta as $k => $v) {
                $resource->setMetaValue($k, $v);
            }
        }

        return $this->fractal->createData($resource);
    }

    /**
     * Get the callback for the given model or array of models
     *
     * @param $resource
     * @param null $callback
     * @return array
     * @throws InvalidResourceException
     * @throws MissingTransformerException
     */
    protected function resolveCallback($resource, $callback = null)
    {
        $isQuery = $resource instanceof Builder || $resource instanceof Relation;

        if ($isQuery) {
            $model = $resource->getModel();
        } else {
            if($resource instanceof Model)
                $model = $resource;
            elseif(is_array($resource) || $resource instanceof \ArrayAccess) {
                if(empty($resource))
                    return [$resource, function() {}];

                $model = $resource[0];
            } else
                throw new InvalidResourceException('Invalid resource provided');
        }

        if($callback === null || (!is_callable($callback) && !$callback instanceof Transformer)) {
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
                    $resource = $resource->load($eager);
                }
            }
        }

        if($callback === null || (!is_callable($callback) && !$callback instanceof Transformer)) {
            throw new MissingTransformerException('Resource callback not provided.');
        }

        return [$resource, $callback];
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
        $config = config('resources.parameters.count');
        $count  = $this->request->get($config['name'], $config['default']);

        return is_numeric($count) && $count > 0 ? $count : $config['default'];
    }
}
