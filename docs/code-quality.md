# Качество и форматирование кода

## PHP-CS-Fixer

Для PHP применяется `friendsofphp/php-cs-fixer` с набором правил `@Symfony`. Текущая конфигурация `.php-cs-fixer.dist.php` использует finder от корня проекта и исключает `vendor`, `var`, `node_modules` и `public/bundles`. Рискованные исправления отключены.

Установите зависимость в `require-dev`:

```bash
composer require --dev friendsofphp/php-cs-fixer:^3.95
```

Composer scripts должны присутствовать в корневом `composer.json`:

```json
{
  "scripts": {
    "format": "php-cs-fixer fix --config=.php-cs-fixer.dist.php",
    "format:check": "php-cs-fixer check --config=.php-cs-fixer.dist.php --diff --using-cache=no"
  }
}
```

Этот JSON — фрагмент, его нужно объединить с существующими scripts, а не заменить весь манифест.

В локальной разработке форматирование запускается через `composer format`; CI вызывает `composer format:check`, который проверяет код без изменения рабочих файлов. Сначала отформатируйте код и внимательно проверьте diff, затем коммитьте результат.

## Composer

Корневой `composer.json` должен содержать обязательные метаданные, включая `name` и `description`, чтобы команда `composer validate --strict` проходила. Название пакета должно использовать допустимый Composer vendor/package формат и соответствовать реальному репозиторию.

`composer.lock` коммитится. CI использует `composer check-platform-reqs --lock`, чтобы обнаруживать отсутствие нужных PHP extensions в образе приложения, и `composer audit --locked`, чтобы проверить уязвимости зависимостей.

## Super-Linter

Super-Linter используется для файлов репозитория: GitHub Actions, JSON, Markdown, YAML и Dockerfile. PHP-specific validators должны проверяться внутри application image, где версия PHP и расширения соответствуют `composer.lock`, а не в отдельном независимом linter image.

В Super-Linter v9 нельзя смешивать режим opt-in (`VALIDATE_*=true`) и исключения (`VALIDATE_*=false`). Если используется opt-in, список включённых проверок должен содержать только необходимые `true` флаги. Не добавляйте противоположные `false` флаги в тот же список.

## zizmor

zizmor проверяет GitHub Actions workflow на небезопасные шаблоны. Важные правила проекта:

- external actions pin-ятся полными SHA;
- права `GITHUB_TOKEN` минимальны и документированы там, где анализатор требует комментарий;
- release workflow использует локальный reusable workflow через специальный синтаксис GitHub;
- `persist-credentials: false` используется для checkout, когда последующее действие не требует credentials в git config;
- доступ на публикацию пакетов ограничен отдельным release job.

Не подавляйте findings без понимания риска и объяснения в ревью.

## Минимальные проверки перед слиянием

```bash
docker compose exec -T php composer validate --strict
docker compose exec -T php composer audit --locked --no-interaction
docker compose exec -T php composer check-platform-reqs --lock
docker compose exec -T php composer format:check
docker compose exec -T php php bin/console lint:yaml config
docker compose exec -T php php bin/console lint:container
docker compose exec -T php php bin/console doctrine:schema:validate
docker compose exec -T php php bin/phpunit
```

Для изменений workflow также должны пройти Super-Linter и zizmor.
