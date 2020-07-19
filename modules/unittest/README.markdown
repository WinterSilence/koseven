# KO7-PHPUnit integration

This module integrates PHPUnit with KO7 and is used to run all the core KO7 tests. In most cases you will not
need to use this module for testing your own projects. If there are particular helpers provided here that you rely on,
that may be a sign that your own code is too closely coupled to the behaviour of the KO7 core classes.

If you look through any of the tests provided in this module you'll probably notice all the `HorribleCamelCase`.
I've chosen to do this because it's part of the PHPUnit coding conventions and is required for certain features such as auto documentation.

## Requirements and installation

Dependencies are listed in the composer.json - run `composer install` to install the module and all external requirements.
Note that more usually you will add this module to your own module's composer.json:

```json
{
  "require-dev": {
    "ko7/unittest": "4.0.*@dev"
  }
}
```

## Usage

	$ phpunit --bootstrap=modules/unittest/bootstrap.php modules/unittest/tests.php

Alternatively you can use a `phpunit.xml` to have a more fine grained control
over which tests are included and which files are whitelisted.

Make sure you only whitelist the highest files in the cascading filesystem, else
you could end up with a lot of "class cannot be redefined" errors.  

If you use the `tests.php` testsuite loader then it will only whitelist the
highest files. see `config/unittest.php` for details on configuring the
`tests.php` whitelist.
