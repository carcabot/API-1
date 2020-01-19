<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190108094958 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_requests ADD refundee_details_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD CONSTRAINT FK_C4BE09428E6A2051 FOREIGN KEY (refundee_details_id) REFERENCES people (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C4BE09428E6A2051 ON application_requests (refundee_details_id)');
        $this->addSql('ALTER TABLE contracts ADD refundee_details_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contracts ADD CONSTRAINT FK_950A9738E6A2051 FOREIGN KEY (refundee_details_id) REFERENCES people (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_950A9738E6A2051 ON contracts (refundee_details_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_requests DROP CONSTRAINT FK_C4BE09428E6A2051');
        $this->addSql('DROP INDEX UNIQ_C4BE09428E6A2051');
        $this->addSql('ALTER TABLE application_requests DROP refundee_details_id');
        $this->addSql('ALTER TABLE contracts DROP CONSTRAINT FK_950A9738E6A2051');
        $this->addSql('DROP INDEX UNIQ_950A9738E6A2051');
        $this->addSql('ALTER TABLE contracts DROP refundee_details_id');
    }
}
