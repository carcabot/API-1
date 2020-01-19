<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190221123450 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_requests ADD acquirer_code VARCHAR(254) DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD acquirer_name VARCHAR(254) DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD customer_of VARCHAR(254) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_requests DROP acquirer_code');
        $this->addSql('ALTER TABLE application_requests DROP acquirer_name');
        $this->addSql('ALTER TABLE application_requests DROP customer_of');
    }
}
