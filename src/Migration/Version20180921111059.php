<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180921111059 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE internal_documents_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE internal_documents (id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, content_path TEXT DEFAULT NULL, name TEXT DEFAULT NULL, text TEXT DEFAULT NULL, type VARCHAR(254) NOT NULL, url TEXT DEFAULT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2A778AE33414710B ON internal_documents (agent_id)');
        $this->addSql('CREATE INDEX IDX_2A778AE361220EA6 ON internal_documents (creator_id)');
        $this->addSql('COMMENT ON COLUMN internal_documents.type IS \'(DC2Type:document_type_enum)\'');
        $this->addSql('ALTER TABLE internal_documents ADD CONSTRAINT FK_2A778AE33414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE internal_documents ADD CONSTRAINT FK_2A778AE361220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE internal_documents_id_seq CASCADE');
        $this->addSql('DROP TABLE internal_documents');
    }
}
