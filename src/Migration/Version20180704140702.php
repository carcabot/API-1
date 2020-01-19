<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180704140702 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE contracts ADD contract_period_max_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE contracts ADD contract_period_min_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE contracts ADD contract_period_unit_code TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE contracts ADD contract_period_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD termination_reason VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_requests DROP termination_reason');
        $this->addSql('ALTER TABLE contracts DROP contract_period_max_value');
        $this->addSql('ALTER TABLE contracts DROP contract_period_min_value');
        $this->addSql('ALTER TABLE contracts DROP contract_period_unit_code');
        $this->addSql('ALTER TABLE contracts DROP contract_period_value');
    }
}
