# json-responder
This trait adds the ability to easily return JSON responses from Controllers, Middleware or Exception Handlers.

## Installation

    composer require jfadich/json-responder

Add the service provider to the `$providers` array in `app.php`

    jfadich\JsonResponder\Providers\LaravelServiceProvider::class
    
If you wish to make and customizations publish the config config to `config/transformers.php` with this command.

    $ php artisan vendor:publish --provider="jfadich\JsonResponder\Providers\LaravelServiceProvider"
    
## Transformers

The transformers are an extension of [thephpleague/Fractal](http://fractal.thephpleague.com/). 
This package provides a command to generate new transformers and locate the correct transformer for a model. 
Transformers found from the model by looking for the class `App\Transformers\{ModelName}Transformer`. 
You can customize the transformers namespace to use in `config/transformers.php`. 

### Making Transformers

To make a new transformer for `BlogPost` execute the following command. 

    $ php artisan make:transformer BlogPostTransformer --model=BlogPost
    
This will generate a new model class in `app\Transformers\BlogPostTransformer.php`.

    <?php
    
    namespace App\Transformers;
    
    use jfadich\JsonResponder\Transformer;
    use App\BlogPost;
    
    class BlogPostTransformer extends Transformer
    {    
        public function transform(BlogPost $blogPost)
        {
            return [
                //
            ];
        }
    }
    
Before this transformer can be use the `BlogPost` must be transformable. 
It is recommended to add the `Transformable` trait and contract to a base model that the rest of your models extend. 

    <?php
    
    namespace App;
    
    use jfadich\JsonResponder\Contracts\Transformable as TransformableContract;
    use jfadich\JsonResponder\Traits\Transformable;
    use Illuminate\Database\Eloquent\Model;
    
    class BaseModel extends Model implements TransformableContract
    {
        use Transformable;
    }
    
### Includes

See the Fractal docs on included data for the basics of how included data on transformers works. 
To make a property on the model available for inclusion add it to the `protected $available` property on your transformer.

    
    class BlogPostTransformer extends Transformer
    {    
        protected $available = ['author']
    }
    
When using Fractal transformers you must define `includeAuthor()` methods for all available includes. 
The transformers in this package will automatically define these methods for any Eloquent relationship.