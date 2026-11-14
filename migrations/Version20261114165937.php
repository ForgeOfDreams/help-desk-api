<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261114165937 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE notification_deliveries (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, channel VARCHAR(16) NOT NULL, status VARCHAR(16) DEFAULT \'pending\' NOT NULL, attempts SMALLINT UNSIGNED DEFAULT 0 NOT NULL, last_error LONGTEXT DEFAULT NULL, sent_at DATETIME DEFAULT NULL, notification_id BINARY(16) NOT NULL, INDEX IDX_9475204AEF1A9D84 (notification_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notifications (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, type VARCHAR(32) NOT NULL, payload JSON NOT NULL, read_at DATETIME DEFAULT NULL, recipient_id BINARY(16) NOT NULL, ticket_id BINARY(16) DEFAULT NULL, INDEX IDX_6000B0D3E92F8F78 (recipient_id), INDEX IDX_6000B0D3700047D2 (ticket_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE roles (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, code VARCHAR(64) NOT NULL, name VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_B63E2EC777153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE team_members (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, role_in_team VARCHAR(16) DEFAULT \'member\' NOT NULL, joined_at DATETIME NOT NULL, team_id BINARY(16) NOT NULL, user_id BINARY(16) NOT NULL, INDEX IDX_BAD9A3C8296CD8AE (team_id), INDEX IDX_BAD9A3C8A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE teams (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, name VARCHAR(120) NOT NULL, code VARCHAR(64) NOT NULL, description LONGTEXT DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_96C2225877153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ticket_attachments (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, storage_key VARCHAR(512) NOT NULL, original_filename VARCHAR(255) NOT NULL, mime_type VARCHAR(127) NOT NULL, size_bytes INT UNSIGNED NOT NULL, checksum_sha256 VARCHAR(64) DEFAULT NULL, ticket_id BINARY(16) NOT NULL, comment_id BINARY(16) DEFAULT NULL, uploaded_by_id BINARY(16) NOT NULL, UNIQUE INDEX UNIQ_2B54FCA9111795A5 (storage_key), INDEX IDX_2B54FCA9700047D2 (ticket_id), INDEX IDX_2B54FCA9F8697D13 (comment_id), INDEX IDX_2B54FCA9A2B28FE8 (uploaded_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ticket_categories (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, name VARCHAR(120) NOT NULL, code VARCHAR(64) NOT NULL, description LONGTEXT DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, parent_id BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_AC60D43C77153098 (code), INDEX IDX_AC60D43C727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ticket_comments (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, body LONGTEXT NOT NULL, visibility VARCHAR(16) DEFAULT \'public\' NOT NULL, deleted_at DATETIME DEFAULT NULL, ticket_id BINARY(16) NOT NULL, author_id BINARY(16) NOT NULL, INDEX IDX_DAF76AAB700047D2 (ticket_id), INDEX IDX_DAF76AABF675F31B (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ticket_history (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, action VARCHAR(100) NOT NULL, changes_json JSON NOT NULL, ticket_id BINARY(16) NOT NULL, actor_id BINARY(16) DEFAULT NULL, INDEX IDX_2B762919700047D2 (ticket_id), INDEX IDX_2B76291910DAF24A (actor_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ticket_number_counters (counter_name VARCHAR(64) NOT NULL, counter_value BIGINT UNSIGNED NOT NULL, PRIMARY KEY (counter_name)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ticket_status_history (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, old_status VARCHAR(32) DEFAULT NULL, new_status VARCHAR(32) NOT NULL, reason LONGTEXT DEFAULT NULL, ticket_id BINARY(16) NOT NULL, changed_by_id BINARY(16) DEFAULT NULL, INDEX IDX_D6921C0D700047D2 (ticket_id), INDEX IDX_D6921C0D828AD0A0 (changed_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ticket_watchers (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, ticket_id BINARY(16) NOT NULL, user_id BINARY(16) NOT NULL, INDEX IDX_41B553AF700047D2 (ticket_id), INDEX IDX_41B553AFA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tickets (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, ticket_number BIGINT UNSIGNED NOT NULL, subject VARCHAR(200) NOT NULL, description LONGTEXT NOT NULL, status VARCHAR(32) DEFAULT \'new\' NOT NULL, priority VARCHAR(16) DEFAULT \'normal\' NOT NULL, first_response_at DATETIME DEFAULT NULL, resolved_at DATETIME DEFAULT NULL, closed_at DATETIME DEFAULT NULL, version INT DEFAULT 1 NOT NULL, requester_id BINARY(16) NOT NULL, assignee_id BINARY(16) DEFAULT NULL, team_id BINARY(16) DEFAULT NULL, category_id BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_54469DF4ECD2759F (ticket_number), INDEX IDX_54469DF4ED442CF4 (requester_id), INDEX IDX_54469DF459EC7D60 (assignee_id), INDEX IDX_54469DF4296CD8AE (team_id), INDEX IDX_54469DF412469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, email VARCHAR(254) NOT NULL, password_hash VARCHAR(255) NOT NULL, display_name VARCHAR(150) NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, email_verified_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_roles (user_id BINARY(16) NOT NULL, role_id BINARY(16) NOT NULL, INDEX IDX_54FCD59FA76ED395 (user_id), INDEX IDX_54FCD59FD60322AC (role_id), PRIMARY KEY (user_id, role_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE notification_deliveries ADD CONSTRAINT FK_9475204AEF1A9D84 FOREIGN KEY (notification_id) REFERENCES notifications (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3E92F8F78 FOREIGN KEY (recipient_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3700047D2 FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE team_members ADD CONSTRAINT FK_BAD9A3C8296CD8AE FOREIGN KEY (team_id) REFERENCES teams (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE team_members ADD CONSTRAINT FK_BAD9A3C8A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE ticket_attachments ADD CONSTRAINT FK_2B54FCA9700047D2 FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ticket_attachments ADD CONSTRAINT FK_2B54FCA9F8697D13 FOREIGN KEY (comment_id) REFERENCES ticket_comments (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE ticket_attachments ADD CONSTRAINT FK_2B54FCA9A2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES users (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE ticket_categories ADD CONSTRAINT FK_AC60D43C727ACA70 FOREIGN KEY (parent_id) REFERENCES ticket_categories (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE ticket_comments ADD CONSTRAINT FK_DAF76AAB700047D2 FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ticket_comments ADD CONSTRAINT FK_DAF76AABF675F31B FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE ticket_history ADD CONSTRAINT FK_2B762919700047D2 FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ticket_history ADD CONSTRAINT FK_2B76291910DAF24A FOREIGN KEY (actor_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE ticket_status_history ADD CONSTRAINT FK_D6921C0D700047D2 FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ticket_status_history ADD CONSTRAINT FK_D6921C0D828AD0A0 FOREIGN KEY (changed_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE ticket_watchers ADD CONSTRAINT FK_41B553AF700047D2 FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ticket_watchers ADD CONSTRAINT FK_41B553AFA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF4ED442CF4 FOREIGN KEY (requester_id) REFERENCES users (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF459EC7D60 FOREIGN KEY (assignee_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF4296CD8AE FOREIGN KEY (team_id) REFERENCES teams (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF412469DE2 FOREIGN KEY (category_id) REFERENCES ticket_categories (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user_roles ADD CONSTRAINT FK_54FCD59FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_roles ADD CONSTRAINT FK_54FCD59FD60322AC FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification_deliveries DROP FOREIGN KEY FK_9475204AEF1A9D84');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D3E92F8F78');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D3700047D2');
        $this->addSql('ALTER TABLE team_members DROP FOREIGN KEY FK_BAD9A3C8296CD8AE');
        $this->addSql('ALTER TABLE team_members DROP FOREIGN KEY FK_BAD9A3C8A76ED395');
        $this->addSql('ALTER TABLE ticket_attachments DROP FOREIGN KEY FK_2B54FCA9700047D2');
        $this->addSql('ALTER TABLE ticket_attachments DROP FOREIGN KEY FK_2B54FCA9F8697D13');
        $this->addSql('ALTER TABLE ticket_attachments DROP FOREIGN KEY FK_2B54FCA9A2B28FE8');
        $this->addSql('ALTER TABLE ticket_categories DROP FOREIGN KEY FK_AC60D43C727ACA70');
        $this->addSql('ALTER TABLE ticket_comments DROP FOREIGN KEY FK_DAF76AAB700047D2');
        $this->addSql('ALTER TABLE ticket_comments DROP FOREIGN KEY FK_DAF76AABF675F31B');
        $this->addSql('ALTER TABLE ticket_history DROP FOREIGN KEY FK_2B762919700047D2');
        $this->addSql('ALTER TABLE ticket_history DROP FOREIGN KEY FK_2B76291910DAF24A');
        $this->addSql('ALTER TABLE ticket_status_history DROP FOREIGN KEY FK_D6921C0D700047D2');
        $this->addSql('ALTER TABLE ticket_status_history DROP FOREIGN KEY FK_D6921C0D828AD0A0');
        $this->addSql('ALTER TABLE ticket_watchers DROP FOREIGN KEY FK_41B553AF700047D2');
        $this->addSql('ALTER TABLE ticket_watchers DROP FOREIGN KEY FK_41B553AFA76ED395');
        $this->addSql('ALTER TABLE tickets DROP FOREIGN KEY FK_54469DF4ED442CF4');
        $this->addSql('ALTER TABLE tickets DROP FOREIGN KEY FK_54469DF459EC7D60');
        $this->addSql('ALTER TABLE tickets DROP FOREIGN KEY FK_54469DF4296CD8AE');
        $this->addSql('ALTER TABLE tickets DROP FOREIGN KEY FK_54469DF412469DE2');
        $this->addSql('ALTER TABLE user_roles DROP FOREIGN KEY FK_54FCD59FA76ED395');
        $this->addSql('ALTER TABLE user_roles DROP FOREIGN KEY FK_54FCD59FD60322AC');
        $this->addSql('DROP TABLE notification_deliveries');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE roles');
        $this->addSql('DROP TABLE team_members');
        $this->addSql('DROP TABLE teams');
        $this->addSql('DROP TABLE ticket_attachments');
        $this->addSql('DROP TABLE ticket_categories');
        $this->addSql('DROP TABLE ticket_comments');
        $this->addSql('DROP TABLE ticket_history');
        $this->addSql('DROP TABLE ticket_number_counters');
        $this->addSql('DROP TABLE ticket_status_history');
        $this->addSql('DROP TABLE ticket_watchers');
        $this->addSql('DROP TABLE tickets');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE user_roles');
    }
}
