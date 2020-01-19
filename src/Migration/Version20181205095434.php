<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181205095434 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE campaigns ADD file_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE campaigns ADD CONSTRAINT FK_E373747093CB796C FOREIGN KEY (file_id) REFERENCES internal_documents (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E373747093CB796C ON campaigns (file_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE campaigns DROP CONSTRAINT FK_E373747093CB796C');
        $this->addSql('DROP INDEX UNIQ_E373747093CB796C');
        $this->addSql('ALTER TABLE campaigns DROP file_id');
    }
}
