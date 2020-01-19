<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191010082538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_requests ADD advisory_notice_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD CONSTRAINT FK_C4BE09428C842A64 FOREIGN KEY (advisory_notice_id) REFERENCES advisory_notices (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C4BE09428C842A64 ON application_requests (advisory_notice_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_requests DROP CONSTRAINT FK_C4BE09428C842A64');
        $this->addSql('DROP INDEX IDX_C4BE09428C842A64');
        $this->addSql('ALTER TABLE application_requests DROP advisory_notice_id');
    }
}
