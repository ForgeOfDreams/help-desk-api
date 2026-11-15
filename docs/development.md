# Разработка

## Требования

- Git.
- Docker Engine или Docker Desktop с Docker Compose v2.
- Доступ к реестру образов и пакетным источникам для первичной сборки.
- Composer-зависимости проекта и закоммиченный `composer.lock`.

PHP, расширения и Composer-зависимости следует запускать внутри контейнера приложения, чтобы локальная машина не расходилась с целевым PHP runtime.

## Локальный запуск

```bash
docker compose config
docker compose up -d --build
docker compose ps
docker compose logs -f php database mailer
```

Если репозиторий содержит Dockerfile в `.docker/Dockerfile`, Compose и workflow должны ссылаться на этот путь. Если Dockerfile хранится по другому пути, синхронно обновите `compose.yaml`, production build и CI workflow.

## Проверка API

Откройте `https://localhost/api/docs`. В стандартной конфигурации HTTPS используется локальный сертификат, который может потребовать принятия/добавления в доверенные для локальных smoke test. API endpoint для входа — `POST /api/auth/login`; дальнейшие запросы отправляют `Authorization: Bearer <JWT>`.

Локальная документация API — источник для ручного тестирования, но окончательный контракт проверяйте по OpenAPI и тестам.

## Письма

Письма в development перехватываются Mailpit. Откройте `http://localhost:8025`, затем вызовите сценарий регистрации или повторной отправки подтверждения. Сообщения не должны уходить на реальные почтовые адреса из локального окружения.

## Работа со схемой данных

```bash
docker compose exec -T php php bin/console make:migration
docker compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec -T php php bin/console doctrine:schema:validate
```

Сгенерированную миграцию следует просмотреть и протестировать на копии подходящей схемы до commit.

## Изменение API

При добавлении операции необходимо обновить DTO/ресурс, processor/provider, правила доступа, валидацию, тесты и документацию. Проверьте, что новые свойства не раскрывают внутренние поля сущностей. Если меняется формат запроса/ответа, обновите примеры и при необходимости версию/совместимость клиента.

## Проверки перед commit

```bash
docker compose exec -T php composer validate --strict
docker compose exec -T php composer check-platform-reqs --lock
docker compose exec -T php composer format:check
docker compose exec -T php composer audit --locked --no-interaction
docker compose exec -T php php bin/console lint:yaml config
docker compose exec -T php php bin/console lint:container
docker compose exec -T php php bin/console doctrine:schema:validate
docker compose exec -T php php bin/phpunit
```

Если команды `format` и `format:check` отсутствуют, сначала установите PHP-CS-Fixer как dev-зависимость и добавьте Composer scripts по `docs/code-quality.md`.

## Git-практика

Pull request должен включать код, миграции и обновление документации в случае изменения публичного поведения или эксплуатационных требований. В описании PR указывайте бизнес-причину, совместимость, изменения схемы, тесты, риски и шаги отката.
