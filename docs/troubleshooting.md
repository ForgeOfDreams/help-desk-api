# Устранение неполадок

## `composer validate --strict` сообщает, что отсутствуют `name` и `description`

Добавьте на верхний уровень `composer.json` поля `name` и `description`, не заменяя остальные
зависимости/scripts. Например, используйте валидное имя package формата `vendor/package`,
соответствующее фактическому репозиторию. После изменения закоммитьте `composer.json` и при
необходимости согласованный `composer.lock`.

## Super-Linter сообщает `Behavior not supported` при смешивании `VALIDATE` flags

Super-Linter v9 использует opt-in режим, когда задан хотя бы один `VALIDATE_*=true`. В таком режиме
не задавайте остальные `VALIDATE_*=false`. Либо используйте только белый список `true`, либо режим
исключений `false`, но не смешивайте их.

## zizmor сообщает `undocumented-permissions`

Добавьте краткий комментарий непосредственно рядом с разрешением `GITHUB_TOKEN`, объясняющий, какая
операция требует его. Не увеличивайте права сверх минимально необходимых. Для публикации образа
`packages: write` должно быть ограничено publish job.

## zizmor сообщает `self-repository`

Для вызова локального reusable workflow используйте GitHub-специфичный синтаксис
`$/.github/workflows/ci.yaml`, а не относительный путь `./.github/workflows/ci.yaml`, если это
соответствует текущей поддерживаемой версии синтаксиса GitHub.

## `getaddrinfo for database failed`

Команда PHP не может разрешить hostname `database`, если Compose-сервис БД не запущен в той же сети
или выбран неправильный Compose-набор. Проверьте:

```bash
docker compose config --quiet
docker compose ps
docker compose logs --tail=200 database
```

В CI перед одноразовыми командами приложения дождитесь `database` и `mailer` командой
`docker compose up --detach --wait database mailer`. Внутри Compose используйте hostname `database`,
а не `localhost`.

## Symfony пытается создать `help_desk_test_test`

Проверьте `when@test` в `config/packages/doctrine.yaml`. Если `dbname_suffix` содержит `_test`, то
`DATABASE_URL` должен содержать базовое имя `help_desk`, а не `help_desk_test`. В тестовом окружении
Doctrine формирует `help_desk_test` автоматически. Дополнительно учитывайте `TEST_TOKEN`, если он
задан.

## `Access denied` на тестовую БД

Проверьте, что CI использует нужный `.github/compose.ci.yaml`, MySQL был инициализирован на чистом
volume, а `.github/mysql-ci-init.sql` создаёт тестовую базу и grants для текущего CI-пользователя.
Изменение init SQL не применяется повторно к уже инициализированному MySQL volume автоматически; для
локального временного CI-стенда удаление volume допустимо только если данные можно потерять.

## `bin/console: permission denied`

Вызывайте консоль через PHP: `php bin/console ...`, а не как исполняемый файл. Это убирает
зависимость от executable bit `bin/console` в текущей файловой системе.

## Отсутствует `composer format:check`

Установите `friendsofphp/php-cs-fixer` в `require-dev`, добавьте `.php-cs-fixer.dist.php` в корень и
scripts `format`/`format:check` в `composer.json`. После этого выполните `composer format`,
проверьте diff и закоммитьте изменения. CI проверяет, что форматирование уже применено.

## `https://localhost/api/docs` не отвечает

Проверьте status/health контейнера, логи PHP/FrankenPHP, публикацию HTTPS-порта, `SERVER_NAME`,
конфигурацию Caddy и readiness БД. При self-signed/local TLS `curl` может требовать `--insecure`
только для локального smoke test; не отключайте проверку сертификата для production-запросов.

## Маршрут `/api/me` зарегистрирован дважды

В первоначальной JWT-заготовке мог использоваться `MeController`, а API Platform слой добавляет
`MeResource`. Оставьте единственную регистрацию маршрута и проверьте результат через `debug:router`
и `/api/docs`.
