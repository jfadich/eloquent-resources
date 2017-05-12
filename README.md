# Eloquent Resources
Eloquent resources provides Laravel integration for the [thephpleague/Fractal](http://fractal.thephpleague.com/) package.

Fractal utilizes transformers to add a layer between your database schema and your API responses. Each model should have 
a transformer to define the how it should be represented in your API.

Eloquent resources integrates Fractal wil Laravel and adds a few helper methods to help transform models. It also  adds 
the artisan command to quickly generate new transformers. When nested relationships are requested to be included 
It can also automatically eager load fractal includes and parse the parameters to apply the proper `sort` and `order` 
query constraints. 
 
Fractal can be verbose. This is the code to create transformed data using an eloquent model and a fractal transformer.
 
     public function lastesPost()
     {
        $fractal = new League\Fractal\Manager();
        $post = App\Post::latest()->first();
        $transformer = new App\Transformers\PostTransformer();
        $resource = League\Fractal\Resource\Item($post, $transformers);
    
        $data = $fractal->createData($resource, $transformer)->toArray();
     
        return response()->json($data);
     }

Eloquent Resources will handle the Fractal manager for you. 

    public function lastesPost()
    {
        $post = Post::latest()->first();

        return $this->respondWithItem($post);
    }
    
## Installation
Install the package using composer.

    $ composer require jfadich/eloquent-resources

Add the service provider to the `$providers` array in `app.php`

    jfadich\EloquentResources\Providers\LaravelServiceProvider::class
    
If you wish to make and customizations publish the config config to `config/eloquent-resources.php` with this command.

    $ php artisan vendor:publish --provider="jfadich\EloquentResources\Providers\LaravelServiceProvider"
    
### Add Eloquent Resources to your controller
Update your base controller in simply add the `RespondsWithResources` trait to your base controller 
or any controller that you with to use Eloquent Resources.

    <?php
    
    namespace App\Http\Controllers;
    
    use Illuminate\Routing\Controller as BaseController;
    use jfadich\EloquentResources\Traits\RespondsWithResources;
    
    class Controller extends BaseController
    {
        use RespondsWithResources;
    }
    
##Using Eloquent Resources

Eloquent resources provides the `respondWithItem()` and `respondWithCollection()` to easily transform a model or 
collection of models. Simply provide the model and a callback. The callback will be given to 
[Fractal](http://fractal.thephpleague.com/) to create the resources and then return a JsonResponse.

    public function index()
    {
        return $this->respondWithCollection(Post::all(), function($post) {
            return [
                'title'     => $post->title,
                'body'      => $post->body,
                'created'   => $post->created_at->getTimestamp(),
                'updated'   => $post->updated_at->getTimestamp()
            ];
        })
    }

The above example will produce the following json.

    {
        "data": [
            {
                "title": "Example Post",
                "body": "This is the post body.",
                "created": 1493604602,
                "updated": 1493604602
            }
        ]
    }

## Transformers

Using an anonymous function like the above example can quickly get redundant. It is recommended that you use a transformer
to make this code reusable.

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
    
If you pass a query builder object from your controller, it can also sort, paginate the results. Eloquent Resources will
automatically grab the corresponding callback for the model.

    // This can be any query builder method like `Post::where('published', true);` or scope like `Post::latest()`
    $posts = App\Post::query();
    
    return $this->respondWithCollection($posts);
    
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
To make a property on the model available for inclusion add it to the `$availableIncludes` property on your transformer.

    
    class PostTransformer extends Transformer
    {    
        protected $availableIncludes = ['author']
    }
    
When using Fractal transformers you must define `includeAuthor()` method. This package will do this for you. 
It can automatically retrieve any eloquent relationship and use the relevant transformer. 