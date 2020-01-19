<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190117071339 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE customer_account_relationships ADD customer_portal_enabled BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE leads ADD referral_source VARCHAR(254) DEFAULT NULL');
        $this->addSql('ALTER TABLE leads ADD specified_referral_source VARCHAR(255) DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN leads.referral_source IS \'(DC2Type:referral_source_enum)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE leads DROP referral_source');
        $this->addSql('ALTER TABLE leads DROP specified_referral_source');
        $this->addSql('ALTER TABLE customer_account_relationships DROP customer_portal_enabled');
    }
}
