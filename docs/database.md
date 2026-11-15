# База данных и миграции

## СУБД и соединение

Основная СУБД — MySQL 8.4, InnoDB, кодировка `utf8mb4` и collation `utf8mb4_0900_ai_ci`. Doctrine получает соединение из `DATABASE_URL`. В Compose внутренним хостом является сервис `database`, порт — `3306`.

Пример формата URL:

```dotenv
DATABASE_URL="mysql://help_desk:change_me@database:3306/help_desk?serverVersion=8.4&charset=utf8mb4"
```

Пароли с символами, имеющими специальное значение в URL, должны быть percent-encoded. Значение server version следует указывать в одном согласованном месте — в текущем URL или в Doctrine-конфигурации, без расходящихся настроек.

## Основные группы таблиц

Схема включает пользователей и роли (`users`, `roles`, связь `user_roles`), команды и членство (`teams`, `team_members`), тикеты и связанные данные (`tickets`, `ticket_categories`, `ticket_comments`, `ticket_attachments`, `ticket_watchers`), аудит/историю (`ticket_history`, `ticket_status_history`), уведомления (`notifications`, `notification_deliveries`), счётчик номеров тикетов (`ticket_number_counters`) и токены действий пользователя (`user_action_tokens`).

Фактические имена столбцов, внешних ключей, индексов и ограничений определяются атрибутами Doctrine и применёнными миграциями. Перед изменением модели сверяйтесь с реальным кодом и историей миграций.

## UUID и enum-значения

Сущности используют Symfony UID UUID и Doctrine type `uuid`. В MySQL UUID хранится в бинарном виде. Не предполагайте, что его строковое представление идентично физическому значению столбца.

Статусы и приоритеты реализованы как PHP backed enums и строковые колонки, а не MySQL ENUM. При изменении значений проверяйте валидаторы, сериализацию, фильтры, тесты и обратную совместимость клиентов.

## Миграции

Любое изменение физической схемы должно сопровождаться Doctrine Migration. Рабочий процесс:

1. Изменить сущность или mapping.
2. Сгенерировать миграцию в приложении с текущим `composer.lock`.
3. Проверить SQL миграции, типы UUID, внешние ключи, индексы и поведение при существующих данных.
4. Проверить результат на отдельной локальной базе.
5. Применить миграции и проверить metadata/schema.
6. Закоммитить миграцию вместе с кодом.

Команды:

```bash
docker compose exec -T php php bin/console make:migration
docker compose exec -T php php bin/console doctrine:migrations:sync-metadata-storage
docker compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec -T php php bin/console doctrine:schema:validate
```

Не используйте `doctrine:schema:update --force` как механизм production-деплоя. Миграция должна быть ревьюема и повторяема.

## Тестовая БД Symfony

В `config/packages/doctrine.yaml` тестовый режим задаёт суффикс:

```yaml
when@test:
    doctrine:
        dbal:
            dbname_suffix: '_test%env(default::TEST_TOKEN)%'
```

Поэтому базовый URL для CI должен использовать имя `help_desk`, а не `help_desk_test`. Symfony добавляет `_test` автоматически и формирует `help_desk_test`; при установленном `TEST_TOKEN` в конце также добавляется его значение.

CI overlay указывает базовое имя `help_desk`. SQL-файл `.github/mysql-ci-init.sql` создаёт `help_desk_test` и выдаёт пользователю `helpdesk_ci` права на эту базу. Не меняйте URL на `help_desk_test`, иначе получится `help_desk_test_test`.

## Транзакции и конкурентные обновления

Номер тикета выделяется атомарным счётчиком, а не вычислением максимального значения. `Ticket::version` позволяет Doctrine обнаруживать конкурирующие обновления. При бизнес-операциях, создающих тикет или изменяющих его состояние, связанные записи истории и аудита должны фиксироваться в согласованной транзакции.

События внешним подписчикам нельзя публиковать до commit. Если нужна гарантия доставки при сбоях между commit и публикацией, используйте transactional outbox и отдельный процесс доставки.

## Резервное копирование

Процедура backup/restore в предоставленной конфигурации не автоматизирована. До production необходимо определить частоту и retention резервных копий, шифрование, хранение вне основного узла, контроль целостности и регулярные проверочные восстановления. Тестовое восстановление должно включать проверку миграций и целостности ключевых таблиц.
