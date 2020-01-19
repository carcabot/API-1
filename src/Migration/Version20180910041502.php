<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180910041502 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE contracts ADD contact_person_id INT');
        $this->addSql(<<<'SQL'
            UPDATE contracts SET contact_person_id = customer_id WHERE id IN (
                SELECT app.id from contracts app JOIN customer_accounts c ON c.id = app.customer_id JOIN people p ON p.id = c.person_details_id
            )
SQL
        );

        $this->addSql(<<<'SQL'
            WITH customer_data AS (
                SELECT co.id AS corporate_id, c.id AS individual_id
                FROM customer_accounts co
                LEFT JOIN corporations corp ON co.corporation_details_id = corp.id
                JOIN corporations_employee_roles cer ON cer.corporation_id = corp.id
                JOIN employee_roles e ON cer.employee_role_id = e.id AND e.role_name = 'CONTACT_PERSON'
                JOIN customer_accounts c ON c.id = e.employee_id
                JOIN people p ON p.id = c.person_details_id
            )
            UPDATE contracts SET contact_person_id = individual_id
            FROM customer_data
            WHERE customer_id = corporate_id AND id IN (
                SELECT app.id FROM contracts app LEFT JOIN customer_accounts c ON c.id = app.customer_id JOIN people p ON p.id = c.corporation_details_id
            )
SQL
        );

        $this->addSql('ALTER TABLE contracts ALTER contact_person_id SET NOT NULL');
        $this->addSql('ALTER TABLE contracts ADD corporation_details_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contracts ADD customer_type VARCHAR(254)');
        $this->addSql('COMMENT ON COLUMN contracts.customer_type IS \'(DC2Type:account_type_enum)\'');

        $this->addSql(<<<'SQL'
            WITH contract_customer_type AS (
                SELECT c.id AS contract_id, ca.type AS customer_account_type
                FROM contracts c
                JOIN customer_accounts ca ON c.customer_id = ca.id
            )
            UPDATE contracts SET customer_type = customer_account_type
            FROM contract_customer_type
            WHERE id = contract_id
SQL
        );

        $this->addSql('ALTER TABLE contracts ALTER customer_type SET NOT NULL');
        $this->addSql('ALTER TABLE contracts ADD person_details_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contracts ADD CONSTRAINT FK_950A9734F8A983C FOREIGN KEY (contact_person_id) REFERENCES customer_accounts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE contracts ADD CONSTRAINT FK_950A97342F6D1D6 FOREIGN KEY (corporation_details_id) REFERENCES corporations (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE contracts ADD CONSTRAINT FK_950A9738DA16437 FOREIGN KEY (person_details_id) REFERENCES people (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_950A9734F8A983C ON contracts (contact_person_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_950A97342F6D1D6 ON contracts (corporation_details_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_950A9738DA16437 ON contracts (person_details_id)');
        $this->addSql('ALTER TABLE application_requests ADD contact_person_id INT');

        $this->addSql(<<<'SQL'
            UPDATE application_requests SET contact_person_id = customer_id WHERE id IN (
                SELECT app.id from application_requests app JOIN customer_accounts c ON c.id = app.customer_id JOIN people p ON p.id = c.person_details_id
            )
SQL
        );

        $this->addSql(<<<'SQL'
            WITH customer_data AS (
                SELECT co.id AS CORPORATE_ID, corp.id AS CORPORATION_ID, c.id AS INDIVIDUAL_ID, p.id AS PEOPLE_ID
                FROM customer_accounts co
                LEFT JOIN corporations corp ON co.corporation_details_id = corp.id
                JOIN corporations_employee_roles cer ON cer.corporation_id = corp.id
                JOIN employee_roles e ON cer.employee_role_id = e.id AND e.role_name = 'CONTACT_PERSON'
                JOIN customer_accounts c ON c.id = e.employee_id
                JOIN people p ON p.id = c.person_details_id
            )
            UPDATE application_requests SET contact_person_id = individual_id
            FROM customer_data
            WHERE customer_id = corporate_id AND id IN (
                SELECT app.id FROM application_requests app LEFT JOIN customer_accounts c ON c.id = app.customer_id JOIN people p ON p.id = c.corporation_details_id
            )
SQL
        );

        $this->addSql('ALTER TABLE application_requests ADD corporation_details_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD customer_type VARCHAR(254)');
        $this->addSql('COMMENT ON COLUMN application_requests.customer_type IS \'(DC2Type:account_type_enum)\'');

        $this->addSql(<<<'SQL'
            WITH application_requests_customer_type AS (
                SELECT ap.id AS application_request_id, ca.type AS customer_account_type
                FROM application_requests ap
                JOIN customer_accounts ca ON ap.customer_id = ca.id
            )
            UPDATE application_requests SET customer_type = customer_account_type
            FROM application_requests_customer_type
            WHERE id = application_request_id
SQL
        );

        $this->addSql('ALTER TABLE application_requests ADD person_details_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD CONSTRAINT FK_C4BE09424F8A983C FOREIGN KEY (contact_person_id) REFERENCES customer_accounts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE application_requests ADD CONSTRAINT FK_C4BE094242F6D1D6 FOREIGN KEY (corporation_details_id) REFERENCES corporations (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE application_requests ADD CONSTRAINT FK_C4BE09428DA16437 FOREIGN KEY (person_details_id) REFERENCES people (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C4BE09424F8A983C ON application_requests (contact_person_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C4BE094242F6D1D6 ON application_requests (corporation_details_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C4BE09428DA16437 ON application_requests (person_details_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_requests DROP CONSTRAINT FK_C4BE09424F8A983C');
        $this->addSql('ALTER TABLE application_requests DROP CONSTRAINT FK_C4BE094242F6D1D6');
        $this->addSql('ALTER TABLE application_requests DROP CONSTRAINT FK_C4BE09428DA16437');
        $this->addSql('DROP INDEX IDX_C4BE09424F8A983C');
        $this->addSql('DROP INDEX UNIQ_C4BE094242F6D1D6');
        $this->addSql('DROP INDEX UNIQ_C4BE09428DA16437');
        $this->addSql('ALTER TABLE application_requests DROP contact_person_id');
        $this->addSql('ALTER TABLE application_requests DROP corporation_details_id');
        $this->addSql('ALTER TABLE application_requests DROP customer_type');
        $this->addSql('ALTER TABLE application_requests DROP person_details_id');
        $this->addSql('ALTER TABLE contracts DROP CONSTRAINT FK_950A9734F8A983C');
        $this->addSql('ALTER TABLE contracts DROP CONSTRAINT FK_950A97342F6D1D6');
        $this->addSql('ALTER TABLE contracts DROP CONSTRAINT FK_950A9738DA16437');
        $this->addSql('DROP INDEX IDX_950A9734F8A983C');
        $this->addSql('DROP INDEX UNIQ_950A97342F6D1D6');
        $this->addSql('DROP INDEX UNIQ_950A9738DA16437');
        $this->addSql('ALTER TABLE contracts DROP contact_person_id');
        $this->addSql('ALTER TABLE contracts DROP corporation_details_id');
        $this->addSql('ALTER TABLE contracts DROP customer_type');
        $this->addSql('ALTER TABLE contracts DROP person_details_id');
    }
}
