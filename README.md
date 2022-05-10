# Static Standalone Classes for Homebase 2 php 8/8.1 Framework

checked with `psalm (level 2)` && `php-stan (level 6)`

## Install
After checkout do:
* `composer install`
* do steps from `setup-tools.howto`

# Provided Classes & Functions

- src/hb-functions.inc.php - "\hb" namespace functions used by framework
- Arr - array methods
- Str - string methods
- DH  - hb\deephash (deep array) methods (TODO)

## Notable tools provided

> `composer test`
    run unit tests. use `test-q` to run quite tests (show errors only)

> `composer psalm`
    check code with psalm

> `composer stan`
    check code with php-stan (default level is 6)

> `composer lint`
    php syntax check

> composer `psalm-dry`   AND   composer `psalm-fix`
    suggested code changes by psalm, be careful always do dry before applying

> `composer doc`
    generate phpDocumentor documentation in `doc` folder

> `./check`
    do all checks

> `./check-push`
    do all checks, add all new files to git, do `git commit -v` and `git push --tags`


