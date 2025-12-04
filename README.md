# Expedition App

Application for importing, viewing and changing of the expedition data.

**Скарб у кожным слове!**

## Getting Started

1. Run `composer install`
2. Create database `bin/console doctrine:database:create`
3. Execute migrations `bin/console doctrine:migrations:migrate`

## Updating Entities 

- Run `bin/console make:migration`

## Run messengers
- For IMEF-data run `bin/console messenger:consume imef -vv`
