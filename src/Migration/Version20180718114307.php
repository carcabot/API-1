<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180718114307 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE point_credits_exchange_rates_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE affiliate_program_transactions_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE affiliate_program_commission_configurations_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE affiliate_program_transaction_fetch_histories_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE point_credits_exchange_rates (id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, value NUMERIC(19, 4) NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, base_amount_currency VARCHAR(3) DEFAULT NULL, base_amount_value NUMERIC(19, 4) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_599CAF683414710B ON point_credits_exchange_rates (agent_id)');
        $this->addSql('CREATE INDEX IDX_599CAF6861220EA6 ON point_credits_exchange_rates (creator_id)');
        $this->addSql('CREATE TABLE affiliate_program_transactions (id INT NOT NULL, customer_id INT NOT NULL, commission_status VARCHAR(254) NOT NULL, provider VARCHAR(254) NOT NULL, transaction_number TEXT NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, commission_amount_currency VARCHAR(3) DEFAULT NULL, commission_amount_value NUMERIC(19, 4) DEFAULT NULL, money_credits_amount_currency VARCHAR(3) DEFAULT NULL, money_credits_amount_value NUMERIC(19, 4) DEFAULT NULL, order_amount_currency VARCHAR(3) DEFAULT NULL, order_amount_value NUMERIC(19, 4) DEFAULT NULL, point_credits_amount_max_value NUMERIC(19, 4) DEFAULT NULL, point_credits_amount_min_value NUMERIC(19, 4) DEFAULT NULL, point_credits_amount_unit_code TEXT DEFAULT NULL, point_credits_amount_value NUMERIC(19, 4) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_ADFA76CD9395C3F3 ON affiliate_program_transactions (customer_id)');
        $this->addSql('COMMENT ON COLUMN affiliate_program_transactions.commission_status IS \'(DC2Type:affiliate_commission_status_enum)\'');
        $this->addSql('COMMENT ON COLUMN affiliate_program_transactions.provider IS \'(DC2Type:affiliate_web_service_partner_enum)\'');
        $this->addSql('CREATE TABLE affiliate_program_commission_configurations (id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, allocation_type VARCHAR(254) NOT NULL, currency VARCHAR(255) DEFAULT NULL, provider VARCHAR(254) NOT NULL, type VARCHAR(254) NOT NULL, value VARCHAR(255) NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_15A492823414710B ON affiliate_program_commission_configurations (agent_id)');
        $this->addSql('CREATE INDEX IDX_15A4928261220EA6 ON affiliate_program_commission_configurations (creator_id)');
        $this->addSql('COMMENT ON COLUMN affiliate_program_commission_configurations.allocation_type IS \'(DC2Type:commission_allocation_enum)\'');
        $this->addSql('COMMENT ON COLUMN affiliate_program_commission_configurations.provider IS \'(DC2Type:affiliate_web_service_partner_enum)\'');
        $this->addSql('COMMENT ON COLUMN affiliate_program_commission_configurations.type IS \'(DC2Type:commission_type_enum)\'');
        $this->addSql('CREATE TABLE affiliate_program_transaction_fetch_histories (id INT NOT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, provider VARCHAR(254) NOT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, conversions_max_value NUMERIC(19, 4) DEFAULT NULL, conversions_min_value NUMERIC(19, 4) DEFAULT NULL, conversions_unit_code TEXT DEFAULT NULL, conversions_value NUMERIC(19, 4) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN affiliate_program_transaction_fetch_histories.provider IS \'(DC2Type:affiliate_web_service_partner_enum)\'');
        $this->addSql('ALTER TABLE point_credits_exchange_rates ADD CONSTRAINT FK_599CAF683414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE point_credits_exchange_rates ADD CONSTRAINT FK_599CAF6861220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE affiliate_program_transactions ADD CONSTRAINT FK_ADFA76CD9395C3F3 FOREIGN KEY (customer_id) REFERENCES customer_accounts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE affiliate_program_commission_configurations ADD CONSTRAINT FK_15A492823414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE affiliate_program_commission_configurations ADD CONSTRAINT FK_15A4928261220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE point_credits_exchange_rates_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE affiliate_program_transactions_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE affiliate_program_commission_configurations_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE affiliate_program_transaction_fetch_histories_id_seq CASCADE');
        $this->addSql('DROP TABLE point_credits_exchange_rates');
        $this->addSql('DROP TABLE affiliate_program_transactions');
        $this->addSql('DROP TABLE affiliate_program_commission_configurations');
        $this->addSql('DROP TABLE affiliate_program_transaction_fetch_histories');
    }
}
