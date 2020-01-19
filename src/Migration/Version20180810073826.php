<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180810073826 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE expire_credits_use_histories_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE expire_credits_use_histories (id INT NOT NULL, expire_action_id INT NOT NULL, use_action_id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, use_amount NUMERIC(19, 4) NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F4655DEDFA97253 ON expire_credits_use_histories (expire_action_id)');
        $this->addSql('CREATE INDEX IDX_F4655DEE9DCD935 ON expire_credits_use_histories (use_action_id)');
        $this->addSql('CREATE INDEX IDX_F4655DE3414710B ON expire_credits_use_histories (agent_id)');
        $this->addSql('CREATE INDEX IDX_F4655DE61220EA6 ON expire_credits_use_histories (creator_id)');
        $this->addSql('CREATE TABLE earn_contract_affiliate_credits_actions (id INT NOT NULL, object_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3F7803A4232D562B ON earn_contract_affiliate_credits_actions (object_id)');
        $this->addSql('CREATE TABLE earn_contract_affiliate_credits_transactions (earn_contract_affiliate_credits_action_id INT NOT NULL, affiliate_program_transaction_id INT NOT NULL, PRIMARY KEY(earn_contract_affiliate_credits_action_id, affiliate_program_transaction_id))');
        $this->addSql('CREATE INDEX IDX_E82696D1DD0CCFFB ON earn_contract_affiliate_credits_transactions (earn_contract_affiliate_credits_action_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E82696D1495C0824 ON earn_contract_affiliate_credits_transactions (affiliate_program_transaction_id)');
        $this->addSql('CREATE TABLE earn_customer_affiliate_credits_actions (id INT NOT NULL, object_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5C805CC5232D562B ON earn_customer_affiliate_credits_actions (object_id)');
        $this->addSql('CREATE TABLE earn_customer_affiliate_credits_transactions (earn_customer_affiliate_credits_action_id INT NOT NULL, affiliate_program_transaction_id INT NOT NULL, PRIMARY KEY(earn_customer_affiliate_credits_action_id, affiliate_program_transaction_id))');
        $this->addSql('CREATE INDEX IDX_2A1DCDD45A3EBA80 ON earn_customer_affiliate_credits_transactions (earn_customer_affiliate_credits_action_id)');
        $this->addSql('CREATE INDEX IDX_2A1DCDD4495C0824 ON earn_customer_affiliate_credits_transactions (affiliate_program_transaction_id)');
        $this->addSql('CREATE TABLE expire_customer_credits_actions_expire_credits_use_histories (expire_customer_credits_action_id INT NOT NULL, expire_credits_use_history_id INT NOT NULL, PRIMARY KEY(expire_customer_credits_action_id, expire_credits_use_history_id))');
        $this->addSql('CREATE INDEX IDX_65CC85B6C00BD85C ON expire_customer_credits_actions_expire_credits_use_histories (expire_customer_credits_action_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_65CC85B630713E48 ON expire_customer_credits_actions_expire_credits_use_histories (expire_credits_use_history_id)');
        $this->addSql('CREATE TABLE expire_contract_credits_actions_expire_credits_use_histories (expire_contract_credits_action_id INT NOT NULL, expire_credits_use_history_id INT NOT NULL, PRIMARY KEY(expire_contract_credits_action_id, expire_credits_use_history_id))');
        $this->addSql('CREATE INDEX IDX_935264B214FDF2A9 ON expire_contract_credits_actions_expire_credits_use_histories (expire_contract_credits_action_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_935264B230713E48 ON expire_contract_credits_actions_expire_credits_use_histories (expire_credits_use_history_id)');
        $this->addSql('ALTER TABLE expire_credits_use_histories ADD CONSTRAINT FK_F4655DEDFA97253 FOREIGN KEY (expire_action_id) REFERENCES update_credits_actions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE expire_credits_use_histories ADD CONSTRAINT FK_F4655DEE9DCD935 FOREIGN KEY (use_action_id) REFERENCES update_credits_actions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE expire_credits_use_histories ADD CONSTRAINT FK_F4655DE3414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE expire_credits_use_histories ADD CONSTRAINT FK_F4655DE61220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE earn_contract_affiliate_credits_actions ADD CONSTRAINT FK_3F7803A4232D562B FOREIGN KEY (object_id) REFERENCES contracts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE earn_contract_affiliate_credits_actions ADD CONSTRAINT FK_3F7803A4BF396750 FOREIGN KEY (id) REFERENCES update_credits_actions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE earn_contract_affiliate_credits_transactions ADD CONSTRAINT FK_E82696D1DD0CCFFB FOREIGN KEY (earn_contract_affiliate_credits_action_id) REFERENCES earn_contract_affiliate_credits_actions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE earn_contract_affiliate_credits_transactions ADD CONSTRAINT FK_E82696D1495C0824 FOREIGN KEY (affiliate_program_transaction_id) REFERENCES affiliate_program_transactions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE earn_customer_affiliate_credits_actions ADD CONSTRAINT FK_5C805CC5232D562B FOREIGN KEY (object_id) REFERENCES customer_accounts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE earn_customer_affiliate_credits_actions ADD CONSTRAINT FK_5C805CC5BF396750 FOREIGN KEY (id) REFERENCES update_credits_actions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE earn_customer_affiliate_credits_transactions ADD CONSTRAINT FK_2A1DCDD45A3EBA80 FOREIGN KEY (earn_customer_affiliate_credits_action_id) REFERENCES earn_customer_affiliate_credits_actions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE earn_customer_affiliate_credits_transactions ADD CONSTRAINT FK_2A1DCDD4495C0824 FOREIGN KEY (affiliate_program_transaction_id) REFERENCES affiliate_program_transactions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE expire_customer_credits_actions_expire_credits_use_histories ADD CONSTRAINT FK_65CC85B6C00BD85C FOREIGN KEY (expire_customer_credits_action_id) REFERENCES expire_customer_credits_actions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE expire_customer_credits_actions_expire_credits_use_histories ADD CONSTRAINT FK_65CC85B630713E48 FOREIGN KEY (expire_credits_use_history_id) REFERENCES expire_credits_use_histories (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE expire_contract_credits_actions_expire_credits_use_histories ADD CONSTRAINT FK_935264B214FDF2A9 FOREIGN KEY (expire_contract_credits_action_id) REFERENCES expire_contract_credits_actions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE expire_contract_credits_actions_expire_credits_use_histories ADD CONSTRAINT FK_935264B230713E48 FOREIGN KEY (expire_credits_use_history_id) REFERENCES expire_credits_use_histories (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE point_credits_exchange_rates ADD is_based_on_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE point_credits_exchange_rates ADD valid_from TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE point_credits_exchange_rates ADD valid_through TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE point_credits_exchange_rates ADD CONSTRAINT FK_599CAF686E363BB FOREIGN KEY (is_based_on_id) REFERENCES point_credits_exchange_rates (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_599CAF686E363BB ON point_credits_exchange_rates (is_based_on_id)');
        $this->addSql('ALTER TABLE affiliate_program_transactions ADD affiliate_program_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE affiliate_program_transactions ADD money_commission_configuration_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE affiliate_program_transactions ADD point_commission_configuration_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE affiliate_program_transactions ADD point_credits_exchange_rate_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE affiliate_program_transactions ADD transaction_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE affiliate_program_transactions ALTER customer_id DROP NOT NULL');
        $this->addSql('ALTER TABLE affiliate_program_transactions ADD CONSTRAINT FK_ADFA76CDF374FF7 FOREIGN KEY (affiliate_program_id) REFERENCES affiliate_programs (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE affiliate_program_transactions ADD CONSTRAINT FK_ADFA76CD50C29A5F FOREIGN KEY (money_commission_configuration_id) REFERENCES affiliate_program_commission_configurations (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE affiliate_program_transactions ADD CONSTRAINT FK_ADFA76CDC6011472 FOREIGN KEY (point_commission_configuration_id) REFERENCES affiliate_program_commission_configurations (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE affiliate_program_transactions ADD CONSTRAINT FK_ADFA76CD6523DE9E FOREIGN KEY (point_credits_exchange_rate_id) REFERENCES point_credits_exchange_rates (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_ADFA76CDF374FF7 ON affiliate_program_transactions (affiliate_program_id)');
        $this->addSql('CREATE INDEX IDX_ADFA76CD50C29A5F ON affiliate_program_transactions (money_commission_configuration_id)');
        $this->addSql('CREATE INDEX IDX_ADFA76CDC6011472 ON affiliate_program_transactions (point_commission_configuration_id)');
        $this->addSql('CREATE INDEX IDX_ADFA76CD6523DE9E ON affiliate_program_transactions (point_credits_exchange_rate_id)');
        $this->addSql('ALTER TABLE affiliate_program_commission_configurations ADD is_based_on_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE affiliate_program_commission_configurations ADD CONSTRAINT FK_15A492826E363BB FOREIGN KEY (is_based_on_id) REFERENCES affiliate_program_commission_configurations (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_15A492826E363BB ON affiliate_program_commission_configurations (is_based_on_id)');
        $this->addSql('ALTER TABLE expire_customer_credits_actions ADD amount_used NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE expire_contract_credits_actions ADD amount_used NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE credits_transactions ALTER credits_transaction_number DROP NOT NULL');
        $this->addSql('ALTER TABLE affiliate_program_commission_configurations ALTER provider DROP NOT NULL');
        $this->addSql('ALTER TABLE affiliate_program_transaction_fetch_histories ADD pending_conversions_max_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE affiliate_program_transaction_fetch_histories ADD pending_conversions_min_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE affiliate_program_transaction_fetch_histories ADD pending_conversions_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE affiliate_program_transaction_fetch_histories DROP conversions_max_value');
        $this->addSql('ALTER TABLE affiliate_program_transaction_fetch_histories DROP conversions_min_value');
        $this->addSql('ALTER TABLE affiliate_program_transaction_fetch_histories DROP conversions_value');
        $this->addSql('ALTER TABLE affiliate_program_transaction_fetch_histories RENAME COLUMN conversions_unit_code TO pending_conversions_unit_code');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE expire_customer_credits_actions_expire_credits_use_histories DROP CONSTRAINT FK_65CC85B630713E48');
        $this->addSql('ALTER TABLE expire_contract_credits_actions_expire_credits_use_histories DROP CONSTRAINT FK_935264B230713E48');
        $this->addSql('ALTER TABLE earn_contract_affiliate_credits_transactions DROP CONSTRAINT FK_E82696D1DD0CCFFB');
        $this->addSql('ALTER TABLE earn_customer_affiliate_credits_transactions DROP CONSTRAINT FK_2A1DCDD45A3EBA80');
        $this->addSql('DROP SEQUENCE expire_credits_use_histories_id_seq CASCADE');
        $this->addSql('DROP TABLE expire_credits_use_histories');
        $this->addSql('DROP TABLE earn_contract_affiliate_credits_actions');
        $this->addSql('DROP TABLE earn_contract_affiliate_credits_transactions');
        $this->addSql('DROP TABLE earn_customer_affiliate_credits_actions');
        $this->addSql('DROP TABLE earn_customer_affiliate_credits_transactions');
        $this->addSql('DROP TABLE expire_customer_credits_actions_expire_credits_use_histories');
        $this->addSql('DROP TABLE expire_contract_credits_actions_expire_credits_use_histories');
        $this->addSql('ALTER TABLE affiliate_program_commission_configurations DROP CONSTRAINT FK_15A492826E363BB');
        $this->addSql('DROP INDEX IDX_15A492826E363BB');
        $this->addSql('ALTER TABLE affiliate_program_commission_configurations DROP is_based_on_id');
        $this->addSql('ALTER TABLE affiliate_program_transactions DROP CONSTRAINT FK_ADFA76CDF374FF7');
        $this->addSql('ALTER TABLE affiliate_program_transactions DROP CONSTRAINT FK_ADFA76CD50C29A5F');
        $this->addSql('ALTER TABLE affiliate_program_transactions DROP CONSTRAINT FK_ADFA76CDC6011472');
        $this->addSql('ALTER TABLE affiliate_program_transactions DROP CONSTRAINT FK_ADFA76CD6523DE9E');
        $this->addSql('DROP INDEX IDX_ADFA76CDF374FF7');
        $this->addSql('DROP INDEX IDX_ADFA76CD50C29A5F');
        $this->addSql('DROP INDEX IDX_ADFA76CDC6011472');
        $this->addSql('DROP INDEX IDX_ADFA76CD6523DE9E');
        $this->addSql('ALTER TABLE affiliate_program_transactions DROP affiliate_program_id');
        $this->addSql('ALTER TABLE affiliate_program_transactions DROP money_commission_configuration_id');
        $this->addSql('ALTER TABLE affiliate_program_transactions DROP point_commission_configuration_id');
        $this->addSql('ALTER TABLE affiliate_program_transactions DROP point_credits_exchange_rate_id');
        $this->addSql('ALTER TABLE affiliate_program_transactions DROP transaction_date');
        $this->addSql('ALTER TABLE affiliate_program_transactions ALTER customer_id SET NOT NULL');
        $this->addSql('ALTER TABLE point_credits_exchange_rates DROP CONSTRAINT FK_599CAF686E363BB');
        $this->addSql('DROP INDEX IDX_599CAF686E363BB');
        $this->addSql('ALTER TABLE point_credits_exchange_rates DROP is_based_on_id');
        $this->addSql('ALTER TABLE point_credits_exchange_rates DROP valid_from');
        $this->addSql('ALTER TABLE point_credits_exchange_rates DROP valid_through');
        $this->addSql('ALTER TABLE expire_customer_credits_actions DROP amount_used');
        $this->addSql('ALTER TABLE expire_contract_credits_actions DROP amount_used');
        $this->addSql('ALTER TABLE affiliate_program_transaction_fetch_histories ADD conversions_max_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE affiliate_program_transaction_fetch_histories ADD conversions_min_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE affiliate_program_transaction_fetch_histories ADD conversions_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE affiliate_program_transaction_fetch_histories DROP pending_conversions_max_value');
        $this->addSql('ALTER TABLE affiliate_program_transaction_fetch_histories DROP pending_conversions_min_value');
        $this->addSql('ALTER TABLE affiliate_program_transaction_fetch_histories DROP pending_conversions_value');
        $this->addSql('ALTER TABLE affiliate_program_transaction_fetch_histories RENAME COLUMN pending_conversions_unit_code TO conversions_unit_code');
        $this->addSql('ALTER TABLE affiliate_program_commission_configurations ALTER provider SET NOT NULL');
        $this->addSql('ALTER TABLE credits_transactions ALTER credits_transaction_number SET NOT NULL');
    }
}
