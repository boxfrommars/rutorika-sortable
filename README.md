## Demo

http://sortable-demo.boxfrommars.ru/

(demo repo code: https://github.com/boxfrommars/rutorika-sortable-demo)

## Install

Install package through Composer

```bash
require: {
    "rutorika/sortable": "dev-master"
}
```

## Sortable Trait 

Adds sortable behavior to Eloquent (Laravel) models

### Usage

Add `\Rutorika\Sortable\SortableTrait` to your Eloquent model. Your model must have `position` field:

```php
class Article extends Eloquent {

    use \Rutorika\Sortable\SortableTrait;
}
```

Now you can move your entities with methods `moveBefore($entity)` and `moveAfter($entity)`:

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

## Sortable Controller

Also this package provides `\Rutorika\Sortable\SortableController`, which handle requests to sort entities

### Usage
Add the service provider to `app/config/app.php`

```php
'providers' => array(
    // providers...
    
    'Rutorika\Sortable\SortableServiceProvider',
)
```

publish the config:
 
```bash
php artisan config:publish rutorika/sortable
```

Add models you need to sort in the config `app/config/packages/rutorika/sortable/config.php`:

```php
'entities' => array(
     'articles' => '\Article', // entityNameForUseInRequest => ModelName
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



