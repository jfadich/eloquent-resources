<?php

namespace App {

    use App\Nested\NestedModel;
    use jfadich\EloquentResources\TransformableModel;


    class TestModel extends TransformableModel {
        public function nestedModel()
        {
            return $this->hasOne(NestedModel::class);
        }
    }

}

namespace App\Nested {

    use jfadich\EloquentResources\TransformableModel;

    class NestedModel extends TransformableModel {

        public function getConnection()
        {
            return new \Illuminate\Database\Connection(null);
        }
    }
}

namespace App\Transformers {

    use jfadich\EloquentResources\Transformer;

    class TestModelTransformer extends Transformer {
        protected $availableIncludes = ['nestedModel'];

        public function transform()
        {
            return ['test' => 'transformed'];
        }
    }
}

namespace App\Transformers\Nested {

    use jfadich\EloquentResources\Transformer;

    class NestedModelTransformer extends Transformer {

    }
}
