<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261115052000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_action_tokens (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, token_hash VARCHAR(64) NOT NULL, purpose VARCHAR(32) NOT NULL, expires_at DATETIME NOT NULL, consumed_at DATETIME DEFAULT NULL, user_id BINARY(16) NOT NULL, INDEX IDX_86E48DB5A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_action_tokens ADD CONSTRAINT FK_86E48DB5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_action_tokens DROP FOREIGN KEY FK_86E48DB5A76ED395');
        $this->addSql('DROP TABLE user_action_tokens');
    }
}
