<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180906122028 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE addon_services ADD is_based_on_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE addon_services ADD CONSTRAINT FK_928E16BD6E363BB FOREIGN KEY (is_based_on_id) REFERENCES addon_services (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_928E16BD6E363BB ON addon_services (is_based_on_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE addon_services DROP CONSTRAINT FK_928E16BD6E363BB');
        $this->addSql('DROP INDEX IDX_928E16BD6E363BB');
        $this->addSql('ALTER TABLE addon_services DROP is_based_on_id');
    }
}
