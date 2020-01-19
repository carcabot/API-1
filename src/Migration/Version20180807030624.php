<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180807030624 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE contracts_addon_services (contract_id INT NOT NULL, addon_service_id INT NOT NULL, PRIMARY KEY(contract_id, addon_service_id))');
        $this->addSql('CREATE INDEX IDX_2DB49E7D2576E0FD ON contracts_addon_services (contract_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2DB49E7D2BD12FFE ON contracts_addon_services (addon_service_id)');
        $this->addSql('CREATE TABLE application_requests_addon_services (application_request_id INT NOT NULL, addon_service_id INT NOT NULL, PRIMARY KEY(application_request_id, addon_service_id))');
        $this->addSql('CREATE INDEX IDX_8C8B597935FD8949 ON application_requests_addon_services (application_request_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8C8B59792BD12FFE ON application_requests_addon_services (addon_service_id)');
        $this->addSql('ALTER TABLE contracts_addon_services ADD CONSTRAINT FK_2DB49E7D2576E0FD FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE contracts_addon_services ADD CONSTRAINT FK_2DB49E7D2BD12FFE FOREIGN KEY (addon_service_id) REFERENCES addon_services (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE application_requests_addon_services ADD CONSTRAINT FK_8C8B597935FD8949 FOREIGN KEY (application_request_id) REFERENCES application_requests (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE application_requests_addon_services ADD CONSTRAINT FK_8C8B59792BD12FFE FOREIGN KEY (addon_service_id) REFERENCES addon_services (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE offers ALTER image_id DROP NOT NULL');
        $this->addSql('ALTER TABLE customer_accounts ADD image_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_accounts ADD CONSTRAINT FK_E60DDD833DA5256D FOREIGN KEY (image_id) REFERENCES digital_documents (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E60DDD833DA5256D ON customer_accounts (image_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE contracts_addon_services');
        $this->addSql('DROP TABLE application_requests_addon_services');
        $this->addSql('ALTER TABLE offers ALTER image_id SET NOT NULL');
        $this->addSql('ALTER TABLE customer_accounts DROP CONSTRAINT FK_E60DDD833DA5256D');
        $this->addSql('DROP INDEX UNIQ_E60DDD833DA5256D');
        $this->addSql('ALTER TABLE customer_accounts DROP image_id');
    }
}
