<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190918091836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE deactivate_contract_credits_actions (id INT NOT NULL, instrument_id INT DEFAULT NULL, object_id INT NOT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3DFC5F6CCF11D9C ON deactivate_contract_credits_actions (instrument_id)');
        $this->addSql('CREATE INDEX IDX_3DFC5F6C232D562B ON deactivate_contract_credits_actions (object_id)');
        $this->addSql('ALTER TABLE deactivate_contract_credits_actions ADD CONSTRAINT FK_3DFC5F6CCF11D9C FOREIGN KEY (instrument_id) REFERENCES application_requests (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE deactivate_contract_credits_actions ADD CONSTRAINT FK_3DFC5F6C232D562B FOREIGN KEY (object_id) REFERENCES contracts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE deactivate_contract_credits_actions ADD CONSTRAINT FK_3DFC5F6CBF396750 FOREIGN KEY (id) REFERENCES update_credits_actions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quotation_price_configurations DROP CONSTRAINT fk_e71cbaf6fb7f25d6');
        $this->addSql('DROP INDEX idx_e71cbaf6fb7f25d6');
        $this->addSql('ALTER TABLE quotation_price_configurations RENAME COLUMN third_party_charges_id TO third_party_charge_configuration_id');
        $this->addSql('ALTER TABLE quotation_price_configurations ADD CONSTRAINT FK_E71CBAF6C0E8374C FOREIGN KEY (third_party_charge_configuration_id) REFERENCES third_party_charge_configurations (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_E71CBAF6C0E8374C ON quotation_price_configurations (third_party_charge_configuration_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE deactivate_contract_credits_actions');
        $this->addSql('ALTER TABLE quotation_price_configurations DROP CONSTRAINT FK_E71CBAF6C0E8374C');
        $this->addSql('DROP INDEX IDX_E71CBAF6C0E8374C');
        $this->addSql('ALTER TABLE quotation_price_configurations RENAME COLUMN third_party_charge_configuration_id TO third_party_charges_id');
        $this->addSql('ALTER TABLE quotation_price_configurations ADD CONSTRAINT fk_e71cbaf6fb7f25d6 FOREIGN KEY (third_party_charges_id) REFERENCES third_party_charge_configurations (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_e71cbaf6fb7f25d6 ON quotation_price_configurations (third_party_charges_id)');
    }
}
