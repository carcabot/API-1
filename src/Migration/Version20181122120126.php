<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181122120126 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service_level_agreements DROP CONSTRAINT fk_a7e27640ee98d9b9');
        $this->addSql('DROP SEQUENCE service_level_agreement_timers_id_seq CASCADE');
        $this->addSql('DROP TABLE service_level_agreement_timers');
        $this->addSql('DROP INDEX idx_a7e27640ee98d9b9');
        $this->addSql('ALTER TABLE service_level_agreements ADD timer_max_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE service_level_agreements ADD timer_min_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE service_level_agreements ADD timer_unit_code TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_level_agreements ADD timer_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE service_level_agreements DROP timer_id');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE service_level_agreement_timers_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE service_level_agreement_timers (id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, description TEXT DEFAULT NULL, name TEXT NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, value_max_value NUMERIC(19, 4) DEFAULT NULL, value_min_value NUMERIC(19, 4) DEFAULT NULL, value_unit_code TEXT DEFAULT NULL, value_value NUMERIC(19, 4) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_3790681261220ea6 ON service_level_agreement_timers (creator_id)');
        $this->addSql('CREATE INDEX idx_379068123414710b ON service_level_agreement_timers (agent_id)');
        $this->addSql('ALTER TABLE service_level_agreement_timers ADD CONSTRAINT fk_3790681261220ea6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_level_agreement_timers ADD CONSTRAINT fk_379068123414710b FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_level_agreements ADD timer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_level_agreements DROP timer_max_value');
        $this->addSql('ALTER TABLE service_level_agreements DROP timer_min_value');
        $this->addSql('ALTER TABLE service_level_agreements DROP timer_unit_code');
        $this->addSql('ALTER TABLE service_level_agreements DROP timer_value');
        $this->addSql('ALTER TABLE service_level_agreements ADD CONSTRAINT fk_a7e27640ee98d9b9 FOREIGN KEY (timer_id) REFERENCES service_level_agreement_timers (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_a7e27640ee98d9b9 ON service_level_agreements (timer_id)');
    }
}
