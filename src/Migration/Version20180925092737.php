<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180925092737 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE campaigns ADD tariff_rate_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE campaigns DROP tariff_rate');
        $this->addSql('ALTER TABLE campaigns ADD CONSTRAINT FK_E37374704C86DC06 FOREIGN KEY (tariff_rate_id) REFERENCES tariff_rates (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_E37374704C86DC06 ON campaigns (tariff_rate_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE campaigns DROP CONSTRAINT FK_E37374704C86DC06');
        $this->addSql('DROP INDEX IDX_E37374704C86DC06');
        $this->addSql('ALTER TABLE campaigns ADD tariff_rate VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE campaigns DROP tariff_rate_id');
    }
}
