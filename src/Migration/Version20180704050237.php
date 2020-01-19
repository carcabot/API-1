<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180704050237 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE contracts ADD name TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE people ADD country_of_origin TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE people ADD marital_status VARCHAR(254) DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN people.marital_status IS \'(DC2Type:marital_status_enum)\'');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE people DROP country_of_origin');
        $this->addSql('ALTER TABLE people DROP marital_status');
        $this->addSql('ALTER TABLE contracts DROP name');
    }
}
