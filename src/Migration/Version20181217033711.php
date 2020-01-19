<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181217033711 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE tariff_daily_rates_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE tariff_daily_rates (id INT NOT NULL, tariff_rate_id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, valid_from TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, valid_through TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, rate_max_value NUMERIC(19, 4) DEFAULT NULL, rate_min_value NUMERIC(19, 4) DEFAULT NULL, rate_unit_code TEXT DEFAULT NULL, rate_value NUMERIC(19, 4) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_686ABDD4C86DC06 ON tariff_daily_rates (tariff_rate_id)');
        $this->addSql('CREATE INDEX IDX_686ABDD3414710B ON tariff_daily_rates (agent_id)');
        $this->addSql('CREATE INDEX IDX_686ABDD61220EA6 ON tariff_daily_rates (creator_id)');
        $this->addSql('ALTER TABLE tariff_daily_rates ADD CONSTRAINT FK_686ABDD4C86DC06 FOREIGN KEY (tariff_rate_id) REFERENCES tariff_rates (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tariff_daily_rates ADD CONSTRAINT FK_686ABDD3414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tariff_daily_rates ADD CONSTRAINT FK_686ABDD61220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tariff_rates ADD is_daily_rate BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE tariff_rates ADD type VARCHAR(254) DEFAULT NULL');
        $this->addSql('UPDATE tariff_rates SET type=\'NORMAL\'');
        $this->addSql('ALTER TABLE tariff_rates ALTER type SET NOT NULL');
        $this->addSql('COMMENT ON COLUMN tariff_rates.type IS \'(DC2Type:tariff_rate_type_enum)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE tariff_daily_rates_id_seq CASCADE');
        $this->addSql('DROP TABLE tariff_daily_rates');
        $this->addSql('ALTER TABLE tariff_rates DROP is_daily_rate');
        $this->addSql('ALTER TABLE tariff_rates DROP type');
    }
}
