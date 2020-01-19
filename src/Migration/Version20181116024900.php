<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181116024900 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE advisory_notices_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE advisory_notices (id INT NOT NULL, file_id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, description TEXT DEFAULT NULL, valid_from TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, valid_through TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6919119193CB796C ON advisory_notices (file_id)');
        $this->addSql('CREATE INDEX IDX_691911913414710B ON advisory_notices (agent_id)');
        $this->addSql('CREATE INDEX IDX_6919119161220EA6 ON advisory_notices (creator_id)');
        $this->addSql('ALTER TABLE advisory_notices ADD CONSTRAINT FK_6919119193CB796C FOREIGN KEY (file_id) REFERENCES digital_documents (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE advisory_notices ADD CONSTRAINT FK_691911913414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE advisory_notices ADD CONSTRAINT FK_6919119161220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE advisory_notices_id_seq CASCADE');
        $this->addSql('DROP TABLE advisory_notices');
    }
}
