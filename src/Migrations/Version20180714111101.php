<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180714111101 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE issue_labeled_subscription (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', user_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', repository_url VARCHAR(255) NOT NULL, label_original_name VARCHAR(255) NOT NULL, label_normalized_name VARCHAR(255) NOT NULL, INDEX IDX_1211033A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', telegram_id VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, unconfirmed_email VARCHAR(255) DEFAULT NULL, confirmation_token VARCHAR(255) DEFAULT NULL, notification_transport_type VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE issue_labeled_subscription ADD CONSTRAINT FK_1211033A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE issue_labeled_subscription DROP FOREIGN KEY FK_1211033A76ED395');
        $this->addSql('DROP TABLE issue_labeled_subscription');
        $this->addSql('DROP TABLE user');
    }
}
