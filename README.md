# URI Template #

This is a full implementation of [RFC 6570](http://tools.ietf.org/html/rfc6570).

## Installation ##

Add the following to your `composer.json` file:

```javascript
{
    "repositories": [
        {
            "type": "composer",
            "url": "http://composer/"
        }
    ],
    "require": {
        "ql/ql-uri-template": "*"
    }
}
```

## Usage ##

```php
<?php
use QL\UriTemplate\Expander;
use QL\UriTemplate\UriTemplate;


$tpl = '/authenticate/{username}{?password}';
$tpl = new UriTemplate($tpl, new Expander);

$url = $tpl->expand([
    'username' => 'mnagi',
    'password' => 'hunter2',
]);

echo $url; // outputs "/authenticate/mnagi?password=hunter2"
```

Note that the above example throws exceptions for an invalid template or an
invalid set of variables. Some applications may *expect* malformed URI
templates and wish to deal with them in a more graceful way. In this case it is
recommended you use the `Expander` class directly.

```php
<?php
use QL\UriTemplate\Expander;

$tpl = '/authenticate/{username}{?password}';
$exp = new Expander;

$url = $exp($tpl, ['username' => 'mnagi', 'password' => 'hunter2' ]);

echo $url; // outputs "/authenticate/mnagi?password=hunter2"
```

The difference between the two (other than how they are invoked) is when there
are errors of some kind:

```php
<?php
use QL\UriTemplate\Expander;
use QL\UriTemplate\UriTemplate;
use QL\UriTemplate\Exception;

$badTpl = '/foo{ba';
$expander = new Expander;

// error with template in Expander
$expander($badTpl, []);
echo $expander->lastError() . "\n"; // "Unclosed expression at offset 4: /foo{ba"

// error with template in UriTemplate
try {
    $tpl = new UriTemplate($badTpl);
} catch (Exception $e) {
    echo $e->getMessage() . "\n"; // outputs "Unclosed expression at offset 4: /foo{ba"
}

// error with variables in template
$expander('/foo/{bar}', ['bar' => new stdClass]);
echo $expander->lastError() . "\n"; // "Objects without a __toString() method are not allowed as variable values."

// error with variales in UriTemplate
$tpl = new UriTemplate('/foo/{bar}', new Expander);
$tpl->expand(['bar' => STDIN]); // this will throw an exception with message "Resources are not allowed as variable values."
```
