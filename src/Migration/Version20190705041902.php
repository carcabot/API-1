<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190705041902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE promotions_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE promotion_categories_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE tariff_rates_promotions (tariff_rate_id INT NOT NULL, promotion_id INT NOT NULL, PRIMARY KEY(tariff_rate_id, promotion_id))');
        $this->addSql('CREATE INDEX IDX_5B84F0C64C86DC06 ON tariff_rates_promotions (tariff_rate_id)');
        $this->addSql('CREATE INDEX IDX_5B84F0C6139DF194 ON tariff_rates_promotions (promotion_id)');
        $this->addSql('CREATE TABLE promotions (id INT NOT NULL, category_id INT NOT NULL, is_based_on_id INT DEFAULT NULL, page_id INT DEFAULT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, contract_types JSONB NOT NULL, currency VARCHAR(3) DEFAULT NULL, customer_types JSONB NOT NULL, name TEXT DEFAULT NULL, promotion_number VARCHAR(128) NOT NULL, status VARCHAR(254) NOT NULL, valid_from TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, valid_through TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, amount_max_value NUMERIC(19, 4) DEFAULT NULL, amount_min_value NUMERIC(19, 4) DEFAULT NULL, amount_unit_code TEXT DEFAULT NULL, amount_value NUMERIC(19, 4) DEFAULT NULL, inventory_level_max_value NUMERIC(19, 4) DEFAULT NULL, inventory_level_min_value NUMERIC(19, 4) DEFAULT NULL, inventory_level_unit_code TEXT DEFAULT NULL, inventory_level_value NUMERIC(19, 4) DEFAULT NULL, recurring_duration_max_value NUMERIC(19, 4) DEFAULT NULL, recurring_duration_min_value NUMERIC(19, 4) DEFAULT NULL, recurring_duration_unit_code TEXT DEFAULT NULL, recurring_duration_value NUMERIC(19, 4) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_EA1B303412469DE2 ON promotions (category_id)');
        $this->addSql('CREATE INDEX IDX_EA1B30346E363BB ON promotions (is_based_on_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EA1B3034C4663E4 ON promotions (page_id)');
        $this->addSql('CREATE INDEX IDX_EA1B30343414710B ON promotions (agent_id)');
        $this->addSql('CREATE INDEX IDX_EA1B303461220EA6 ON promotions (creator_id)');
        $this->addSql('CREATE INDEX IDX_EA1B3034832A3469 ON promotions (promotion_number)');
        $this->addSql('COMMENT ON COLUMN promotions.status IS \'(DC2Type:promotion_status_enum)\'');
        $this->addSql('CREATE TABLE promotions_tariff_rates (promotion_id INT NOT NULL, tariff_rate_id INT NOT NULL, PRIMARY KEY(promotion_id, tariff_rate_id))');
        $this->addSql('CREATE INDEX IDX_6AD66C85139DF194 ON promotions_tariff_rates (promotion_id)');
        $this->addSql('CREATE INDEX IDX_6AD66C854C86DC06 ON promotions_tariff_rates (tariff_rate_id)');
        $this->addSql('CREATE TABLE promotion_categories (id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, name TEXT NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7F5D6DCC3414710B ON promotion_categories (agent_id)');
        $this->addSql('CREATE INDEX IDX_7F5D6DCC61220EA6 ON promotion_categories (creator_id)');
        $this->addSql('ALTER TABLE tariff_rates_promotions ADD CONSTRAINT FK_5B84F0C64C86DC06 FOREIGN KEY (tariff_rate_id) REFERENCES tariff_rates (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tariff_rates_promotions ADD CONSTRAINT FK_5B84F0C6139DF194 FOREIGN KEY (promotion_id) REFERENCES promotions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE promotions ADD CONSTRAINT FK_EA1B303412469DE2 FOREIGN KEY (category_id) REFERENCES promotion_categories (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE promotions ADD CONSTRAINT FK_EA1B30346E363BB FOREIGN KEY (is_based_on_id) REFERENCES promotions (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE promotions ADD CONSTRAINT FK_EA1B3034C4663E4 FOREIGN KEY (page_id) REFERENCES web_pages (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE promotions ADD CONSTRAINT FK_EA1B30343414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE promotions ADD CONSTRAINT FK_EA1B303461220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE promotions_tariff_rates ADD CONSTRAINT FK_6AD66C85139DF194 FOREIGN KEY (promotion_id) REFERENCES promotions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE promotions_tariff_rates ADD CONSTRAINT FK_6AD66C854C86DC06 FOREIGN KEY (tariff_rate_id) REFERENCES tariff_rates (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE promotion_categories ADD CONSTRAINT FK_7F5D6DCC3414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE promotion_categories ADD CONSTRAINT FK_7F5D6DCC61220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE application_requests ADD promotion_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD CONSTRAINT FK_C4BE0942139DF194 FOREIGN KEY (promotion_id) REFERENCES promotions (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C4BE0942139DF194 ON application_requests (promotion_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_requests DROP CONSTRAINT FK_C4BE0942139DF194');
        $this->addSql('ALTER TABLE tariff_rates_promotions DROP CONSTRAINT FK_5B84F0C6139DF194');
        $this->addSql('ALTER TABLE promotions DROP CONSTRAINT FK_EA1B30346E363BB');
        $this->addSql('ALTER TABLE promotions_tariff_rates DROP CONSTRAINT FK_6AD66C85139DF194');
        $this->addSql('ALTER TABLE promotions DROP CONSTRAINT FK_EA1B303412469DE2');
        $this->addSql('DROP SEQUENCE promotions_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE promotion_categories_id_seq CASCADE');
        $this->addSql('DROP TABLE tariff_rates_promotions');
        $this->addSql('DROP TABLE promotions');
        $this->addSql('DROP TABLE promotions_tariff_rates');
        $this->addSql('DROP TABLE promotion_categories');
        $this->addSql('DROP INDEX IDX_C4BE0942139DF194');
        $this->addSql('ALTER TABLE application_requests DROP promotion_id');
    }
}
