# Leads Importer

A single-page Laravel application for importing CSV leads into SQLite.

## Requirements

PHP 8.5 with SQLite support and Composer.

## Run locally

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
composer run dev
```

Open [http://localhost:8000](http://localhost:8000) and upload the supplied CSV.

On the first migration, confirm SQLite database creation when prompted.
