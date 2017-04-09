<?php

namespace {
    require __DIR__ . '/../vendor/autoload.php';

    function config($key = null) {
        $config = include __DIR__ . '/../config/config.php';
        $config = ['transformers' => $config];

        if($key !== null)
            return array_get($config, $key);
            //return $config->get($key);

        return $config;
    }

}

namespace App {
    use jfadich\JsonResponder\Contracts\Transformable as TransformableContract;
    use jfadich\JsonResponder\Traits\Transformable;


    class TestModel implements TransformableContract{
        use Transformable;
    }

}

namespace App\Nested {
    use jfadich\JsonResponder\Contracts\Transformable as TransformableContract;
    use jfadich\JsonResponder\Traits\Transformable;

    class NestedModel implements TransformableContract{
        use Transformable;
    }
}

namespace App\Transformers {
    use jfadich\JsonResponder\Transformer;

    class TestModelTransformer extends Transformer {
    }
}

namespace App\Transformers\Nested {
    use jfadich\JsonResponder\Transformer;

    class NestedModelTransformer extends Transformer {

    }
}
