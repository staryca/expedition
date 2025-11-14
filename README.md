# Expedition App

Application for importing, viewing and changing of the expedition data.

**Скарб у кожным слове!**

## Getting Started

1. Run `composer install`
2. Create database `bin/console doctrine:database:create`
3. Execute migrations `bin/console doctrine:migrations:migrate`

## Updating Entities 

- Run `bin/console make:migration`

## Old projects without migrations

- Execute `CREATE TABLE doctrine_migration_versions ( "version" varchar(191) NOT NULL, executed_at timestamp(0) DEFAULT NULL::timestamp without time zone NULL, execution_time int4 NULL, CONSTRAINT doctrine_migration_versions_pkey PRIMARY KEY (version));`
- Execute `INSERT INTO doctrine_migration_versions ("version", executed_at, execution_time) VALUES('DoctrineMigrations\Version20251014102841', '2025-10-14 00:00:00.000', 0);`

## Run messengers
- For IMEF-data run `bin/console messenger:consume imef -vv`
