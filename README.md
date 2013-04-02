# MDMTranslatorCheckerBundle

Is a tool to help you fix your translations files.

## Usages

### Add the bundle to your AppKernel

````
...
new MDM\TranslatorCheckerBundle\MDMTranslatorCheckerBundle(),
...
````

### Detect duplicate of translation values

Sometimes your files can contains to keys with same values, used the next command to detect them :

````
php app/console translation:check-duplicates [culture]
````

### Detect missing tanslations

You can sometime forget to add `{% trans %}` token or `{{ "thing"|trans }}` filter. Can also make typos in your translations keys.

````
php app/console translation:check-missings [culture]
````

If you want to output in a Junit file use :

````
php app/console translation:check-missings en -n --junit myjunit.xml
````

Or for multiple cultures at the same time :

````
php app/console translation:check-missings fr,en -n --junit myjunit.xml
````

### Detect unused translation keys

If you want to detect keys not used in your twig :

````
php app/console translation:check-missings [culture] --show-unused
````

Of course can also be outputed in junit :

````
php app/console translation:check-missings fr,en -n --junit myjunit.xml --show-unused
````
