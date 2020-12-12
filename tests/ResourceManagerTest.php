<?php

use jfadich\EloquentResources\ResourceManager;
use App\Transformers\TestModelTransformer;
use PHPUnit\Framework\TestCase;
use App\TestModel;

class ResourceManagerTest extends TestCase
{
    public function test_resolve_resource_type_from_namespace()
    {
        $manager = new ResourceManager(New League\Fractal\Manager(), new \Illuminate\Http\Request());

        $this->assertEquals('test_model', $manager->getResourceTypeFromClass(App\TestModel::class));
        $this->assertEquals('nested-nested_model', $manager->getResourceTypeFromClass(\App\Nested\NestedModel::class));
    }

    public function test_resolve_namespace_from_resource_type()
    {
        $manager = new ResourceManager(New League\Fractal\Manager(), new \Illuminate\Http\Request());

        $this->assertEquals(TestModel::class, $manager->getClassFromResourceType('test_model'));
        $this->assertEquals(\App\Nested\NestedModel::class, $manager->getClassFromResourceType('nested-nested_model'));
    }

    public function test_get_transformer_from_model()
    {
        $manager = new ResourceManager(New League\Fractal\Manager(), new \Illuminate\Http\Request());

        $testModel = new TestModel();
        $nestedModel = new \App\Nested\NestedModel();

        $this->assertTrue($manager->getTransformer($testModel) instanceof TestModelTransformer);
        $this->assertTrue($manager->getTransformer($nestedModel) instanceof \App\Transformers\Nested\NestedModelTransformer);
    }

    public function test_invalid_model_type()
    {
        $manager = new ResourceManager(New League\Fractal\Manager(), new \Illuminate\Http\Request());

        $this->expectException(\jfadich\EloquentResources\Exceptions\InvalidResourceTypeException::class);

        $manager->getClassFromResourceType('not-a-resource');
    }

    public function test_parse_includes()
    {
        $request = new \Illuminate\Http\Request();
        $request->replace(['with'   => 'nestedModel']);

        $manager = new ResourceManager(New League\Fractal\Manager(), $request);
        $transformer = new TestModelTransformer;
        $model = new TestModel;

        $this->assertEquals($manager->getEagerLoad($transformer, $model), ['nestedModel']);

        $request->replace(['with'   => 'invalidRelationship']);
        $manager = new ResourceManager(New League\Fractal\Manager(), $request);
        $this->expectException(\jfadich\EloquentResources\Exceptions\InvalidModelRelationException::class);
        $manager->getEagerLoad($transformer, $model);

        $request->replace(['with'   => 'nestedModel:sort(created|desc)']);
        $manager = new ResourceManager(New League\Fractal\Manager(), $request);

        $this->assertEquals($manager->getEagerLoad($transformer, $model), ['nestedModel' => function() { }]);
    }

    public function test_make_item()
    {
        $manager = new ResourceManager(New League\Fractal\Manager(), new \Illuminate\Http\Request());
        $item = new TestModel;

        $resource = $manager->buildItemResource($item);
        $this->assertEquals(['data' => ['test' => 'transformed']], $resource->toArray());

    }

    public function test_make_collection()
    {
        $manager = new ResourceManager(New League\Fractal\Manager(), new \Illuminate\Http\Request());
        $collection = new \Illuminate\Database\Eloquent\Collection([new TestModel]);
        $emptyCollection = new \Illuminate\Database\Eloquent\Collection();

        $resource = $manager->buildCollectionResource($collection);
        $this->assertEquals(['data' => [['test' => 'transformed']]], $resource->toArray());

        $resource = $manager->buildCollectionResource($emptyCollection);
        $this->assertEquals(['data' => []], $resource->toArray());

        $resource = $manager->buildCollectionResource([]);
        $this->assertEquals(['data' => []], $resource->toArray());

        $resource = $manager->buildCollectionResource($collection, ['key' => 'meta value']);
        $this->assertEquals([
            'data' => [['test' => 'transformed']],
            'meta' => ['key' => 'meta value']
        ], $resource->toArray());

        $resource = $manager->buildCollectionResource($collection, [], function() {
            return ['test' => 'anonymously transformed'];
        });
        $this->assertEquals(['data' => [['test' => 'anonymously transformed']]], $resource->toArray());
    }

    public function test_make_collection_from_query()
    {
        $request = new \Illuminate\Http\Request();
        $request->initialize(['limit' => 0]);

        $manager = new ResourceManager(New League\Fractal\Manager(), $request);
        $model = $this->getMockBuilder(\Illuminate\Database\Eloquent\Builder::class)
            ->disableOriginalConstructor()
            ->setMethods(['getModel', 'get'])
            ->getMock();

        $model->method('getModel')->willReturn(new TestModel());
        $model->method('get')->willReturn(new \Illuminate\Support\Collection([new TestModel()]));

        $resource = $manager->buildCollectionResource($model);

        $this->assertEquals(['data' => [['test' => 'transformed']]], $resource->toArray());
    }
}