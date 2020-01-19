<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180925112546 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE campaigns DROP CONSTRAINT fk_e373747073484933');
        $this->addSql('DROP INDEX idx_e373747073484933');
        $this->addSql('ALTER TABLE campaigns ADD objective TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE campaigns DROP objective_id');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE campaigns ADD objective_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE campaigns DROP objective');
        $this->addSql('ALTER TABLE campaigns ADD CONSTRAINT fk_e373747073484933 FOREIGN KEY (objective_id) REFERENCES campaign_objectives (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_e373747073484933 ON campaigns (objective_id)');
    }
}
