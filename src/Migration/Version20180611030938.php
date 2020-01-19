<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180611030938 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE partner_commission_statement_data_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE partner_commission_statements_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE partner_commission_statement_data (id INT NOT NULL, application_request_id INT DEFAULT NULL, commission_rate_id INT NOT NULL, lead_id INT DEFAULT NULL, statement_id INT NOT NULL, type VARCHAR(254) NOT NULL, amount_currency VARCHAR(3) DEFAULT NULL, amount_value NUMERIC(19, 4) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D67E6FC935FD8949 ON partner_commission_statement_data (application_request_id)');
        $this->addSql('CREATE INDEX IDX_D67E6FC9154C794F ON partner_commission_statement_data (commission_rate_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D67E6FC955458D ON partner_commission_statement_data (lead_id)');
        $this->addSql('CREATE INDEX IDX_D67E6FC9849CB65B ON partner_commission_statement_data (statement_id)');
        $this->addSql('COMMENT ON COLUMN partner_commission_statement_data.type IS \'(DC2Type:commission_statement_data_type_enum)\'');
        $this->addSql('CREATE TABLE partner_commission_statements (id INT NOT NULL, file_id INT DEFAULT NULL, partner_id INT NOT NULL, payment_receipt_id INT DEFAULT NULL, bank_account_number TEXT DEFAULT NULL, bank_name TEXT DEFAULT NULL, confirmation_number TEXT DEFAULT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, payment_method TEXT DEFAULT NULL, payment_status VARCHAR(254) NOT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status VARCHAR(254) NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, total_payment_due_currency VARCHAR(3) DEFAULT NULL, total_payment_due_value NUMERIC(19, 4) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_99FDAF2693CB796C ON partner_commission_statements (file_id)');
        $this->addSql('CREATE INDEX IDX_99FDAF269393F8FE ON partner_commission_statements (partner_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_99FDAF261EAA2943 ON partner_commission_statements (payment_receipt_id)');
        $this->addSql('COMMENT ON COLUMN partner_commission_statements.payment_status IS \'(DC2Type:payment_status_enum)\'');
        $this->addSql('COMMENT ON COLUMN partner_commission_statements.status IS \'(DC2Type:commission_statement_status_enum)\'');
        $this->addSql('ALTER TABLE partner_commission_statement_data ADD CONSTRAINT FK_D67E6FC935FD8949 FOREIGN KEY (application_request_id) REFERENCES application_requests (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE partner_commission_statement_data ADD CONSTRAINT FK_D67E6FC9154C794F FOREIGN KEY (commission_rate_id) REFERENCES commission_rates (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE partner_commission_statement_data ADD CONSTRAINT FK_D67E6FC955458D FOREIGN KEY (lead_id) REFERENCES leads (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE partner_commission_statement_data ADD CONSTRAINT FK_D67E6FC9849CB65B FOREIGN KEY (statement_id) REFERENCES partner_commission_statements (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE partner_commission_statements ADD CONSTRAINT FK_99FDAF2693CB796C FOREIGN KEY (file_id) REFERENCES digital_documents (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE partner_commission_statements ADD CONSTRAINT FK_99FDAF269393F8FE FOREIGN KEY (partner_id) REFERENCES partners (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE partner_commission_statements ADD CONSTRAINT FK_99FDAF261EAA2943 FOREIGN KEY (payment_receipt_id) REFERENCES digital_documents (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE application_requests ADD lead_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD CONSTRAINT FK_C4BE094255458D FOREIGN KEY (lead_id) REFERENCES leads (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C4BE094255458D ON application_requests (lead_id)');
        $this->addSql('ALTER TABLE commission_rates ADD is_based_on_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commission_rates ADD CONSTRAINT FK_A95B9AF6E363BB FOREIGN KEY (is_based_on_id) REFERENCES commission_rates (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_A95B9AF6E363BB ON commission_rates (is_based_on_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE partner_commission_statement_data DROP CONSTRAINT FK_D67E6FC9849CB65B');
        $this->addSql('DROP SEQUENCE partner_commission_statement_data_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE partner_commission_statements_id_seq CASCADE');
        $this->addSql('DROP TABLE partner_commission_statement_data');
        $this->addSql('DROP TABLE partner_commission_statements');
        $this->addSql('ALTER TABLE application_requests DROP CONSTRAINT FK_C4BE094255458D');
        $this->addSql('DROP INDEX IDX_C4BE094255458D');
        $this->addSql('ALTER TABLE application_requests DROP lead_id');
        $this->addSql('ALTER TABLE commission_rates DROP CONSTRAINT FK_A95B9AF6E363BB');
        $this->addSql('DROP INDEX IDX_A95B9AF6E363BB');
        $this->addSql('ALTER TABLE commission_rates DROP is_based_on_id');
    }
}
