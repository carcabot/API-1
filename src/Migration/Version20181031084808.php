<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181031084808 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE country_calendars ADD enabled BOOLEAN');
        $this->addSql('UPDATE country_calendars SET enabled = false');
        $this->addSql('ALTER TABLE country_calendars ALTER enabled SET NOT NULL');
        $this->addSql('ALTER TABLE opening_hours_specifications ALTER closes DROP NOT NULL');
        $this->addSql('ALTER TABLE opening_hours_specifications ALTER day_of_week TYPE VARCHAR(254)');
        $this->addSql('COMMENT ON COLUMN opening_hours_specifications.day_of_week IS \'(DC2Type:day_of_week_enum)\'');
        $this->addSql('ALTER TABLE opening_hours_specifications ALTER opens DROP NOT NULL');
        $this->addSql('ALTER TABLE opening_hours_specifications ALTER valid_from DROP NOT NULL');
        $this->addSql('ALTER TABLE opening_hours_specifications ALTER valid_through DROP NOT NULL');
        $this->addSql('CREATE SEQUENCE service_level_agreement_actions_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE service_level_agreement_actions (id INT NOT NULL, ticket_id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, end_time TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, status VARCHAR(254) NOT NULL, start_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, value_max_value NUMERIC(19, 4) DEFAULT NULL, value_min_value NUMERIC(19, 4) DEFAULT NULL, value_unit_code TEXT DEFAULT NULL, value_value NUMERIC(19, 4) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B049A850700047D2 ON service_level_agreement_actions (ticket_id)');
        $this->addSql('CREATE INDEX IDX_B049A8503414710B ON service_level_agreement_actions (agent_id)');
        $this->addSql('CREATE INDEX IDX_B049A85061220EA6 ON service_level_agreement_actions (creator_id)');
        $this->addSql('COMMENT ON COLUMN service_level_agreement_actions.status IS \'(DC2Type:sla_action_status_enum)\'');
        $this->addSql('ALTER TABLE service_level_agreement_actions ADD CONSTRAINT FK_B049A850700047D2 FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_level_agreement_actions ADD CONSTRAINT FK_B049A8503414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_level_agreement_actions ADD CONSTRAINT FK_B049A85061220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tickets ALTER planned_completion_date DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE country_calendars DROP enabled');
        $this->addSql('ALTER TABLE opening_hours_specifications ALTER closes SET NOT NULL');
        $this->addSql('ALTER TABLE opening_hours_specifications ALTER day_of_week TYPE TEXT');
        $this->addSql('ALTER TABLE opening_hours_specifications ALTER opens SET NOT NULL');
        $this->addSql('ALTER TABLE opening_hours_specifications ALTER valid_from SET NOT NULL');
        $this->addSql('ALTER TABLE opening_hours_specifications ALTER valid_through SET NOT NULL');
        $this->addSql('DROP SEQUENCE service_level_agreement_actions_id_seq CASCADE');
        $this->addSql('DROP TABLE service_level_agreement_actions');
        $this->addSql('ALTER TABLE tickets ALTER planned_completion_date SET NOT NULL');
    }
}
