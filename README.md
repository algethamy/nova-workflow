# Workflow Resource Tool for Laravel Nova

This package helps you to create workflow on your Nova application

![screenshot](./diagram.png)

![screenshot](./details.png)


## Installation

You can install the package in to a Laravel app that uses [Nova](https://nova.laravel.com) via composer:

```bash
composer require cammac/nova-workflow
```

Next, publish the config file

```bash
php artisan vendor:publish --tag  workflow
```

open `config/workflow.php` and define your workflow

## Usage

To display the workflow that are associated with a given Nova resource, you need to add the workflow Resource Tool to your resource.

For example, in your `app/Nova/Order.php` file:

```php
public function fields(Request $request)
{
    return [
        ID::make()->sortable(),

        // Your other fields

        Workflow::make('request')->onlyOnDetail() // request is the workflow name defined in workflow configuration file

    ];
}
```

This will automatically search possible transitions for the current status

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
