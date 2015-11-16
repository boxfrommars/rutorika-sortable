[![Build Status](https://travis-ci.org/boxfrommars/rutorika-sortable.svg?branch=master)](https://travis-ci.org/boxfrommars/rutorika-sortable) [![Latest Stable Version](https://poser.pugx.org/rutorika/sortable/v/stable)](https://packagist.org/packages/rutorika/sortable) [![Total Downloads](https://poser.pugx.org/rutorika/sortable/downloads)](https://packagist.org/packages/rutorika/sortable) [![Latest Unstable Version](https://poser.pugx.org/rutorika/sortable/v/unstable)](https://packagist.org/packages/rutorika/sortable) [![License](https://poser.pugx.org/rutorika/sortable/license)](https://packagist.org/packages/rutorika/sortable)

## Laravel 5 - Demo

* http://sortable5.boxfrommars.ru/
* http://sortable5.boxfrommars.ru/grouped (sortable groups demo)
* http://sortable5.boxfrommars.ru/posts (many to many demo)

(demo repo code: https://github.com/boxfrommars/rutorika-sortable-demo5)

## Install

Install package through Composer

```bash
composer require rutorika/sortable
```
> Note: For **Laravel 4** please see the branch [1.2@laravel4](https://github.com/boxfrommars/rutorika-sortable/tree/laravel4)

## Sortable Trait 

Adds sortable behavior to Eloquent (Laravel) models

### Usage
Your model must have `position` field:

```php
// schema builder example
public function up()
{
    Schema::create('articles', function (Blueprint $table) {
        // ... other fields ...
        $table->integer('position'); // Your model must have position field:
    });
}
```

Add `\Rutorika\Sortable\SortableTrait` to your Eloquent model.

```php
class Article extends Model {

    use \Rutorika\Sortable\SortableTrait;
}
```

Now you can move your entities with methods `moveBefore($entity)` and `moveAfter($entity)` (you dont need to save 
model after that, it has saved already):

```php
$entity = Article::find(1);

$positionEntity = Article::find(10)

$entity->moveAfter($positionEntity);

// if $positionEntity->position is 14, then $entity->position is 15 now
```

Also this trait automatically defines entity position on the `create` event, so you do not need to add `position` manually, just create entities as usual:

```php
$article = new Article();
$article->title = $faker->sentence(2);
$article->description = $faker->paragraph();
$article->save();
```

This entity will be at position `entitiesMaximumPosition + 1`

To get ordered entities use the `sorted` scope:

```php
$articles = Article::sorted()->get();
```

> ** Note **: Resorting does not take place after a record is deleted. Gaps in positional values do not affect the ordering of your lists. However, if you prefer to prevent gaps you can reposition your models using the `deleting` event. Something like:

```php
// YourAppServiceProvider

YourModel::deleting(function ($model) {
    $model->next()->decrement('position');
});
```
 > You need rutorika-sortable >=2.3 to use `->next()`

### Sortable groups

if you want group entity ordering by field, add to your model

```php
protected static $sortableGroupField = 'fieldName';
```

now moving and ordering will be encapsulated by this field.

### Sortable many to many

Let's assume your database structure is

```
posts
    id
    title

tags
    id
    title

post_tag
    post_id
    tag_id
```

and you want to order *tags* for each *post*

Add `position` column to the pivot table (you can use any name you want, but `position` is used by default)

```
post_tag
    post_id
    tag_id
    position
```

Add `\Rutorika\Sortable\BelongsToSortedManyTrait` to your `Post` model and define `belongsToSortedMany` relation provided by this trait:

```php
class Post extends Model {

    use BelongsToSortedManyTrait;

    public function tags()
    {
        return $this->belongsToSortedMany('\App\Tag');
    }
}
```

> Note: `$this->belongsToSortedMany` has different signature then `$this->belongsToMany` -- the second argument for this method is `$orderColumn` (`'position'` by default), next arguments are the same

Attaching tags to post with `save`/`sync`/`attach` methods will set proper position

```php
    $post->tags()->save($tag) // or
    $post->tags()->attach($tag->id) // or
    $post->tags()->sync([$tagId1, $tagId2, /* ...tagIds */])
```

Getting related model is sorted by position

```php
$post->tags; // ordered by position by default
```

You can reorder tags for given post

```php
    $post->tags()->moveBefore($entityToMove, $whereToMoveEntity); // or
    $post->tags()->moveAfter($entityToMove, $whereToMoveEntity);
```


Many to many demo: http://sortable5.boxfrommars.ru/posts ([code](https://github.com/boxfrommars/rutorika-sortable-demo5))

## Sortable Controller

Also this package provides `\Rutorika\Sortable\SortableController`, which handle requests to sort entities

### Usage
Add the service provider to `config/app.php`

```php
'providers' => array(
    // providers...
    
    'Rutorika\Sortable\SortableServiceProvider',
)
```

publish the config:
 
```bash
php artisan vendor:publish
```

Add models you need to sort in the config `config/sortable.php`:

```php
'entities' => array(
     'articles' => '\App\Article', // entityNameForUseInRequest => ModelName
     // or
     'articles' => ['entity' => '\App\Article'],
     // or for many to many
     'posts' => [
        'entity' => '\App\Post',
        'relation' => 'tags' // relation name (method name which returns $this->belongsToSortedMany)
     ]
),
```

Add route to the `sort` method of the controller:

```php
Route::post('sort', '\Rutorika\Sortable\SortableController@sort'); 
```

Now if you post to this route valid data:

```php
$validator = \Validator::make(\Input::all(), array(
    'type' => array('required', 'in:moveAfter,moveBefore'), // type of move, moveAfter or moveBefore
    'entityName' => array('required', 'in:' . implode(',', array_keys($sortableEntities))), // entity name, 'articles' in this example
    'positionEntityId' => 'required|numeric', // id of relative entity
    'id' => 'required|numeric', // entity id
));

// or for many to many

$validator = \Validator::make(\Input::all(), array(
    'type' => array('required', 'in:moveAfter,moveBefore'), // type of move, moveAfter or moveBefore
    'entityName' => array('required', 'in:' . implode(',', array_keys($sortableEntities))), // entity name, 'articles' in this example
    'positionEntityId' => 'required|numeric', // id of relative entity
    'id' => 'required|numeric', // entity id
    'parentId' => 'required|numeric', // parent entity id
));

```

Then entity with `\Input::get('id')` id will be moved relative by entity with `\Input::get('positionEntityId')` id.

For example, if request data is:

```
type:moveAfter
entityName:articles
id:3
positionEntityId:14
```
then the article with id 3 will be moved after the article with id 14. 

### jQuery UI sortable example

> Note: Laravel 5 has csrf middleware enabled by default, so you should setup ajax requests: http://laravel.com/docs/5.0/routing#csrf-protection

Template

```html
<table class="table table-striped table-hover">
    <tbody class="sortable" data-entityname="articles">
    @foreach ($articles as $article)
    <tr data-itemId="{{{ $article->id }}}">
        <td class="sortable-handle"><span class="glyphicon glyphicon-sort"></span></td>
        <td class="id-column">{{{ $article->id }}}</td>
        <td>{{{ $article->title }}}</td>
    </tr>
    @endforeach
    </tbody>
</table>
```

Template for many to many ordering

```html
<table class="table table-striped table-hover">
    <tbody class="sortable" data-entityname="posts">
    @foreach ($post->tags as $tag)
    <tr data-itemId="{{ $tag->id }}" data-parentId="{{ $post->id }}">
        <td class="sortable-handle"><span class="glyphicon glyphicon-sort"></span></td>
        <td class="id-column">{{ $tag->id }}</td>
        <td>{{ $tag->title }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
```


```js
    /**
     *
     * @param type string 'insertAfter' or 'insertBefore'
     * @param entityName
     * @param id
     * @param positionId
     */
    var changePosition = function(requestData){
        $.ajax({
            'url': '/sort',
            'type': 'POST',
            'data': requestData,
            'success': function(data) {
                if (data.success) {
                    console.log('Saved!');
                } else {
                    console.error(data.errors);
                }
            },
            'error': function(){
                console.error('Something wrong!');
            }
        });
    };

    $(document).ready(function(){
        var $sortableTable = $('.sortable');
        if ($sortableTable.length > 0) {
            $sortableTable.sortable({
                handle: '.sortable-handle',
                axis: 'y',
                update: function(a, b){

                    var entityName = $(this).data('entityname');
                    var $sorted = b.item;

                    var $previous = $sorted.prev();
                    var $next = $sorted.next();

                    if ($previous.length > 0) {
                        changePosition({
                            parentId: $sorted.data('parentid'),
                            type: 'moveAfter',
                            entityName: entityName,
                            id: $sorted.data('itemid'),
                            positionEntityId: $previous.data('itemid')
                        });
                    } else if ($next.length > 0) {
                        changePosition({
                            parentId: $sorted.data('parentid'),
                            type: 'moveBefore',
                            entityName: entityName,
                            id: $sorted.data('itemid'),
                            positionEntityId: $next.data('itemid')
                        });
                    } else {
                        console.error('Something wrong!');
                    }
                },
                cursor: "move"
            });
        }
    });
```

