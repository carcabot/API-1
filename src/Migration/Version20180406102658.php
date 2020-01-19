<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20180406102658 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE web_pages ADD position INT DEFAULT NULL');
        $this->addSql('ALTER TABLE web_pages ADD return_url TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE tariff_rates ALTER remark TYPE TEXT');
        $this->addSql('ALTER TABLE tariff_rates ALTER remark DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE web_pages DROP position');
        $this->addSql('ALTER TABLE web_pages DROP return_url');
        $this->addSql('ALTER TABLE tariff_rates ALTER remark TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE tariff_rates ALTER remark DROP DEFAULT');
    }
}
