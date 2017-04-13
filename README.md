# json-responder
This trait adds the ability to easily return JSON responses from Controllers, Middleware or Exception Handlers.

## Installation

    composer require jfadich/json-responder

Add the service provider to the `$providers` array in `app.php`

    jfadich\EloquentResources\Providers\LaravelServiceProvider::class
    
If you wish to make and customizations publish the config config to `config/transformers.php` with this command.

    $ php artisan vendor:publish --provider="jfadich\EloquentResources\Providers\LaravelServiceProvider"
    
## Transformers

The transformers are an extension of [thephpleague/Fractal](http://fractal.thephpleague.com/). 
This package provides a command to generate new transformers and locate the correct transformer for a model. 
Transformers found from the model by looking for the class `App\Transformers\{ModelName}Transformer`. 
You can customize the transformers namespace to use in `config/transformers.php`. 

## Getting Started

Every model that has a transformer must implement the `jfadich\EloquentResources\Contracts\Transformable` contract.
The required methods can be included using the `jfadich\EloquentResources\Transformable` trait.
It's recommended to add this contract to a `BaseModel` that the rest of your transformable models extend.

    <?php
    
    namespace App;
    
    use jfadich\EloquentResources\Contracts\Transformable as TransformableContract;
    use jfadich\EloquentResources\Traits\Transformable;
    use Illuminate\Database\Eloquent\Model;
    
    class BaseModel extends Model implements TransformableContract
    {
        use Transformable;
    }
    
For the rest of these examples let's use a `Post` model.

    <?php
    namespace App;
    
    class Post extends BaseModel
    {
        public function author()
        {
            return $this->hasOne(Author::class);
        }
    }
    
### Making Transformers

To make a new transformer for `Post` execute the following command. 

    $ php artisan make:transformer PostTransformer --model=Post
    
This will generate a new model class in `app\Transformers\PostTransformer.php`.

    <?php
    
    namespace App\Transformers;
    
    use jfadich\EloquentResources\Transformer;
    use App\BlogPost;
    
    class PostTransformer extends Transformer
    {    
        public function transform(Post $post)
        {
            return [
                //
            ];
        }
    }
    
### Includes

See the Fractal docs on included data for the basics of how included data on transformers works. 
To make a property on the model available for inclusion add it to the `protected $available` property on your transformer.

    
    class PostTransformer extends Transformer
    {    
        protected $available = ['author']
    }
    
When using Fractal transformers you must define `includeAuthor()` method. This package will do this for you. 
It can automatically relieve any eloquent relationship and use the relevant transformer. 