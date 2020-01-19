<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180803093247 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE campaigns_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE campaign_expectations_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE campaign_categories_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE campaign_templates_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE unsubscribe_reasons_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE campaign_objectives_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE campaign_source_list_items (id INT NOT NULL, customer_id INT DEFAULT NULL, lead_id INT DEFAULT NULL, source VARCHAR(254) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E8B09789395C3F3 ON campaign_source_list_items (customer_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E8B097855458D ON campaign_source_list_items (lead_id)');
        $this->addSql('COMMENT ON COLUMN campaign_source_list_items.source IS \'(DC2Type:campaign_source_type_enum)\'');
        $this->addSql('CREATE TABLE sms_campaign_source_list_items (id INT NOT NULL, mobile_number VARCHAR(35) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN sms_campaign_source_list_items.mobile_number IS \'(DC2Type:phone_number)\'');
        $this->addSql('CREATE TABLE campaigns (id INT NOT NULL, assignee_id INT NOT NULL, expectation_list_id INT DEFAULT NULL, objective_id INT DEFAULT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, category VARCHAR(254) NOT NULL, current_stage VARCHAR(254) NOT NULL, description TEXT DEFAULT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, note TEXT DEFAULT NULL, priority VARCHAR(254) NOT NULL, planned_end_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, planned_start_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, status VARCHAR(254) NOT NULL, tariff_rate VARCHAR(255) DEFAULT NULL, template VARCHAR(255) DEFAULT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, actual_cost_currency VARCHAR(3) DEFAULT NULL, actual_cost_value NUMERIC(19, 4) DEFAULT NULL, estimated_cost_currency VARCHAR(3) DEFAULT NULL, estimated_cost_value NUMERIC(19, 4) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E373747059EC7D60 ON campaigns (assignee_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E373747091225532 ON campaigns (expectation_list_id)');
        $this->addSql('CREATE INDEX IDX_E373747073484933 ON campaigns (objective_id)');
        $this->addSql('CREATE INDEX IDX_E37374703414710B ON campaigns (agent_id)');
        $this->addSql('CREATE INDEX IDX_E373747061220EA6 ON campaigns (creator_id)');
        $this->addSql('COMMENT ON COLUMN campaigns.category IS \'(DC2Type:campaign_category_enum)\'');
        $this->addSql('COMMENT ON COLUMN campaigns.current_stage IS \'(DC2Type:campaign_stage_enum)\'');
        $this->addSql('COMMENT ON COLUMN campaigns.priority IS \'(DC2Type:campaign_priority_enum)\'');
        $this->addSql('COMMENT ON COLUMN campaigns.status IS \'(DC2Type:campaign_status_enum)\'');
        $this->addSql('CREATE TABLE campaigns_source_lists (campaign_id INT NOT NULL, source_list_id INT NOT NULL, PRIMARY KEY(campaign_id, source_list_id))');
        $this->addSql('CREATE INDEX IDX_EDD862BCF639F774 ON campaigns_source_lists (campaign_id)');
        $this->addSql('CREATE INDEX IDX_EDD862BC7471AEE3 ON campaigns_source_lists (source_list_id)');
        $this->addSql('CREATE TABLE source_lists (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE campaign_expectation_lists (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE campaign_expectations (id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8CA665273414710B ON campaign_expectations (agent_id)');
        $this->addSql('CREATE INDEX IDX_8CA6652761220EA6 ON campaign_expectations (creator_id)');
        $this->addSql('CREATE TABLE unsubscribe_list_items (id INT NOT NULL, item_id INT NOT NULL, unsubscribe_reason_id INT DEFAULT NULL, email VARCHAR(255) NOT NULL, note TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_39B9BF50126F525E ON unsubscribe_list_items (item_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_39B9BF50B6080DD3 ON unsubscribe_list_items (unsubscribe_reason_id)');
        $this->addSql('CREATE TABLE campaign_categories (id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FDE466C03414710B ON campaign_categories (agent_id)');
        $this->addSql('CREATE INDEX IDX_FDE466C061220EA6 ON campaign_categories (creator_id)');
        $this->addSql('CREATE TABLE email_campaign_source_list_items (id INT NOT NULL, email_address VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE unsubscribe_lists (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE campaign_templates (id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, template TEXT NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_AFA061F53414710B ON campaign_templates (agent_id)');
        $this->addSql('CREATE INDEX IDX_AFA061F561220EA6 ON campaign_templates (creator_id)');
        $this->addSql('CREATE TABLE unsubscribe_reasons (id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, description TEXT DEFAULT NULL, name VARCHAR(255) NOT NULL, require_note BOOLEAN NOT NULL, status VARCHAR(254) NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1CD3B2723414710B ON unsubscribe_reasons (agent_id)');
        $this->addSql('CREATE INDEX IDX_1CD3B27261220EA6 ON unsubscribe_reasons (creator_id)');
        $this->addSql('COMMENT ON COLUMN unsubscribe_reasons.status IS \'(DC2Type:unsubscribe_reason_status_enum)\'');
        $this->addSql('CREATE TABLE direct_mail_source_list_items (id INT NOT NULL, item_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CDB258E4126F525E ON direct_mail_source_list_items (item_id)');
        $this->addSql('CREATE TABLE campaign_objectives (id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_ABA749C43414710B ON campaign_objectives (agent_id)');
        $this->addSql('CREATE INDEX IDX_ABA749C461220EA6 ON campaign_objectives (creator_id)');
        $this->addSql('CREATE TABLE campaign_expectation_list_items (id INT NOT NULL, item_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2CEAD291126F525E ON campaign_expectation_list_items (item_id)');
        $this->addSql('ALTER TABLE campaign_source_list_items ADD CONSTRAINT FK_E8B09789395C3F3 FOREIGN KEY (customer_id) REFERENCES customer_accounts (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_source_list_items ADD CONSTRAINT FK_E8B097855458D FOREIGN KEY (lead_id) REFERENCES leads (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_source_list_items ADD CONSTRAINT FK_E8B0978BF396750 FOREIGN KEY (id) REFERENCES list_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sms_campaign_source_list_items ADD CONSTRAINT FK_F812E038BF396750 FOREIGN KEY (id) REFERENCES list_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaigns ADD CONSTRAINT FK_E373747059EC7D60 FOREIGN KEY (assignee_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaigns ADD CONSTRAINT FK_E373747091225532 FOREIGN KEY (expectation_list_id) REFERENCES campaign_expectation_lists (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaigns ADD CONSTRAINT FK_E373747073484933 FOREIGN KEY (objective_id) REFERENCES campaign_objectives (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaigns ADD CONSTRAINT FK_E37374703414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaigns ADD CONSTRAINT FK_E373747061220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaigns_source_lists ADD CONSTRAINT FK_EDD862BCF639F774 FOREIGN KEY (campaign_id) REFERENCES campaigns (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaigns_source_lists ADD CONSTRAINT FK_EDD862BC7471AEE3 FOREIGN KEY (source_list_id) REFERENCES source_lists (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE source_lists ADD CONSTRAINT FK_912E449ABF396750 FOREIGN KEY (id) REFERENCES item_lists (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_expectation_lists ADD CONSTRAINT FK_4D925858BF396750 FOREIGN KEY (id) REFERENCES item_lists (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_expectations ADD CONSTRAINT FK_8CA665273414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_expectations ADD CONSTRAINT FK_8CA6652761220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE unsubscribe_list_items ADD CONSTRAINT FK_39B9BF50126F525E FOREIGN KEY (item_id) REFERENCES campaigns (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE unsubscribe_list_items ADD CONSTRAINT FK_39B9BF50B6080DD3 FOREIGN KEY (unsubscribe_reason_id) REFERENCES unsubscribe_reasons (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE unsubscribe_list_items ADD CONSTRAINT FK_39B9BF50BF396750 FOREIGN KEY (id) REFERENCES list_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_categories ADD CONSTRAINT FK_FDE466C03414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_categories ADD CONSTRAINT FK_FDE466C061220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE email_campaign_source_list_items ADD CONSTRAINT FK_29F289C0BF396750 FOREIGN KEY (id) REFERENCES list_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE unsubscribe_lists ADD CONSTRAINT FK_B7767DFFBF396750 FOREIGN KEY (id) REFERENCES item_lists (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_templates ADD CONSTRAINT FK_AFA061F53414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_templates ADD CONSTRAINT FK_AFA061F561220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE unsubscribe_reasons ADD CONSTRAINT FK_1CD3B2723414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE unsubscribe_reasons ADD CONSTRAINT FK_1CD3B27261220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE direct_mail_source_list_items ADD CONSTRAINT FK_CDB258E4126F525E FOREIGN KEY (item_id) REFERENCES postal_addresses (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE direct_mail_source_list_items ADD CONSTRAINT FK_CDB258E4BF396750 FOREIGN KEY (id) REFERENCES list_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_objectives ADD CONSTRAINT FK_ABA749C43414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_objectives ADD CONSTRAINT FK_ABA749C461220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_expectation_list_items ADD CONSTRAINT FK_2CEAD291126F525E FOREIGN KEY (item_id) REFERENCES campaign_expectations (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_expectation_list_items ADD CONSTRAINT FK_2CEAD291BF396750 FOREIGN KEY (id) REFERENCES list_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE campaigns_source_lists DROP CONSTRAINT FK_EDD862BCF639F774');
        $this->addSql('ALTER TABLE unsubscribe_list_items DROP CONSTRAINT FK_39B9BF50126F525E');
        $this->addSql('ALTER TABLE campaigns_source_lists DROP CONSTRAINT FK_EDD862BC7471AEE3');
        $this->addSql('ALTER TABLE campaigns DROP CONSTRAINT FK_E373747091225532');
        $this->addSql('ALTER TABLE campaign_expectation_list_items DROP CONSTRAINT FK_2CEAD291126F525E');
        $this->addSql('ALTER TABLE unsubscribe_list_items DROP CONSTRAINT FK_39B9BF50B6080DD3');
        $this->addSql('ALTER TABLE campaigns DROP CONSTRAINT FK_E373747073484933');
        $this->addSql('DROP SEQUENCE campaigns_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE campaign_expectations_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE campaign_categories_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE campaign_templates_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE unsubscribe_reasons_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE campaign_objectives_id_seq CASCADE');
        $this->addSql('DROP TABLE campaign_source_list_items');
        $this->addSql('DROP TABLE sms_campaign_source_list_items');
        $this->addSql('DROP TABLE campaigns');
        $this->addSql('DROP TABLE campaigns_source_lists');
        $this->addSql('DROP TABLE source_lists');
        $this->addSql('DROP TABLE campaign_expectation_lists');
        $this->addSql('DROP TABLE campaign_expectations');
        $this->addSql('DROP TABLE unsubscribe_list_items');
        $this->addSql('DROP TABLE campaign_categories');
        $this->addSql('DROP TABLE email_campaign_source_list_items');
        $this->addSql('DROP TABLE unsubscribe_lists');
        $this->addSql('DROP TABLE campaign_templates');
        $this->addSql('DROP TABLE unsubscribe_reasons');
        $this->addSql('DROP TABLE direct_mail_source_list_items');
        $this->addSql('DROP TABLE campaign_objectives');
        $this->addSql('DROP TABLE campaign_expectation_list_items');
    }
}
