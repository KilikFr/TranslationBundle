Kilik Translation Bundle
========================

KTB (Kilik Translation Bundle) is a tool to be used with Symfony Translator. It tries to simplify the exchanges with the (human) translators.

**Note for Symfony 3.4+ / Symfony 4+ projects**

> Since symfony applications are not a bundle, we use the special bundle name 'app' to handle import and export of application translations not located into a bundle

From the command line you can export translations (filtering with bundles names, domains, and locales) to CSV (Tab separators).

Your translator (colleague, service provider, etc...) can open CSV files with specific translator tools (or with Office Software).

Then, you can import updated translations to your project.

The translations made in vendors are also supported (useful when you have to work on big applications with a lot of bundles).

Concepts:

- your project is fully translated in a locale (locale reference, fallback)
- it aims to simplify the process to translate missing translations with non-team people

Add this bundle to your application
===================================

    composer require kilik/translation-bundle

Than, add this line to your AppKernel.php:

        $bundles = [
            // ...
            new Kilik\TranslationBundle\KilikTranslationBundle(),
            // ...
        ];

Export translations
===================

Export translations to CSV:

export translations, with EN locale as reference, and match missing translations to FR or ES to a file: 

    ./bin/console kilik:translation:export en fr,es AcmeBundle ~/translations.csv

work on some bundles at the same time: 

    ./bin/console kilik:translation:export en fr,es AcmeBundle,MyOtherBundle ~/translations.csv

export only lines with missing translations:

    ./bin/console kilik:translation:export en fr,es AcmeBundle --only-missing ~/translations.csv

export only some domains:

    ./bin/console kilik:translation:export en fr,es AcmeBundle --domains messages,validators ~/translations.csv

export application only translations (Symfony 3.4+ / Symfony 4+1):

    ./bin/console kilik:translation:export en fr app ~/translations.csv

Import translations
===================

Import translations from CSV (translations are merged with your current project translations).

import all translations from your CSV file, for a given locales:

    ./bin/console kilik:translation:import fr ~/translations.csv

import all translations from your CSV file, overriding existing translation keys:

    ./bin/console kilik:translation:import fr ~/translations.csv -o

import translations from your CSV file, for a specific bundle, for a given locales:

    ./bin/console kilik:translation:import fr --bundles AcmeBundle ~/translations.csv

import translations from your CSV file, for domains, for a given locales:

    ./bin/console kilik:translation:import fr --domains messages,validators AcmeBundle ~/translations.csv

you can also import translations with many locales:

    ./bin/console kilik:translation:import fr,es,nl ~/translations.csv

import translations from your CSV file, for application only translations (Symfony 3.4+ / Symfony 4+1):

    ./bin/console kilik:translation:import fr --bundles app ~/translations.csv

For bundle developpers
======================

```shell
# prepare tests
./prepare-tests.sh

# run tests
./run-tests.sh

# launch composer
./scripts/composer.sh
```
