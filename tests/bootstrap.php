<?php

namespace {

    use jfadich\JsonResponder\TransformationManager;

    require __DIR__ . '/../vendor/autoload.php';

    if( ! function_exists('config')) {
        function config($key = null) {
            $config = include __DIR__ . '/../config/config.php';
            $config = ['transformers' => $config];

            if($key !== null)
                return array_get($config, $key);
            //return $config->get($key);

            return $config;
        }
    }

    if( ! function_exists('app')) {
        function app($make) {
            static $manager;

            if($make === TransformationManager::class) {
                if ($manager === null) {
                    $config = config('transformers.namespaces');
                    $manager = new TransformationManager($config['models'], $config['transformers'], $config['presenters']);
                }

                return $manager;
            }

            return null;
        }
    }


}

namespace App {

    use App\Nested\NestedModel;
    use Illuminate\Database\Eloquent\Model;
    use jfadich\JsonResponder\Contracts\Transformable as TransformableContract;
    use jfadich\JsonResponder\Traits\Transformable;


    class TestModel extends Model implements TransformableContract{
        use Transformable;

        public function nestedModel()
        {
            return $this->hasOne(NestedModel::class);
        }
    }

}

namespace App\Nested {

    use Illuminate\Database\Eloquent\Model;
    use jfadich\JsonResponder\Contracts\Transformable as TransformableContract;
    use jfadich\JsonResponder\Traits\Transformable;

    class NestedModel extends Model implements TransformableContract{
        use Transformable;

        public function getConnection()
        {
            return new \Illuminate\Database\Connection(null);
        }
    }
}

namespace App\Transformers {
    use jfadich\JsonResponder\Transformer;

    class TestModelTransformer extends Transformer {
        protected $availableIncludes = ['nestedModel'];
    }
}

namespace App\Transformers\Nested {
    use jfadich\JsonResponder\Transformer;

    class NestedModelTransformer extends Transformer {

    }
}
