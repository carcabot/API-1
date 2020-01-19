<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20180405064805 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE customer_accounts ADD acquired_from_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_accounts ADD source VARCHAR(254) DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN customer_accounts.source IS \'(DC2Type:source_enum)\'');
        $this->addSql('ALTER TABLE customer_accounts ADD CONSTRAINT FK_E60DDD83B43AA055 FOREIGN KEY (acquired_from_id) REFERENCES customer_accounts (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_E60DDD83B43AA055 ON customer_accounts (acquired_from_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE customer_accounts DROP CONSTRAINT FK_E60DDD83B43AA055');
        $this->addSql('DROP INDEX IDX_E60DDD83B43AA055');
        $this->addSql('ALTER TABLE customer_accounts DROP acquired_from_id');
        $this->addSql('ALTER TABLE customer_accounts DROP source');
    }
}
