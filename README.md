# Laravel Model Seeder
Create seeders from your model using the data in your database

>This package will generate automatic seeders using your models. It supports pivot relations to automatic sync relations in pivot tables with your models. 

[![Latest Version on Packagist](https://img.shields.io/packagist/v/singlequote/laravel-model-seeder.svg?style=flat-square)](https://packagist.org/packages/singlequote/laravel-model-seeder)
[![Total Downloads](https://img.shields.io/packagist/dt/singlequote/laravel-model-seeder.svg?style=flat-square)](https://packagist.org/packages/singlequote/laravel-model-seeder)


### Installation
```console
composer require singlequote/laravel-model-seeder --dev
```

### Usage
The package has a single command line with a few options.
The command below will generate seeders using laravels default folder structure.
The seeders will be created in `database/seeders` and your models should be in `App\Models`
```bahs
php artisan seed:make
```
### Model events
By default laravel adds the `WithoutModelEvents` trait to your seeders. The package does this also for abious reasons. If you would like to use your model events, you can add the `--with-events` option to the command.
```bash
php artisan seed:make --with-events
```

### Changing the package behaviour
If you moved your models to another location or you renamed your Models folder to something else you can change this in the config of the package.

#### Publish config
Using the command you can publish the config and edit it.

```bash
php artisan vendor:publish --tag=model-seeder
```

The default content of the config file is:
```php
<?php

return [
    
    /**
     * The relative path from your root to your models location
     * You can add multiple model paths
     */
    'models_path' => [
        "app/Models"
    ],
    
    /**
     * The relative path from your root to the location where the seeders will be generated
     */
    'output_path' => "database/seeders",
    
    /**
     * These columns will be excluded from your seeders
     */
    'exclude_columns' => [
        "id",
    ],    
];
```

### Changing model location and seeder output
You can change your models location using the config or using the `--path` option from your command line.
For example if you are using modules in your package and you want to create seeders for your module.
```bash
php artisan seed:make --path=Modules/TestModule/App/Models
```
#### Seeder output location
The package will try to detect the output directory by checking the folder structure of your project and look for the `database` folder.

If you changed this and the package can't detect the output path you can give the output path as a option with the command.

```bash
php artisan seed:make --path=Modules/TestModule/App/Models --output=database/seeders
```

#### Auto retrieve all models
If you have multiple model locations and want to make seeders of them all. You can use the `--path=auto` option. This option will import all declared classes and check if they extend the `Model::class`. 
```bash
php artisan seed:make --path=auto
```

#### Exclude models
By default the package will create seeders for every model in the given path. You can use the `--only=..` option to exclude other models from the process.
```bash
php artisan seed:make --only=User,Roles
```


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Postcardware

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Quotec, Traktieweg 8c 8304 BA, Emmeloord, Netherlands.

## Credits

- [Wim Pruiksma](https://github.com/wimurk)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
