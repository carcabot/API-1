<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20180531031711 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE addon_services_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE tariff_rate_terms_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE addon_services (id INT NOT NULL, description TEXT DEFAULT NULL, name TEXT DEFAULT NULL, note TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE tariff_rate_terms (id INT NOT NULL, tariff_rate_id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, bill_from_service_provider BOOLEAN DEFAULT NULL, contract_duration TEXT DEFAULT NULL, contract_renewal TEXT DEFAULT NULL, deposit TEXT DEFAULT NULL, deposit_amount TEXT DEFAULT NULL, deposit_type TEXT DEFAULT NULL, discount TEXT DEFAULT NULL, early_termination_charges TEXT DEFAULT NULL, fixed_rate TEXT DEFAULT NULL, free_gifts TEXT DEFAULT NULL, incentives TEXT DEFAULT NULL, late_payment_charges TEXT DEFAULT NULL, meter_installation_fee TEXT DEFAULT NULL, non_standard_plan TEXT DEFAULT NULL, other_fee_and_charges TEXT DEFAULT NULL, plan_name TEXT DEFAULT NULL, plan_type TEXT DEFAULT NULL, prevailing_meter_charge TEXT DEFAULT NULL, registration_fee TEXT DEFAULT NULL, smart_meter BOOLEAN DEFAULT NULL, service_provider TEXT DEFAULT NULL, standard_plan TEXT DEFAULT NULL, version TEXT DEFAULT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BC7CB4EE4C86DC06 ON tariff_rate_terms (tariff_rate_id)');
        $this->addSql('CREATE INDEX IDX_BC7CB4EE3414710B ON tariff_rate_terms (agent_id)');
        $this->addSql('CREATE INDEX IDX_BC7CB4EE61220EA6 ON tariff_rate_terms (creator_id)');
        $this->addSql('ALTER TABLE tariff_rate_terms ADD CONSTRAINT FK_BC7CB4EE4C86DC06 FOREIGN KEY (tariff_rate_id) REFERENCES tariff_rates (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tariff_rate_terms ADD CONSTRAINT FK_BC7CB4EE3414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tariff_rate_terms ADD CONSTRAINT FK_BC7CB4EE61220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tariff_rates ADD terms_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tariff_rates ADD CONSTRAINT FK_4E7361A653742F27 FOREIGN KEY (terms_id) REFERENCES tariff_rate_terms (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4E7361A653742F27 ON tariff_rates (terms_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE tariff_rates DROP CONSTRAINT FK_4E7361A653742F27');
        $this->addSql('DROP SEQUENCE addon_services_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE tariff_rate_terms_id_seq CASCADE');
        $this->addSql('DROP TABLE addon_services');
        $this->addSql('DROP TABLE tariff_rate_terms');
        $this->addSql('DROP INDEX UNIQ_4E7361A653742F27');
        $this->addSql('ALTER TABLE tariff_rates DROP terms_id');
    }
}
