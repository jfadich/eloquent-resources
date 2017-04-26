<?php

use jfadich\EloquentResources\ResourceManager;
use PHPUnit\Framework\TestCase;

class ResourceManagerTest extends TestCase
{
    public function test_resolve_resource_type_from_namespace()
    {
        $manager = new ResourceManager;

        $this->assertEquals('test_model', $manager->getResourceTypeFromClass(App\TestModel::class));
        $this->assertEquals('nested-nested_model', $manager->getResourceTypeFromClass(\App\Nested\NestedModel::class));
    }

    public function test_resolve_namespace_from_resource_type()
    {
        $manager = new ResourceManager;

        $this->assertEquals(\App\TestModel::class, $manager->getClassFromResourceType('test_model'));
        $this->assertEquals(\App\Nested\NestedModel::class, $manager->getClassFromResourceType('nested-nested_model'));
    }

    public function test_get_transformer_from_model()
    {
        $manager = new ResourceManager;

        $testModel = new \App\TestModel();
        $nestedModel = new \App\Nested\NestedModel();

        $this->assertTrue($manager->getTransformer($testModel) instanceof \App\Transformers\TestModelTransformer);
        $this->assertTrue($manager->getTransformer($nestedModel) instanceof \App\Transformers\Nested\NestedModelTransformer);
    }

    public function test_invalid_model_type()
    {
        $manager = new ResourceManager;

        $this->expectException(\jfadich\EloquentResources\Exceptions\InvalidResourceTypeException::class);

        $manager->getClassFromResourceType('not-a-resource');
    }
}