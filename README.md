# Expedition App

Application for importing, viewing and changing of the expedition data.

**Скарб у кожным слове!**

## Getting Started

1. Run `composer install`
2. Create database `bin/console doctrine:database:create`
3. Execute migrations `bin/console doctrine:migrations:migrate`
4. Add dictionary from https://github.com/staryca/dict_be

## Updating Entities 

- Run `bin/console make:migration`
- Create migration `bin/console doctrine:migrations:diff`

## Run messengers
- For IMEF-data run `bin/console messenger:consume imef -vv`
