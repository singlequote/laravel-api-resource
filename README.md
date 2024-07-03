
# Laravel API Resource Generator

This package contains a wide array of standard options for your API resources. Additionally, it generates a complete API resource based on your model. The package follows the default laravel folder/file structures. 

[![Latest Version on Packagist](https://img.shields.io/packagist/v/singlequote/laravel-api-resource.svg?style=flat-square)](https://packagist.org/packages/singlequote/laravel-api-resource)
[![Total Downloads](https://img.shields.io/packagist/dt/singlequote/laravel-api-resource.svg?style=flat-square)](https://packagist.org/packages/singlequote/laravel-api-resource)

## Installation

```bash
composer require singlequote/laravel-api-resource
```

## Publish files
Publish the config
```bash
php artisan vendor:publish --tag=laravel-api-resource-config
```
Publish stub files
```bash
php artisan vendor:publish --tag=laravel-api-resource-stubs
```

## API Resource Generation

```bash
php artisan make:api-resource {model}
```

With the command above, you can generate a complete set for your API resources including the API controller, actions, requests, and an API resource.

For example, if we would want to generate an API resource for our `User` model.

```bash
php artisan make:api-resource User
```

The following files will be created:

```plaintext
App/Http/Controllers
    - ApiUserController

App/Actions/Users
    - DeleteUserAction
    - IndexUsersAction
    - ShowUserAction
    - StoreUserAction
    - UpdateUserAction

App/Http/Requests/Users
    - IndexUserRequest
    - ShowUserRequest
    - StoreUserRequest
    - UpdateUserRequest

App/Http/Resources
    - UserResource
```
After the generation is completed you can add your api resource route to your `api.php` route file.
```php
/*
  |--------------------------------------------------------------------------
  | User routes
  |--------------------------------------------------------------------------
 */
Route::apiResource('users', UserController::class)->only('index', 'store', 'show', 'update', 'destroy');
```
## Resource methods
The package comes with default methods that can be used to quickly setup your api. For instance the policy service can be used to add policies to your resource response.
```php
   use SingleQuote\LaravelApiResource\Service\ApiPolicyService;

    public function toArray(Request $request): array
    {
        return [
            // ...
            'policies' => ApiPolicyService::defaults($this->resource),
        ];
    }
```

In addition you can pass additional policy methods as a second parameter.

```php
'policies' => ApiPolicyService::defaults($this->resource, ['sendInvite', 'acceptInvite']),
```

## Api methods
The package comes with default api options. To use the provided helpers, add the `HasApi` trait to your models. 
In the code previews below we use a package named [Ziggy](https://github.com/tighten/ziggy) to parse the url. If you don't use any javascript libraries to build your url you have to manual build the url according to the previews below.
For example: 
```javascript
axios.get(route('api.users.index', {
	limit : 100
}))
```
will look like
```
GET: <your_site_url>/api/users?limit=100
```

| helper | value |
|:--------:| -------------:|
| limit | number |
| search| array |
| where| array |
| orWhere| array |
| whereIn | array |
| whereNotIn | array |
| whereNotNull | string |
| has | array |
| whereRelation | array |
| with | array |
| select | array |
| orderBy | string |
| orderByDesc | string |

**limit**
The default limit provided by the package is set to `1000` results per page. You can change the default in the `laravel-api-resource` config file. To change the limit for a single request you can use the `limit` helper.
```javascript
axios.get(route('api.users.index', {
	limit : 100
}))
```

**search**
A search helper is available if you want to create a search input.  The search field accepts an array with 2 required fields. The field and query. The fields are the columns the api should search in. The query is the query used to search in the columns. 
```javascript
axios.get(route('api.users.index', {
	search: {
        fields: "name,email",
        query: "john"
    }
}))
```
**where**
You may use the query builder's `where` method to add "where" clauses to the query. The most basic call to the `where` method requires 2 arguments. The first argument is the name of the column. The second argument is the value to compare against the column's value.
```javascript
axios.get(route('api.users.index', {
     where: {
        first_name: "john"
    }
}))
```
You may also pass an additional operator to retrieve data for example, get all users younger than a certain date
```javascript
axios.get(route('api.users.index', {
     where: {
        date_of_birth: {
           gt: "1995-01-31"
        } 
    }
}))
```

**whereIn**
The `whereIn` method verifies that a given column's value is contained within the given array:
```javascript
axios.get(route('api.users.index', {
	whereIn: {
        role: ['admin', 'employee']
    }
}))
```
**whereNotIn**
The `whereNotIn` method verifies that the given column's value is not contained in the given array
```javascript
axios.get(route('api.users.index', {
	whereNotIn: {
        role: ['quests', 'visitors']
    }
}))
```
**whereNotNull**
The `whereNotNull` method verifies that the given column's value is not `NULL`
```javascript
axios.get(route('api.users.index', {
	whereNotNull: "password"
}))
```
**has**
When retrieving model records, you may wish to limit your results based on the existence of a relationship. For example, imagine you want to retrieve all users that have at least one role.
```javascript
axios.get(route('api.users.index', {
	has:  ['roles']
}))
```
**whereRelation**
If you would like to query for a relationship's existence with a single, simple where condition attached to the relationship query.
```javascript
axios.get(route('api.users.index', {
	whereRelation:  {
            roles: {
                name: 'admin',
            }
        }
}))
```

You may also pass an additional operator to retrieve data for example, get all users where the role was created after a certain date.
```javascript
axios.get(route('api.users.index', {
    whereRelation:  {
        roles: {
            created_at: {
                gt: "2024-01-01",
            },
        }
    }
}))
```



**with**
Sometimes you may need to eager load several different relationships. To do so, just pass an array of relationships to the `with` method
```javascript
axios.get(route('api.users.index', {
	with: ['roles']
}))
```
**Using `with` with depth**
When you want to retrieve a relation containing other relations you can set a property on each model. This allows you to allow certain models to accept multiple depths
Add the property `$apiRelations` to your model.

```php

class User extends Authenticatable
{
    public array $apiRelations = [
        'roles.permissions', //allows for users.roles.permissions
    ];
```

**select**
Sometimes you may only need a few columns from the resource and keep your api responses small.
```javascript
axios.get(route('api.users.index', {
	select: ['id', 'name']
}))
```
**orderBy/orderByDesc**
Sometimes you may want to change the ordering form your api response. You can use the `orderBy` or `orderByDesc` helper
```javascript
axios.get(route('api.users.index', {
	orderBy: 'name'
}))
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Postcardware

You're free to use this package, but if it makes it to your production environment, we highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Quotec, Traktieweg 8c 8304 BA, Emmeloord, Netherlands.

## Credits

- [Wim Pruiksma](https://github.com/wimurk)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
