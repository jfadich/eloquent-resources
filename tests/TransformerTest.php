<?php

use League\Fractal\Resource\Item;
use PHPUnit\Framework\TestCase;

class TransformerTest extends TestCase
{
    public function test_includes()
    {
        $model              = new \App\TestModel();
        $model->nestedModel = new \App\Nested\NestedModel();
        $transformer        = new \App\Transformers\TestModelTransformer();

        $included           = $transformer->includeNestedModel($model);

        $this->assertTrue($included instanceof Item);
        $this->assertEquals($model->nestedModel, $included->getData());
    }
}