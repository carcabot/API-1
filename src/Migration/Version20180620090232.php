<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180620090232 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE leads ADD tariff_rate_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE leads ADD customer_of VARCHAR(254) DEFAULT NULL');
        $this->addSql('ALTER TABLE leads ADD date_deleted TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE leads ADD lpg_user BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE leads ADD tenant BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE leads ADD CONSTRAINT FK_179045524C86DC06 FOREIGN KEY (tariff_rate_id) REFERENCES tariff_rates (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_179045524C86DC06 ON leads (tariff_rate_id)');
        $this->addSql('ALTER TABLE customer_accounts ADD lpg_user BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_accounts ADD tenant BOOLEAN DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE leads DROP CONSTRAINT FK_179045524C86DC06');
        $this->addSql('DROP INDEX IDX_179045524C86DC06');
        $this->addSql('ALTER TABLE leads DROP tariff_rate_id');
        $this->addSql('ALTER TABLE leads DROP customer_of');
        $this->addSql('ALTER TABLE leads DROP date_deleted');
        $this->addSql('ALTER TABLE leads DROP lpg_user');
        $this->addSql('ALTER TABLE leads DROP tenant');
        $this->addSql('ALTER TABLE customer_accounts DROP lpg_user');
        $this->addSql('ALTER TABLE customer_accounts DROP tenant');
    }
}
