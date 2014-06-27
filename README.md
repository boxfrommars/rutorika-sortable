Adds sortable behavior to Eloquent models

# Usage

Add `\Rutorika\Sortable\SortableTrait` to your Eloquent model. Your model must have `position` field.

```php
class Article extends Eloquent {

    use \Rutorika\Sortable\SortableTrait;
}
```

Now you can move your entities with methods `moveBefore($entity)` and `moveAfter($entity)`

```php
$entity = Article::find(1);

$positionEntity = Article::find(10)

$entity->moveAfter($positionEntity);

// if $positionEntity->position is 14, then $entity->position is 15 now
```

Also this trait automatically defines entity position on create event, so you shouldn't add `position` value by hands, just create it as usually 

```php
$article = new Article();
$article->title = $faker->sentence(2);
$article->description = $faker->paragraph();
$article->save();
```

This entity will have `entitiesMaximumPosition + 1` position;

To get ordered entities use `sorted` scope:

```php
$articles = Article::sorted()->get();
```



# Controller

Also this package provides `\Rutorika\Sortable\SortableController`, which handle requests to sort entities

## Usage

Add route to `sort` method of controller
```php
Route::post('sort', '\Rutorika\Sortable\SortableController@sort'); 
```
Now if you post to this route valid data like 

```php
$validator = \Validator::make(\Input::all(), array(
    'type' => array('required', 'in:moveAfter,moveBefore'), // type of move
    'table' => array('required', 'in:' . implode(',', array_keys($sortableTables))), // which entity 
    'positionEntityId' => 'required|numeric', // id of relative entity
    'id' => 'required|numeric', // entity id
));
```

Then entity with `\Input::get('id')` id will be moved relative by entity with `\Input::get('positionEntityId')` id.



