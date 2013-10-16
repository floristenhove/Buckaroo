Buckaroo
========

A bridge between laravel 4 and the Buckaroo Payment engine.


Installation
============

Add `johnhout/buckaroo` as a requirement to composer.json:

```javascript
{
    "require": {
       "johnhout/buckaroo" : "dev-master"
    }
}
```

Update your packages with `composer update` or install with `composer install`.

Once Composer has installed or updated your packages you need to register Buckaroo with Laravel itself. Open up app/config/app.php and find the providers key towards the bottom and add:

```php
'johnhout\Buckaroo\BuckarooServiceProvider'
```

Configuration
=============

You should  publish a configuration file to enter your settings of your buckaroo account by running the following Artisan command.

```
$ php artisan config:publish johnhout/buckaroo
```

Add the serviceprovider
=============
We made it easy for you to access the class using the facade method. Add the following `in your config/app.php` in the 'aliases' array.

```php
'Buckaroo'    => 'johnhout\Buckaroo\Facades\Buckaroo',
```

Usage
=============

Before you will be transfered to a bank EU-Law requires the user to perform an action wich in our case will be a button.
The following code will generate a FORM with hidden inputs with everything set to be posted.
```php
Buckaroo::createForm(array(
	'brq_amount'  		=> '10.00',
	'brq_invoicenumber' => 'Invoice0001'
));
```

Adding a picked bank to the form for the iDeal portal.
```php
Buckaroo::createForm(array(
	'brq_amount'  				=> '10.00',
	'brq_invoicenumber' 		=> 'Invoice0001',
	'brq_payment_method' 		=> 'ideal',
	'brq_service_ideal_issuer' 	=> 'ABNANL2A'
));
```

Using PayPal.
```php
Buckaroo::createForm(array(
	'brq_amount'  		=> '10.00',
	'brq_invoicenumber' => 'Invoice0001',
	'brq_payment_method' => 'paypal',
	'brq_service_paypal_buyeremail' => 'user@example.com'
));
```


A complete list of possible banks to select from when using ideal
```html
<select>
	<option value="ABNANL2A">ABN AMRO</option>
	<option value="ASNBNL21">ASN Bank</option>
	<option value="FRBKNL2L">Friesland Bank</option>
	<option value="INGBNL2A">ING</option>
	<option value="RABONL2U">Rabobank</option>
	<option value="SNSBNL2A">SNS Bank</option>
	<option value="RBRBNL21">RegioBank</option>
	<option value="TRIONL2U">Triodos Bank</option>
	<option value="FVLBNL22">Van Lanschot</option>
	<option value="KNABNL2H">KNAB bank</option>
</select>
```



Retrieving order Information of a previously made payment with order number.
```php
Buckaroo::getInvoiceInfo('Invoice0001');
```
This will return the following array with if the test mode is active a the `test` key as `1`.
```php
Array
(
    [id] => 943E893D60F643F5B1BC295C925F4
    [attempts] => 1
    [type_description] => iDeal
    [datetime] => 2013-10-16T10:18:27+02:00
    [status] => 190
    [status_msg] => Succes
    [currency] => EUR
    [test] => 1
)
```

However if your simply want to resolve if a payment was payed than you could use the following it returns a bool.
```php
Buckaroo::checkInvoiceForSuccess('Invoice0001');
```

