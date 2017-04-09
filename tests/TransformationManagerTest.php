<?php

use jfadich\JsonResponder\TransformationManager;
use PHPUnit\Framework\TestCase;

class TransformationManagerTest extends TestCase
{
    public function test_resolve_resource_type_from_namespace()
    {
        $manager = new TransformationManager;

        $this->assertEquals('test_model', $manager->getResourceTypeFromClass(App\TestModel::class));
        $this->assertEquals('nested-nested_model', $manager->getResourceTypeFromClass(\App\Nested\NestedModel::class));
    }

    public function test_resolve_namespace_from_resource_type()
    {
        $manager = new TransformationManager;

        $this->assertEquals(\App\TestModel::class, $manager->getClassFromResourceType('test_model'));
        $this->assertEquals(\App\Nested\NestedModel::class, $manager->getClassFromResourceType('nested-nested_model'));
    }

    public function test_get_transformer_from_model()
    {
        $manager = new TransformationManager;

        $testModel = new \App\TestModel();
        $nestedModel = new \App\Nested\NestedModel();

        $this->assertTrue($manager->getTransformer($testModel) instanceof \App\Transformers\TestModelTransformer);
        $this->assertTrue($manager->getTransformer($nestedModel) instanceof \App\Transformers\Nested\NestedModelTransformer);
    }

    public function test_invalid_model_type()
    {
        $manager = new TransformationManager;

        $this->expectException(\jfadich\JsonResponder\Exceptions\InvalidResourceTypeException::class);

        $manager->getClassFromResourceType('not-a-resource');
    }
}