<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180611081819 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE list_items_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE item_lists_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE list_items (id INT NOT NULL, description TEXT DEFAULT NULL, name VARCHAR(255) NOT NULL, position INT DEFAULT NULL, value VARCHAR(255) DEFAULT NULL, entity_type VARCHAR(128) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE application_request_list_items (id INT NOT NULL, item_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_79C97E95126F525E ON application_request_list_items (item_id)');
        $this->addSql('CREATE TABLE customer_account_list_items (id INT NOT NULL, item_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A704F2CE126F525E ON customer_account_list_items (item_id)');
        $this->addSql('CREATE TABLE item_lists (id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, description TEXT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, type VARCHAR(254) NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_228184753414710B ON item_lists (agent_id)');
        $this->addSql('CREATE INDEX IDX_2281847561220EA6 ON item_lists (creator_id)');
        $this->addSql('COMMENT ON COLUMN item_lists.type IS \'(DC2Type:item_list_type_enum)\'');
        $this->addSql('CREATE TABLE item_lists_list_items (item_list_id INT NOT NULL, list_item_id INT NOT NULL, PRIMARY KEY(item_list_id, list_item_id))');
        $this->addSql('CREATE INDEX IDX_F120ABDB36F330DF ON item_lists_list_items (item_list_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F120ABDBCE208F53 ON item_lists_list_items (list_item_id)');
        $this->addSql('CREATE TABLE lead_list_items (id INT NOT NULL, item_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_101666D2126F525E ON lead_list_items (item_id)');
        $this->addSql('ALTER TABLE application_request_list_items ADD CONSTRAINT FK_79C97E95126F525E FOREIGN KEY (item_id) REFERENCES application_requests (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE application_request_list_items ADD CONSTRAINT FK_79C97E95BF396750 FOREIGN KEY (id) REFERENCES list_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE customer_account_list_items ADD CONSTRAINT FK_A704F2CE126F525E FOREIGN KEY (item_id) REFERENCES customer_accounts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE customer_account_list_items ADD CONSTRAINT FK_A704F2CEBF396750 FOREIGN KEY (id) REFERENCES list_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE item_lists ADD CONSTRAINT FK_228184753414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE item_lists ADD CONSTRAINT FK_2281847561220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE item_lists_list_items ADD CONSTRAINT FK_F120ABDB36F330DF FOREIGN KEY (item_list_id) REFERENCES item_lists (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE item_lists_list_items ADD CONSTRAINT FK_F120ABDBCE208F53 FOREIGN KEY (list_item_id) REFERENCES list_items (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE lead_list_items ADD CONSTRAINT FK_101666D2126F525E FOREIGN KEY (item_id) REFERENCES leads (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE lead_list_items ADD CONSTRAINT FK_101666D2BF396750 FOREIGN KEY (id) REFERENCES list_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_request_list_items DROP CONSTRAINT FK_79C97E95BF396750');
        $this->addSql('ALTER TABLE customer_account_list_items DROP CONSTRAINT FK_A704F2CEBF396750');
        $this->addSql('ALTER TABLE item_lists_list_items DROP CONSTRAINT FK_F120ABDBCE208F53');
        $this->addSql('ALTER TABLE lead_list_items DROP CONSTRAINT FK_101666D2BF396750');
        $this->addSql('ALTER TABLE item_lists_list_items DROP CONSTRAINT FK_F120ABDB36F330DF');
        $this->addSql('DROP SEQUENCE list_items_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE item_lists_id_seq CASCADE');
        $this->addSql('DROP TABLE list_items');
        $this->addSql('DROP TABLE application_request_list_items');
        $this->addSql('DROP TABLE customer_account_list_items');
        $this->addSql('DROP TABLE item_lists');
        $this->addSql('DROP TABLE item_lists_list_items');
        $this->addSql('DROP TABLE lead_list_items');
    }
}
