-- Create the isolated Doctrine test database and grant only the CI app user access.
CREATE DATABASE IF NOT EXISTS help_desk_test
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_0900_ai_ci;

GRANT ALL PRIVILEGES ON help_desk_test.* TO 'helpdesk_ci'@'%';
