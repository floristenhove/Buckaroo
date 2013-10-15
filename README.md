Buckaroo
========

A bridge between laravel 4 and the Buckaroo Payment engine/


Installation
============

Add `johnhout/Buckaroo` as a requirement to composer.json:

```javascript
{
    "require": {
        "rcrowe/twigbridge": "0.5.*"
    }
}
```

Update your packages with `composer update` or install with `composer install`.

Once Composer has installed or updated your packages you need to register Buckaroo with Laravel itself. Open up app/config/app.php and find the providers key towards the bottom and add:

```php
	'Johninthout\Buckaroo\BuckarooServiceProvider'
```

Configuration
=============

You should  publish a configuration file to enter your settings of your buckaroo account by running the following Artisan command.

```
$ php artisan config:publish johninthout\buckaroo
```

Add the serviceprovider
=============
We made it easy for you to access the class using the facade method. Add the following `in your config/app.php` in the 'aliases' array.

```php
	'Buckaroo'    => 'Johninthout\Buckaroo\Facades\Buckaroo',
```

Ussage
=============
Will update this later on!