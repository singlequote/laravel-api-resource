# Laravel API Resource

[![Latest Version on Packagist](https://img.shields.io/packagist/v/singlequote/laravel-api-resource.svg?style=flat-square)](https://packagist.org/packages/singlequote/laravel-api-resource)
[![Total Downloads](https://img.shields.io/packagist/dt/singlequote/laravel-api-resource.svg?style=flat-square)](https://packagist.org/packages/singlequote/laravel-api-resource)

A practical Laravel package designed to streamline API development by automating resource generation and providing a powerful, out-of-the-box filtering system.

This package accelerates your workflow with two core features:
1.  **Rapid Scaffolding**: Use the `php artisan make:api-resource` command to intelligently generate a full set of API files (Controller, Actions, Requests, and Resource) from your existing models.
    Features:
    - Complete Scaffolding: Generates Controller, FormRequests, Actions, and API Resources.
    - Smart Relation Detection: Automatically distinguishes between "Single" relations (e.g., HasOne, BelongsTo) and "Collection" relations (e.g., HasMany, BelongsToMany).
    - Database Driven Validation: Generates validation rules based on your database columns (types, nullable, max length, etc.).
    - Advanced Overwriting: Supports force overwriting and selective generation.
    
    Route Suggestions: Provides the correct API route definition upon completion.
2.  **Powerful Filtering**: Equip your API endpoints with a comprehensive set of filters from the moment you install it. Sort, search, and filter resources with ease without any initial setup.

---

## Table of Contents

- [Installation & Setup](#installation--setup)
- [Usage](#usage)
  - [1. Generating a Full API Resource](#1-generating-a-full-api-resource)
  - [2. Using the API Filters](#2-using-the-api-filters)
  - [3. Customizing the Resource Response](#3-customizing-the-resource-response)
- [API Filtering Reference](#api-filtering-reference)
  - [limit](#limit)
  - [search](#search)
  - [where](#where)
  - [whereIn / whereNotIn](#wherein--wherenotin)
  - [whereNull / whereNotNull](#wherenull--wherenotnull)
  - [has / doesntHave](#has--doesnothave)
  - [whereRelation](#whererelation)
  - [with](#with)
  - [withCount](#withcount)
  - [select](#select)
  - [orderBy / orderByDesc](#orderby--orderbydesc)
  - [Custom Orderable Columns](#custom-orderable-columns)
- [Contributing](#contributing)
- [Postcardware](#postcardware)
- [Credits](#credits)
- [License](#license)

---

## Installation & Setup

**1. Install the package via Composer:**

```bash
composer require singlequote/laravel-api-resource
```

**2. (Optional) Publish Files:**

You can publish the configuration and stub files to customize the package's behavior and generated file templates.

Publish the config file:
```bash
php artisan vendor:publish --tag=laravel-api-resource-config
```

Publish the stub files:
```bash
php artisan vendor:publish --tag=laravel-api-resource-stubs
```

---

## Usage

### 1. Generating a Full API Resource

Use the `make:api-resource` Artisan command to generate all the necessary files for a model's API endpoint.

```bash
php artisan make:api-resource User
```

### Command Options
The command supports several options to customize the generation process:
| Option | Description |
| :--- | :--- |
| `--force` | Overwrite existing files without asking for confirmation. Useful for CI/CD or quick regeneration. |
| `--only`| Generate only specific parts. Comma separated. Available: `controller`, `actions`, `requests`, `resource`. |
| `--except`| Exclude specific parts from generation. Comma separated. |
| `--module`| Specify a module name if you are using a modular application structure. |

**Example:**
```bash
php artisan make:api-resource User --force
```
```bash
php artisan make:api-resource User --only=resource,controller
```
```bash
php artisan make:api-resource User --except=requests
```

## Supported Relations
The generator recognizes a wide range of Eloquent relationships and generates the appropriate code (e.g., new UserResource vs UserResource::collection):

**Single Relations (Returns Object):**
- HasOne
- MorphOne
- BelongsTo
- MorphTo
- HasOneThrough

**Collection Relations (Returns Array):**
- HasMany
- BelongsToMany
- MorphToMany
- MorphMany
- HasManyThrough
- MorphedByMany

## Controlling Generation
You can control which relations are included in the generated API using the `#[SkipApiGeneration]` attribute on your model methods. This attribute accepts an array of scopes to skip:

- `SkipApiGeneration::ALL` (default): Skips generation for everything.
- `SkipApiGeneration::ACTIONS`: Skips generation in Store/Update actions (making the relation read-only).
- `SkipApiGeneration::REQUESTS`: Skips validation rules in Requests.
- `SkipApiGeneration::RESOURCE`: Skips inclusion in the API Resource.

**Example**
```php
use SingleQuote\LaravelApiResource\Attributes\SkipApiGeneration;

class User extends Model
{
    // Completely ignore this relation
    #[SkipApiGeneration]
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Read-only: visible in resource, but not updateable via API
    #[SkipApiGeneration([SkipApiGeneration::ACTIONS, SkipApiGeneration::REQUESTS])]
    public function logs()
    {
        return $this->hasMany(Log::class);
    }
}
```

This single command creates the following file structure, ready for you to add your business logic:

```plaintext
App/Http/Controllers
└── Api/UserController.php

App/Actions/Users
├── DeleteUserAction.php
├── IndexUserAction.php
├── ShowUserAction.php
├── StoreUserAction.php
└── UpdateUserAction.php

App/Http/Requests/Users
├── IndexUserRequest.php
├── ShowUserRequest.php
├── StoreUserRequest.php
└── UpdateUserRequest.php

App/Http/Resources
└── UserResource.php
```

Finally, add the generated route to your `routes/api.php` file:

```php
use App\Http\Controllers\Api\UserController;

Route::apiResource('users', UserController::class);
```

### 2. Using the API Filters

To enable the powerful filtering capabilities, simply add the `HasApi` trait to your model.

```php
use Illuminate\Database\Eloquent\Model;
use SingleQuote\LaravelApiResource\Traits\HasApi;

class User extends Model
{
    use HasApi;
    
    // ...
}
```

You can now use a wide range of query parameters to filter your API results directly from the URL. See the [API Filtering Reference](#api-filtering-reference) below for a full list of available methods.

### 3. Customizing the Resource Response

The package provides helpers to easily customize your JSON response. For instance, you can use the `ApiPolicyService` to automatically include the results of your model's policies.

In your `UserResource.php`:

```php
use SingleQuote\LaravelApiResource\Service\ApiPolicyService;
use Illuminate\Http\Request;

public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        // ...
        'policies' => ApiPolicyService::defaults($this->resource, ['sendInvite']),
    ];
}
```

---

## API Filtering Reference

All examples use the [Ziggy](https://github.com/tighten/ziggy) package for clean URL generation. A manual URL example is provided for reference.

| Helper | Value Type | Description |
| :--- | :--- | :--- |
| `limit` | `number` | Sets the number of results per page. |
| `search`| `array` | Searches specified columns for a query. |
| `where`| `array` | Adds a "where" clause to the query. |
| `orWhere`| `array` | Adds an "or where" clause. |
| `whereJsonContains` | `array` | Queries a relationship with a `where` condition. |
| `whereJsonDoesntContain` | `array` | Queries a json column with a `whereIn` condition. |
| `whereIn` | `array` | Filters by a column's value within an array. |
| `whereNotIn` | `array` | Filters by a column's value not in an array. |
| `whereNull` | `string` | Finds records where a column is NULL. |
| `whereNotNull` | `string` | Finds records where a column is not NULL. |
| `has` | `array` | Filters based on the existence of a relationship. |
| `doesntHave` | `array` | Filters based on the absence of a relationship. |
| `whereRelation` | `array` | Queries a relationship with a `where` condition. |
| `with` | `array`| Eager loads relationships. |
| `withCount` | `array`| Counts the results of a given relationship. |
| `select` | `array` | Selects specific columns to return. |
| `orderBy` | `string` | Sorts the results in ascending order. |
| `orderByDesc` | `string` | Sorts the results in descending order. |

### limit

The default limit is `1000`. You can change this in the config file or override it per request.

```javascript
axios.get(route('api.users.index', { limit: 100 }));
// GET /api/users?limit=100
```

### search

Search for a query within specified columns. Use `*` to search all fillable columns.

```javascript
axios.get(route('api.users.index', {
    search: {
        fields: ['name', 'email'],
        query: "john"
    }
}));
// GET /api/users?search[fields][0]=name&search[fields][1]=email&search[query]=john
```

### where

Add "where" clauses. You can also provide an operator (`gt`, `lt`, `sw`, etc.).

| Operator | Shorthand |
| :--- | :--- |
| startsWith | `sw` |
| endsWith| `ew` |
| notContains| `nin` |
| contains| `in` |
| equals | `eq` |
| notEqual | `neq` |
| greater | `gt` |
| greaterEquals | `gte` |
| lesser | `lt` |
| lesserEquals | `lte` |

```javascript
axios.get(route('api.users.index', {
    where: {
        date_of_birth: { gt: "1995-01-31" }
    }
}));
// GET /api/users?where[date_of_birth][gt]=1995-01-31
```

### Multiple where operators

Enables `BETWEEN`-like queries using multiple `where` clauses.

```javascript
axios.get(route('api.users.index', {
    where: [{
        date_of_birth: {
            lte: "1995-01-30"
        }
    }, {
        date_of_birth: {
            gte: "1995-01-15",
        },
    }],
}));
// GET /api/users?where[0][date_of_birth][lte]=1995-01-30&where[1][date_of_birth][gte]=1995-01-15
```

### orWhere

Adds an alternative `where` clause using `OR` logic.

```javascript
axios.get(route('api.countries.index', {
    where: {
        name: {
            in: 'Netherlands',
        },
    },
    orWhere: {
        name: {
            in: 'Belgium',
        },
    },
}));
// GET /api/countries?where[name][in]=Netherlands&orWhere[name][in]=Belgium
```

### whereJsonContains / whereJsonDoesntContain

Verifies that a column's value is (or is not) within a given array.

```javascript
axios.get(route('api.users.index', {
    whereJsonContains: { data->language: ['en', 'nl'] }
}));
// GET /api/users?whereJsonContains[data->language][0]=en&whereJsonContains[data->language][1]=nl
```


### whereIn / whereNotIn

Verifies that a column's value is (or is not) within a given array.

```javascript
axios.get(route('api.users.index', {
    whereIn: { role: ['admin', 'employee'] }
}));
// GET /api/users?whereIn[role][0]=admin&whereIn[role][1]=employee
```

### whereNull / whereNotNull

Verifies that a column's value is `NULL` or not `NULL`.

```javascript
axios.get(route('api.users.index', { whereNull: "email_verified_at" }));
// GET /api/users?whereNull=email_verified_at
```

### has / doesntHave

Limit results based on the existence of a relationship. You can also add nested conditions.

```javascript
axios.get(route('api.users.index', {
    has: {
        roles: {
            whereIn: { id: [1, 2] }
        }
    }
}));
// GET /api/users?has[roles][whereIn][id][0]=1&has[roles][whereIn][id][1]=2
```

### whereRelation

Query for a relationship's existence with a simple `where` condition.

```javascript
axios.get(route('api.users.index', {
    whereRelation: {
        roles: { name: 'admin' }
    }
}));
// GET /api/users?whereRelation[roles][name]=admin
```

### with

Eager load relationships to avoid N+1 query problems.

```javascript
axios.get(route('api.users.index', {
    with: {
        roles: {
            select: ['id', 'name']
        }
    }
}));
// GET /api/users?with[roles][select][0]=id&with[roles][select][1]=name
```

### withCount

Count the number of results from a relationship without loading them.

```javascript
axios.get(route('api.users.index', { withCount: ['posts'] }));
// GET /api/users?withCount[0]=posts
```

**Note:** To include the count in your response, you must manually add the `posts_count` attribute to your resource's `toArray` method.

```php
// In app/Http/Resources/UserResource.php
public function toArray(Request $request): array
{
    return [
        // ... other attributes
        'posts_count' => $this->whenCounted('posts'),
    ];
}
```

### select

Specify which columns to retrieve to keep responses lean.

```javascript
axios.get(route('api.users.index', { select: ['id', 'name'] }));
// GET /api/users?select[0]=id&select[1]=name
```

### orderBy / orderByDesc

Sort the results by a given column, including columns on related models.

```javascript
axios.get(route('api.users.index', { orderBy: 'roles.name' }));
// GET /api/users?orderBy=roles.name
```

### Custom Orderable Columns

To make custom columns (e.g., from `withCount` or `withSum`) sortable, add them to the `$apiOrderBy` property on your model.

```php
// In your Product.php model
class Product extends Model
{
    public array $apiOrderBy = [
        'articles_sum_price', // From a withSum query
    ];
}
```

Now you can sort by this custom column:
`GET /api/products?orderBy=articles_sum_price`

---

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

---

## Postcardware

You're free to use this package, but if it makes it to your production environment, we highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Quotec, Traktieweg 8c 8304 BA, Emmeloord, Netherlands.

---

## Credits

-   [Wim Pruiksma](https://github.com/wimurk)
-   [All Contributors](../../contributors)

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
