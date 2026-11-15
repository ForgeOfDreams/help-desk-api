# Конфигурация окружений

## Общий принцип

Нечувствительные значения и разумные локальные defaults могут задаваться `.env`/Compose. Секреты production должны передаваться через защищённые переменные среды или secret store. Не коммитьте `.env.local`, production credentials и приватные JWT-ключи.

Compose использует стандартные файлы:

- `compose.yaml` — базовые сервисы PHP/FrankenPHP, MySQL и named volumes.
- `compose.override.yaml` — локальная разработка, bind-mount исходников, Xdebug и Mailpit.
- `compose.prod.yaml` — параметры production-образа и обязательные production-секреты.
- `.github/compose.ci.yaml` — настройки, используемые только GitHub Actions через `COMPOSE_FILE`.

Не добавляйте CI overlay в обычный локальный или production запуск без специальной причины.

## Переменные Compose и приложения

| Переменная | Назначение |
|---|---|
| `APP_ENV` | Окружение Symfony; в локальном режиме обычно `dev`, в production — `prod`, тесты запускаются с `--env=test` |
| `APP_SECRET` | Секрет Symfony; должен быть уникальным и защищённым вне CI |
| `DATABASE_URL` | Doctrine DBAL URL; внутри Compose указывает на `database:3306` |
| `MYSQL_VERSION` | Версия MySQL; для текущего baseline — `8.4` |
| `MYSQL_DATABASE` | База, инициализируемая образом MySQL |
| `MYSQL_USER` | Приложенческий пользователь MySQL |
| `MYSQL_PASSWORD` | Пароль приложенческого пользователя |
| `MYSQL_ROOT_PASSWORD` | Пароль root MySQL только для инициализации/администрирования |
| `MYSQL_CHARSET` | Кодировка сервера; ожидается `utf8mb4` |
| `MYSQL_COLLATION` | Collation; текущая настройка — `utf8mb4_0900_ai_ci` |
| `SERVER_NAME` | Имя сайта для Caddy/FrankenPHP |
| `DEFAULT_URI` | Базовый URI Symfony при генерации абсолютных адресов |
| `HTTP_PORT` | Published HTTP-порт на хосте, по умолчанию 80 |
| `HTTPS_PORT` | Published HTTPS-порт на хосте, по умолчанию 443 |
| `HTTP3_PORT` | Published UDP-порт HTTP/3, по умолчанию 443 |
| `MAILER_DSN` | DSN транспорта Symfony Mailer |
| `APP_FRONTEND_URL` | Базовый frontend URL для ссылок из писем |
| `APP_DEFAULT_SENDER` | Адрес отправителя писем |
| `CORS_ALLOW_ORIGIN` | Доверенные origin для браузерного клиента, согласно security-конфигурации |
| `JWT_SECRET_KEY` | Путь к приватному JWT-ключу |
| `JWT_PUBLIC_KEY` | Путь к публичному JWT-ключу |
| `JWT_PASSPHRASE` | Passphrase приватного ключа, если она задана |
| `CADDY_MERCURE_JWT_SECRET` | Секрет Mercure publisher/subscriber JWT |
| `CADDY_MERCURE_JWT_ISSUER` | Issuer для Mercure JWT |
| `CADDY_MERCURE_URL` | Внутренний URL hub, используемый конфигурацией Caddy/приложения |
| `CADDY_MERCURE_PUBLIC_URL` | Публичный URL hub |

Дополнительные настройки могут быть описаны в `.env`, `config/packages/*`, `frankenphp/Caddyfile` и `frankenphp/conf.d/*`. При изменении окружения проверяйте их совместно.

## Development

Development override монтирует исходники в `/app`, включает режим наблюдения FrankenPHP и может включать Xdebug. Mailpit запускается как Compose-сервис `mailer`. PHP-контейнер использует `MAILER_DSN=smtp://mailer:1025`; веб-интерфейс Mailpit доступен на `http://localhost:8025`.

SMTP-порт 1025 доступен внутри сети Compose, но не публикуется наружу стандартным development override. В контейнере `php` нельзя использовать `localhost` как hostname Mailpit: он указывает на сам PHP-контейнер.

В тестовом окружении Symfony Mailer должен использовать null transport, чтобы тесты не отправляли внешнюю почту.

## Production

Production Compose запускает целевой образ `frankenphp_prod`. Обязательные секреты, включая `APP_SECRET` и Mercure secret, должны задаваться окружением развёртывания; нельзя использовать локальные placeholder-значения.

Production `MAILER_DSN` должен указывать на реальный поддерживаемый SMTP/API-транспорт. Конфигурацию Mailpit нельзя переносить в production.

Перед развёртыванием проверьте пути ключей, права чтения, внешние адреса, TLS, соединение с БД и переменные, необходимые ссылкам подтверждения email.

## Проверка конфигурации

```bash
docker compose config --quiet
docker compose exec -T php php bin/console lint:yaml config
docker compose exec -T php php bin/console lint:container
docker compose exec -T php php bin/console debug:config doctrine
docker compose exec -T php php bin/console debug:config api_platform
```

Для проверки production-конфигурации выполняйте команды в окружении `prod` с теми же типами переменных и секретов, что используются при реальном запуске; не копируйте production-секреты в локальный `.env`.
