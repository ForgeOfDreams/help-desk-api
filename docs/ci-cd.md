# CI/CD и выпуск

## CI workflow

`.github/workflows/ci.yaml` рассчитан на push и pull request в `main` и `dev`, ручной запуск, вызов как reusable workflow и еженедельный scheduled запуск. CI должен выполнять статические проверки репозитория, аудит GitHub Actions, сборку production target и интеграционные проверки приложения.

Основные этапы:

1. Super-Linter проверяет GitHub Actions, JSON, Markdown, YAML и Dockerfile.
2. zizmor анализирует workflow на проблемы безопасности.
3. Docker собирает production target без публикации на каждом запуске CI.
4. Интеграционное задание поднимает MySQL и Mailpit, затем приложение.
5. Composer проверяет строгую валидность манифеста, платформенные требования и уязвимости зависимостей.
6. PHP-код проходит синтаксическую проверку, Symfony YAML/container lint.
7. CI создаёт тестовую БД, синхронизирует migration metadata, применяет миграции и проверяет Doctrine schema.
8. Выполняется PHPUnit и smoke test `https://localhost/api/docs`.
9. При ошибке сохраняются логи Compose; после задания удаляются контейнеры и тестовые volumes.

Задание намеренно использует `php bin/console`, а не непосредственный запуск `bin/console`, чтобы не зависеть от executable bit файла в контейнере.

## Отделение тестовой базы

CI использует `COMPOSE_FILE=compose.yaml:compose.override.yaml:.github/compose.ci.yaml`. Эта настройка предназначена только для GitHub-hosted Linux runner. Test-specific overlay содержит искусственные значения и не должен использоваться в production.

`DATABASE_URL` в CI указывает на базовое имя `help_desk`; тестовый суффикс `_test` добавляется Symfony через Doctrine-конфигурацию. `.github/mysql-ci-init.sql` заранее создаёт `help_desk_test` и выдаёт доступ `helpdesk_ci`. Ошибка `help_desk_test_test` означает, что `_test` ошибочно записан и в URL, и в `dbname_suffix`.

Перед стартом одноразового PHP-командного контейнера CI запускает и дожидается готовности сервисов `database` и `mailer`. Это необходимо, потому что штатный entrypoint приложения проверяет доступность БД.

## Релизный workflow

`.github/workflows/release.yaml` срабатывает на теги `v*`, проверяет стабильный SemVer `vMAJOR.MINOR.PATCH`, повторно вызывает CI и после успешной проверки публикует production image в GitHub Container Registry.

Формат образа:

```text
ghcr.io/<owner>/<repository>:v1.2.3
```

Тег `latest` обновляется только для самой новой стабильной SemVer-версии по имеющимся тегам. Более старый тег, опубликованный позднее, не должен откатывать `latest` назад.

Сборка production image использует target `frankenphp_prod`, Buildx, SBOM и provenance attestations. Workflow использует `GITHUB_TOKEN`; право `packages: write` ограничено publish job. Внешние actions фиксируются полными commit SHA, а checkout работает с `persist-credentials: false`.

## Выпуск версии

```bash
git tag v1.2.3
git push origin v1.2.3
```

Перед созданием тега убедитесь, что CI успешно пройден на release commit, миграции применимы, зависимости проверены, документация обновлена и production-конфигурация содержит реальные секреты.

## Настройки GitHub

Рекомендуется защитить `main` и `dev` правилами pull request и обязательными checks. Токен workflow должен быть read-only по умолчанию; права записи выдаются лишь конкретным jobs. Включите доступные в используемом тарифе dependency graph, Dependabot security updates, secret scanning и push protection.

Не разрешайте автоматическое слияние major-обновлений framework/runtime без ревью. Dependabot PR должны проходить полный CI.

## Статус подтверждения

YAML и архивные артефакты могут быть проверены локально, но это не равно успешному исполнению workflow в GitHub. После внесения документации или изменения workflow результат необходимо подтвердить реальным запуском на GitHub-hosted runner.
