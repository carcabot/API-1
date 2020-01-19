<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190410024954 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE tickets ADD paused BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE tickets ADD time_left_max_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE tickets ADD time_left_min_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE tickets ADD time_left_unit_code TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE tickets ADD time_left_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE tickets ADD timer_max_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE tickets ADD timer_min_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE tickets ADD timer_unit_code TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE tickets ADD timer_value NUMERIC(19, 4) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE tickets DROP paused');
        $this->addSql('ALTER TABLE tickets DROP time_left_max_value');
        $this->addSql('ALTER TABLE tickets DROP time_left_min_value');
        $this->addSql('ALTER TABLE tickets DROP time_left_unit_code');
        $this->addSql('ALTER TABLE tickets DROP time_left_value');
        $this->addSql('ALTER TABLE tickets DROP timer_max_value');
        $this->addSql('ALTER TABLE tickets DROP timer_min_value');
        $this->addSql('ALTER TABLE tickets DROP timer_unit_code');
        $this->addSql('ALTER TABLE tickets DROP timer_value');
    }
}
