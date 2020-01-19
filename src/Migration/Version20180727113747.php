<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180727113747 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE payments_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE payments (id INT NOT NULL, payment_receipt_id INT DEFAULT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, bank_account_holder_name TEXT DEFAULT NULL, bank_account_number TEXT DEFAULT NULL, bank_code TEXT DEFAULT NULL, bank_name TEXT DEFAULT NULL, confirmation_number TEXT DEFAULT NULL, payment_method TEXT DEFAULT NULL, payment_number VARCHAR(128) NOT NULL, status VARCHAR(254) NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, amount_currency VARCHAR(3) DEFAULT NULL, amount_value NUMERIC(19, 4) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_65D29B32B3A884C2 ON payments (payment_number)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_65D29B321EAA2943 ON payments (payment_receipt_id)');
        $this->addSql('CREATE INDEX IDX_65D29B323414710B ON payments (agent_id)');
        $this->addSql('CREATE INDEX IDX_65D29B3261220EA6 ON payments (creator_id)');
        $this->addSql('COMMENT ON COLUMN payments.status IS \'(DC2Type:payment_status_enum)\'');
        $this->addSql('CREATE TABLE money_credits_transactions_payments (money_credits_transaction_id INT NOT NULL, payment_id INT NOT NULL, PRIMARY KEY(money_credits_transaction_id, payment_id))');
        $this->addSql('CREATE INDEX IDX_5D3C620FC6262A46 ON money_credits_transactions_payments (money_credits_transaction_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5D3C620F4C3A3BB ON money_credits_transactions_payments (payment_id)');
        $this->addSql('CREATE TABLE withdraw_credits_actions (id INT NOT NULL, object_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5663374D232D562B ON withdraw_credits_actions (object_id)');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B321EAA2943 FOREIGN KEY (payment_receipt_id) REFERENCES digital_documents (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B323414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B3261220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE money_credits_transactions_payments ADD CONSTRAINT FK_5D3C620FC6262A46 FOREIGN KEY (money_credits_transaction_id) REFERENCES money_credits_transactions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE money_credits_transactions_payments ADD CONSTRAINT FK_5D3C620F4C3A3BB FOREIGN KEY (payment_id) REFERENCES payments (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE withdraw_credits_actions ADD CONSTRAINT FK_5663374D232D562B FOREIGN KEY (object_id) REFERENCES customer_accounts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE withdraw_credits_actions ADD CONSTRAINT FK_5663374DBF396750 FOREIGN KEY (id) REFERENCES update_credits_actions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE credits_transactions ALTER credits_transaction_number TYPE VARCHAR(128)');
        $this->addSql('ALTER TABLE credits_transactions ALTER credits_transaction_number DROP DEFAULT');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C14DCC84CE5A633F ON credits_transactions (credits_transaction_number)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE money_credits_transactions_payments DROP CONSTRAINT FK_5D3C620F4C3A3BB');
        $this->addSql('DROP SEQUENCE payments_id_seq CASCADE');
        $this->addSql('DROP TABLE payments');
        $this->addSql('DROP TABLE money_credits_transactions_payments');
        $this->addSql('DROP TABLE withdraw_credits_actions');
        $this->addSql('DROP INDEX UNIQ_C14DCC84CE5A633F');
        $this->addSql('ALTER TABLE credits_transactions ALTER credits_transaction_number TYPE TEXT');
        $this->addSql('ALTER TABLE credits_transactions ALTER credits_transaction_number DROP DEFAULT');
        $this->addSql('ALTER TABLE credits_transactions ALTER credits_transaction_number TYPE TEXT');
    }
}
