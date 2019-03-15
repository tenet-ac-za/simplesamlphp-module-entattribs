entattribs:AttributeFromEntity  [![Build Status](https://travis-ci.org/safire-ac-za/simplesamlphp-module-entattribs.svg?branch=master)](https://travis-ci.org/safire-ac-za/simplesamlphp-module-entattribs)
==============================

This SimpleSAMLphp auth proc filter allows you to provides additional
attributes from based on entity attributes in metadata. It is useful
when entity metadata contains definitive information that you wish
to convert into a SAML attribute (e.g. an entity attribute containing
the value that should be used for _schacHomeOrganization_ in remote
IdP metadata).

Installation
------------

Once you have installed SimpleSAMLphp, installing this module is
very simple.  Just execute the following command in the root of your
SimpleSAMLphp installation:

```
composer.phar require safire-ac-za/simplesamlphp-module-entattribs:dev-master
```

where `dev-master` instructs Composer to install the `master` (**development**) branch from the Git repository. See the
[releases](https://github.com/safire-ac-za/simplesamlphp-module-entattribs/releases)
available if you want to use a stable version of the module

Usage
-----

This module provides the _entattribs:AttributeFromEntity_ auth proc filter,
which can be used as follows:

```php
50 => [
    'class'     => 'entattribs:AttributeFromEntity',
    '%replace',
    'urn:x-example:schacHomeOrganization' => 'schacHomeOrganization',
    'urn:x-example:schacHomeOrganizationType' => 'schacHomeOrganizationType',
],
```

Where the parameters are as follows:

* `class` - the name of the class, must be _entattribs:AttributeFromEntity_

* `%replace` - replace the values of any existing SAML attributes with those
   from the entity attributes. (Default is to create a multi-valued attribute
   unless `%ignore` is set.)

* `%ignore` - ignore any SAML attributes that already exist. (Default is to
   create a multi-valued attribute unless `%replace` is set.)

* `%skipsource` - do not look in the source metadata for entity attributes.
  (default is to check source metadata.)

* `%skipdest` - do not look in the destination metadata for entity attributes.
  (default is to check destination metadata.)

Any remaining key/value pairs are used to form a map between the entity
attribute name (key) and the corresponding SAML attribute name to use (value).

The parameters `%replace` and `%ignore` are intended to be mutually exclusive
and using them together will generate a warning.

Example
-------

If the above filter were applied following remote IdP metadata:

```php
$metadata['https://idp.example.org/idp/shibboleth'] = [
    /* ... */
    'EntityAttributes' => [
        'urn:x-example:schacHomeOrganization' => 'example.org',
        'urn:x-example:schacHomeOrganizationType' => 'urn:schac:homeOrganizationType:int:other',
    ],
    /* ... */
];
```

it would result in the following attributes:

```php
$attributes = [
    'schacHomeOrganization' => 'example.org',
    'schacHomeOrganizationType' => 'urn:schac:homeOrganizationType:int:other',
];
```

and any existing values of those two attributes would have been lost/replaced.
