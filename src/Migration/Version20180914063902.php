<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180914063902 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE audit_associations (id SERIAL NOT NULL, typ VARCHAR(128) NOT NULL, tbl VARCHAR(128) DEFAULT NULL, label VARCHAR(255) DEFAULT NULL, fk VARCHAR(255) NOT NULL, class VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE audit_logs (id SERIAL NOT NULL, source_id INT NOT NULL, target_id INT DEFAULT NULL, blame_id INT DEFAULT NULL, action VARCHAR(12) NOT NULL, tbl VARCHAR(128) NOT NULL, diff JSON DEFAULT NULL, logged_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D62F2858953C1C61 ON audit_logs (source_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D62F2858158E0B66 ON audit_logs (target_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D62F28588C082A2E ON audit_logs (blame_id)');
        $this->addSql('COMMENT ON COLUMN audit_logs.diff IS \'(DC2Type:json_array)\'');
        $this->addSql('ALTER TABLE audit_logs ADD CONSTRAINT FK_D62F2858953C1C61 FOREIGN KEY (source_id) REFERENCES audit_associations (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE audit_logs ADD CONSTRAINT FK_D62F2858158E0B66 FOREIGN KEY (target_id) REFERENCES audit_associations (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE audit_logs ADD CONSTRAINT FK_D62F28588C082A2E FOREIGN KEY (blame_id) REFERENCES audit_associations (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE audit_logs DROP CONSTRAINT FK_D62F2858953C1C61');
        $this->addSql('ALTER TABLE audit_logs DROP CONSTRAINT FK_D62F2858158E0B66');
        $this->addSql('ALTER TABLE audit_logs DROP CONSTRAINT FK_D62F28588C082A2E');
        $this->addSql('DROP TABLE audit_associations');
        $this->addSql('DROP TABLE audit_logs');
    }
}
