<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180827110108 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE point_credits_exchange_rates ADD remark TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ALTER remark TYPE TEXT');
        $this->addSql('ALTER TABLE application_requests ALTER remark DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_requests ALTER remark TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE application_requests ALTER remark DROP DEFAULT');
        $this->addSql('ALTER TABLE point_credits_exchange_rates DROP remark');
    }
}
