<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190729020922 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE messages_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE message_templates_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE message_recipient_list_items (id INT NOT NULL, customer_id INT DEFAULT NULL, message_id INT DEFAULT NULL, message_addresses JSONB NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_28A00C9D9395C3F3 ON message_recipient_list_items (customer_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_28A00C9D537A1329 ON message_recipient_list_items (message_id)');
        $this->addSql('CREATE TABLE messages (id INT NOT NULL, message_template_id INT DEFAULT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, date_read TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_received TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_sent TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, response_message VARCHAR(255) DEFAULT NULL, status VARCHAR(254) DEFAULT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DB021E9665A55141 ON messages (message_template_id)');
        $this->addSql('CREATE INDEX IDX_DB021E963414710B ON messages (agent_id)');
        $this->addSql('CREATE INDEX IDX_DB021E9661220EA6 ON messages (creator_id)');
        $this->addSql('COMMENT ON COLUMN messages.status IS \'(DC2Type:message_status_enum)\'');
        $this->addSql('CREATE TABLE message_templates (id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, body VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, message_number VARCHAR(255) NOT NULL, planned_end_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, planned_start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, recipients_filters JSONB DEFAULT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, title VARCHAR(255) NOT NULL, status VARCHAR(254) NOT NULL, type VARCHAR(254) NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, entity_type VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5917BB8A1B7825B ON message_templates (message_number)');
        $this->addSql('CREATE INDEX IDX_5917BB83414710B ON message_templates (agent_id)');
        $this->addSql('CREATE INDEX IDX_5917BB861220EA6 ON message_templates (creator_id)');
        $this->addSql('COMMENT ON COLUMN message_templates.status IS \'(DC2Type:message_status_enum)\'');
        $this->addSql('COMMENT ON COLUMN message_templates.type IS \'(DC2Type:message_type_enum)\'');
        $this->addSql('CREATE TABLE message_templates_digital_documents (message_id INT NOT NULL, digital_document_id INT NOT NULL, PRIMARY KEY(message_id, digital_document_id))');
        $this->addSql('CREATE INDEX IDX_B92F8D80537A1329 ON message_templates_digital_documents (message_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B92F8D801F859883 ON message_templates_digital_documents (digital_document_id)');
        $this->addSql('CREATE TABLE message_templates_message_recipient_list_items (message_id INT NOT NULL, recipient_list_item_id INT NOT NULL, PRIMARY KEY(message_id, recipient_list_item_id))');
        $this->addSql('CREATE INDEX IDX_3078E214537A1329 ON message_templates_message_recipient_list_items (message_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3078E214E9950BA5 ON message_templates_message_recipient_list_items (recipient_list_item_id)');
        $this->addSql('ALTER TABLE message_recipient_list_items ADD CONSTRAINT FK_28A00C9D9395C3F3 FOREIGN KEY (customer_id) REFERENCES customer_accounts (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_recipient_list_items ADD CONSTRAINT FK_28A00C9D537A1329 FOREIGN KEY (message_id) REFERENCES messages (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_recipient_list_items ADD CONSTRAINT FK_28A00C9DBF396750 FOREIGN KEY (id) REFERENCES list_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E9665A55141 FOREIGN KEY (message_template_id) REFERENCES message_templates (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E963414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E9661220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_templates ADD CONSTRAINT FK_5917BB83414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_templates ADD CONSTRAINT FK_5917BB861220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_templates_digital_documents ADD CONSTRAINT FK_B92F8D80537A1329 FOREIGN KEY (message_id) REFERENCES message_templates (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_templates_digital_documents ADD CONSTRAINT FK_B92F8D801F859883 FOREIGN KEY (digital_document_id) REFERENCES digital_documents (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_templates_message_recipient_list_items ADD CONSTRAINT FK_3078E214537A1329 FOREIGN KEY (message_id) REFERENCES message_templates (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_templates_message_recipient_list_items ADD CONSTRAINT FK_3078E214E9950BA5 FOREIGN KEY (recipient_list_item_id) REFERENCES message_recipient_list_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE message_templates_message_recipient_list_items DROP CONSTRAINT FK_3078E214E9950BA5');
        $this->addSql('ALTER TABLE message_recipient_list_items DROP CONSTRAINT FK_28A00C9D537A1329');
        $this->addSql('ALTER TABLE messages DROP CONSTRAINT FK_DB021E9665A55141');
        $this->addSql('ALTER TABLE message_templates_digital_documents DROP CONSTRAINT FK_B92F8D80537A1329');
        $this->addSql('ALTER TABLE message_templates_message_recipient_list_items DROP CONSTRAINT FK_3078E214537A1329');
        $this->addSql('DROP SEQUENCE messages_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE message_templates_id_seq CASCADE');
        $this->addSql('CREATE TABLE import_listing_data (uen_nric VARCHAR(100) NOT NULL, customerid VARCHAR(100) DEFAULT NULL, customercategory VARCHAR(100) DEFAULT NULL, customertype VARCHAR(100) DEFAULT NULL, source VARCHAR(100) DEFAULT NULL, companyname VARCHAR(100) DEFAULT NULL, salutation VARCHAR(100) DEFAULT NULL, firstname VARCHAR(100) DEFAULT NULL, middlename VARCHAR(100) DEFAULT NULL, lastname VARCHAR(100) DEFAULT NULL, fullname VARCHAR(100) DEFAULT NULL, preferredcontactmethod VARCHAR(100) DEFAULT NULL, mobileno VARCHAR(100) DEFAULT NULL, phoneno VARCHAR(100) DEFAULT NULL, faxno VARCHAR(100) DEFAULT NULL, email VARCHAR(100) DEFAULT NULL, referralcode VARCHAR(100) DEFAULT NULL, gender VARCHAR(100) DEFAULT NULL, maritalstatus VARCHAR(100) DEFAULT NULL, dateofbirth VARCHAR(100) DEFAULT NULL, placeofbirth VARCHAR(100) DEFAULT NULL, dateofdeath VARCHAR(100) DEFAULT NULL, nationality VARCHAR(100) DEFAULT NULL, race VARCHAR(100) DEFAULT NULL, languages VARCHAR(100) DEFAULT NULL, preferredlanguage VARCHAR(100) DEFAULT NULL, status VARCHAR(100) DEFAULT NULL, createddate_time VARCHAR(100) DEFAULT NULL, PRIMARY KEY(uen_nric))');
        $this->addSql('DROP TABLE message_recipient_list_items');
        $this->addSql('DROP TABLE messages');
        $this->addSql('DROP TABLE message_templates');
        $this->addSql('DROP TABLE message_templates_digital_documents');
        $this->addSql('DROP TABLE message_templates_message_recipient_list_items');
    }
}
