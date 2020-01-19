<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181011114349 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_requests ADD submitter_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD date_submitted TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD external_application_request_number TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD CONSTRAINT FK_C4BE0942919E5513 FOREIGN KEY (submitter_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C4BE0942919E5513 ON application_requests (submitter_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_requests DROP CONSTRAINT FK_C4BE0942919E5513');
        $this->addSql('DROP INDEX IDX_C4BE0942919E5513');
        $this->addSql('ALTER TABLE application_requests DROP submitter_id');
        $this->addSql('ALTER TABLE application_requests DROP date_submitted');
        $this->addSql('ALTER TABLE application_requests DROP external_application_request_number');
    }
}
