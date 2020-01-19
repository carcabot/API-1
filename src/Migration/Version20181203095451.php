<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181203095451 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE partner_commission_statements DROP CONSTRAINT FK_99FDAF2693CB796C');
        $this->addSql('ALTER TABLE partner_commission_statements ADD CONSTRAINT FK_99FDAF2693CB796C FOREIGN KEY (file_id) REFERENCES internal_documents (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE partner_commission_statements DROP CONSTRAINT fk_99fdaf2693cb796c');
        $this->addSql('ALTER TABLE partner_commission_statements ADD CONSTRAINT fk_99fdaf2693cb796c FOREIGN KEY (file_id) REFERENCES digital_documents (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
