<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181121072447 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE contracts_internal_documents (contract_id INT NOT NULL, file_id INT NOT NULL, PRIMARY KEY(contract_id, file_id))');
        $this->addSql('CREATE INDEX IDX_CD6B5EB92576E0FD ON contracts_internal_documents (contract_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CD6B5EB993CB796C ON contracts_internal_documents (file_id)');
        $this->addSql('ALTER TABLE contracts_internal_documents ADD CONSTRAINT FK_CD6B5EB92576E0FD FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE contracts_internal_documents ADD CONSTRAINT FK_CD6B5EB993CB796C FOREIGN KEY (file_id) REFERENCES internal_documents (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE internal_documents ADD owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE internal_documents ADD CONSTRAINT FK_2A778AE37E3C61F9 FOREIGN KEY (owner_id) REFERENCES customer_accounts (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_2A778AE37E3C61F9 ON internal_documents (owner_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE contracts_internal_documents');
        $this->addSql('ALTER TABLE internal_documents DROP CONSTRAINT FK_2A778AE37E3C61F9');
        $this->addSql('DROP INDEX IDX_2A778AE37E3C61F9');
        $this->addSql('ALTER TABLE internal_documents DROP owner_id');
    }
}
