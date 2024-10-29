# Static Standalone Classes for Homebase 2 php 8/8.4 Framework

tested with [spartan-test](https://github.com/parf/spartan-test), checked with [psalm](https://psalm.dev/docs/annotating_code/supported_annotations/) (level 2) && [php-stan](https://phpstan.org/writing-php-code/phpdocs-basics) (level 6)


## Using it
- `composer require "homebase2/hb-core:dev-main"` - **recommended**
- `composer require homebase2/hb-core` for stable version

# Provided Classes & Functions

- [Str](src/hb/Str.php) - string methods
- [Arr](src/hb/Arr.php) & [Arr0](src/hb/Arr0.php) - Generic Array Methods
- [DH aka DeepHash](DH-DeepHash.md)  - Deep(aka nested) Array methods
- TODO: AH - Array of Hashes/Records (~ sql tables)
- TODO: ADH - Array of AH (~ mongo records)
- [\hb\ functions](src/hb-functions.inc.php) - used by framework



# FRAMEWORK DEVELOPMENT STUFF
TODO - move to Homebase Development Document


## Install
1. `composer install`
2. install [php-tools](https://github.com/homebase/php-tools#install)
3. `ln -s ~/src/php-tools/bin tools`


## Notable tools provided

> `composer test`
    run [unit tests](https://github.com/parf/spartan-test). use `test-q` to run quite tests (show errors only)

> `composer psalm`
    check code with [psalm](https://psalm.dev/docs/annotating_code/supported_annotations/)

> `composer stan`
    check code with [php-stan](https://phpstan.org/writing-php-code/phpdocs-basics) (default level is 6)

> `composer lint`
    php syntax check

> `composer psalm-dry`   AND   `composer psalm-fix`  (aka [psalter](https://psalm.dev/docs/manipulating_code/fixing/)
    review/apply suggested code changes by psalm, be careful always do dry before applying

> `composer cs-dry`   AND   `composer cs-fix`
    review/apply suggested code changes by [php-cs-fixer](https://mlocati.github.io/php-cs-fixer-configurator/), be careful always do dry before applying

> `composer doc`
    generate [phpDocumentor](https://docs.phpdoc.org/3.0/guide/guides/running-phpdocumentor.html#quickstart) documentation in `doc` folder

> `./check`
    do all checks: lint, unit tests, psalm, php-stan; stops when any of them failed

> `./check-commit`, `./check-push`
    do `./check`, add all new files to git, do `git commit -v -s` and `git push --tags`

> `./psysh`
    php shell: [Docs](https://developpaper.com/psysh-php-interactive-console/)<br>
    notable commands: `wtf`, `doc ClassName`, `doc ClassName::method`, `show ClassName::method`, `ls -l ClassName`, `ls -l --grep all \hb\Arr`
    use `help` to see more

PS:<br>
 this project was bootstrapped from [composer-php8-template](https://github.com/parf/composer-php8-template)
