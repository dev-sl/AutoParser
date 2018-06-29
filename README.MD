### Settings:

* Set **TELEGRAM_TOKEN** and **TELEGRAM_CHAT_ID** in `.env `file
* Get migrations for create scheme: `php bin/console doctrine:migration:migrate`

### Run:
* `php bin/console app:get-cars`

### Database:
* **Sqlite3** in `var/app.db`

### Cron:
* crontab -e `*/1 * * * * php {path_to_project}/bin/console app:get-cars`