<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191007101547 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE maintenance_configurations_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE maintenance_configurations (id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, about TEXT NOT NULL, description TEXT DEFAULT NULL, planned_end_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, planned_start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status VARCHAR(254) NOT NULL, text TEXT NOT NULL, used_in JSONB NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F59DDCF83414710B ON maintenance_configurations (agent_id)');
        $this->addSql('CREATE INDEX IDX_F59DDCF861220EA6 ON maintenance_configurations (creator_id)');
        $this->addSql('COMMENT ON COLUMN maintenance_configurations.status IS \'(DC2Type:maintenance_configuration_status_enum)\'');
        $this->addSql('ALTER TABLE maintenance_configurations ADD CONSTRAINT FK_F59DDCF83414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE maintenance_configurations ADD CONSTRAINT FK_F59DDCF861220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE maintenance_configurations_id_seq CASCADE');
        $this->addSql('DROP TABLE maintenance_configurations');
    }
}
