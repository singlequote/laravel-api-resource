
# Laravel API Resources made simple

This package helps developers efficiently manage resource transformations, handle relationships, and customize resource attributes, ensuring a clean and maintainable API development workflow. Whether you're building a new API or maintaining an existing one, laravel-api-resource enhances your ability to deliver robust and compliant JSON responses with minimal effort. 

This package generates a complete api set for your model. From controller to action to request to resource. All you have to do is edit the form requests. 

> Version > 2.1 adds the ability to pass additional attributes to the `relation` and `has`  method. See [the with method](https://github.com/singlequote/laravel-api-resource?tab=readme-ov-file#with). 

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
// using the Ziggy package
axios.get(route('api.users.index', {
	limit : 100
}))
```
will look like
```
GET: <your_site_url>/api/users?limit=100
```
Manual
```javascript
axios.get('/api/users?limit=100')
```

| helper | value |
|:--------:| -------------:|
| limit | number |
| search| array |
| where| array |
| orWhere| array |
| whereIn | array |
| whereNotIn | array |
| whereNull | string |
| whereNotNull | string |
| has | array |
| doesntHave | array |
| whereRelation | array |
| with | array|
| select | array |
| orderBy | string |
| orderByDesc | string |

## limit
The default limit provided by the package is set to `1000` results per page. You can change the default in the `laravel-api-resource` config file. To change the limit for a single request you can use the `limit` helper.
```javascript
axios.get(route('api.users.index', {
    limit : 100
}))
```

## search
A search helper is available if you want to create a search input.  The search field accepts an array with 2 required fields. The field and query. The fields are the columns the api should search in. The query is the query used to search in the columns. 
```javascript
axios.get(route('api.users.index', {
	search: {
        fields: ['name', 'email'],
        query: "john"
    }
}))
// /api/users?search[fields][0]=name&search[fields][1]=email&search[query]=john
```
If you want to search all fillable columns within your model you can use the wildcard `*` as your searchfield
```javascript
axios.get(route('api.users.index', {
	search: {
        fields: ['*'], // search in all fillable columns
        query: "john"
    }
}))
// /api/users?search[fields][0]=*&search[query]=john
```


## where
You may use the query builder's `where` method to add "where" clauses to the query. The most basic call to the `where` method requires 2 arguments. The first argument is the name of the column. The second argument is the value to compare against the column's value.
```javascript
axios.get(route('api.users.index', {
     where: {
        first_name: "john"
    }
}))
// /api/users?where[first_name]=john
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
// /api/users?where[date_of_birth][gt]=1995-01-31
```
**Digging Deeper**

If you want to filter multiple times on the same column, you can change the `where` object to an array and add the `where` statements as array objects. 

Below we will retrieve all users that were created on a specific day
```javascript
axios.get(route('api.users.index', {
     where: [
	      {
	          created_at: {
	              lte: "1995-01-31 23:59"
	          }
	      }, {
	          created_at: {
	              gte: "1995-01-31 00:00"
	          }
	      }
	  ],
}))
// /api/users?where[0][date_of_birth][lte]=1995-01-31 23:59&where[1][date_of_birth][gte]=1995-01-31 00:00
```

**Available operators**
| Operator | Shorthand |
|:--------:| -------------:|
| startsWith | sw |
| endsWith| ew |
| notContains| nin |
| contains| in |
| equals | eq |
| notEqual | neq |
| greater | gt |
| greaterEquals | gte |
| lesser | lt |
| lesserEquals | lte |


## whereIn
The `whereIn` method verifies that a given column's value is contained within the given array:
```javascript
axios.get(route('api.users.index', {
	whereIn: {
        role: ['admin', 'employee']
    }
}))
// /api/users?whereIn[role][0]=admin&whereIn[role][1]=employee
```
## whereNotIn
The `whereNotIn` method verifies that the given column's value is not contained in the given array
```javascript
axios.get(route('api.users.index', {
	whereNotIn: {
        role: ['quests', 'visitors']
    }
}))
// /api/users?whereNotIn[role][0]=quests&whereNotIn[role][1]=visitors
```
## whereNull
The `whereNull` method verifies that the given column's value is `NULL`
```javascript
axios.get(route('api.users.index', {
    whereNull: "email_verified_at"
}))
// /api/users?whereNull[0]=email_verified_at
```
## whereNotNull
The `whereNotNull` method verifies that the given column's value is not `NULL`
```javascript
axios.get(route('api.users.index', {
    whereNotNull: "password"
}))
// /api/users?whereNotNull[0]=password
```
## has
When retrieving model records, you may wish to limit your results based on the existence of a relationship. For example, imagine you want to retrieve all users that have at least one role.
```javascript
axios.get(route('api.users.index', {
    has:  ['roles']
}))
// /api/users?has[0]=roles
```

**Digging deeper**

You can add additional parameters to the `has` object. For example, if you would like to get all users that with certain roles.
```javascript
axios.get(route('api.users.index', {
    has: {
	roles: {
            whereIn: {
                id: [1, 2]
            }
	}
    }
}))
// /api/users?has[roles][whereIn][id][0]=1&has[roles][whereIn][id][1]=2
```

## doesntHave
When retrieving model records, you may wish to limit your results based on the existence of a relationship. For example, imagine you want to retrieve all users don't have any roles attached.
```javascript
axios.get(route('api.users.index', {
    doesntHave:  ['roles']
}))
// /api/users?doesntHave[0]=roles
```
## whereRelation
If you would like to query for a relationship's existence with a single, simple where condition attached to the relationship query.
```javascript
axios.get(route('api.users.index', {
    whereRelation:  {
        roles: {
            name: 'admin',
        }
    }
}))
// /api/users?whereRelation[roles][name]=admin
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
// /api/users?whereRelation[roles][created_at][gt]=2024-01-01
```



## with
Sometimes you may need to eager load several different relationships. To do so, just pass an array of relationships to the `with` method
```javascript
axios.get(route('api.users.index', {
    with: ['roles']
}))
// /api/users?with[0]=roles
```
**Using `with` on sub relations**
When you want to retrieve a relation containing other relations you can set a property on each model. This allows you to allow certain models to accept multiple depths
Add the property `$apiRelations` to your model.

```php

class User extends Authenticatable
{
    public array $apiRelations = [
        'roles.permissions', //allows for users.roles.permissions
    ];
```
**Passing additional properties to relations**
The `with` method also accepts a json object. This way you can pass the same methods to relations.
For example, if you would like to retrieve only the name of the roles
```javascript
axios.get(route('api.users.index', {
    with: {
	    invitations: true,
	    roles: {
		    select: ['name']
	    }
	}
}))
// /api/users?with[invitations]=1&with[roles][roles][select][0][name]
```
Even include the permissions
```javascript
axios.get(route('api.users.index', {
    with: {
	    invitations: true,
	    roles: {
		    select: ['name'],
		    with: {
			    permissions: {
				    select: ['name'],
			    }
		    }
	    }
	}
}))
```

## select
Sometimes you may only need a few columns from the resource and keep your api responses small.
```javascript
axios.get(route('api.users.index', {
    select: ['id', 'name']
}))
```
## orderBy/orderByDesc
Sometimes you may want to change the ordering form your api response. You can use the `orderBy` or `orderByDesc` helper
```javascript
axios.get(route('api.users.index', {
    orderBy: 'name'
}))
// /api/users?orderBy=name
```
**Order relations**
```javascript
axios.get(route('api.users.index', {
    orderBy: 'roles.name'
}))
// /api/users?orderBy=roles.name
```

## Custom orderable columns
When using for example the `withCount` or `withSum` on your model query by default that column isn't sortable because it doesn't exists in your fillable attribute. To make the custom column sortable you can add the `$apiOrderBy` attribute to your model to make those columns sortable

To make for example article prices sortable, add the `withSum` to your query.

```php
$query->withSum('articles', 'price');
```

```php
class Product extends Model
{
    /**
     * @var array
     */
    public array $apiOrderBy = [
        'articles_sum_price',
    ];
```

next add the orderBy to your api request
```url
.../products?orderBy=articles_sum_price
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
