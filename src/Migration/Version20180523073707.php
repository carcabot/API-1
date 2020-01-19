<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20180523073707 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE contracts ADD deposit_amount_currency VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE contracts ADD deposit_amount_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD deposit_amount_currency VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD deposit_amount_value NUMERIC(19, 4) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_requests DROP deposit_amount_currency');
        $this->addSql('ALTER TABLE application_requests DROP deposit_amount_value');
        $this->addSql('ALTER TABLE contracts DROP deposit_amount_currency');
        $this->addSql('ALTER TABLE contracts DROP deposit_amount_value');
    }
}
