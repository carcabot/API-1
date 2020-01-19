<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190214103003 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE contract_postal_addresses_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE contract_postal_addresses (id INT NOT NULL, address_id INT NOT NULL, contract_id INT NOT NULL, valid_from TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, valid_through TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BC762324F5B7AF75 ON contract_postal_addresses (address_id)');
        $this->addSql('CREATE INDEX IDX_BC7623242576E0FD ON contract_postal_addresses (contract_id)');
        $this->addSql('ALTER TABLE contract_postal_addresses ADD CONSTRAINT FK_BC762324F5B7AF75 FOREIGN KEY (address_id) REFERENCES postal_addresses (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE contract_postal_addresses ADD CONSTRAINT FK_BC7623242576E0FD FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql(<<<'SQL'
            INSERT INTO contract_postal_addresses (id, contract_id, address_id)
                SELECT nextval('contract_postal_addresses_id_seq'), contract_id, address_id FROM contracts_postal_addresses WHERE contract_id IN (SELECT id FROM contracts)
SQL
        );

        $this->addSql('DROP TABLE contracts_postal_addresses');
        $this->addSql('ALTER TABLE postal_addresses ADD text TEXT DEFAULT NULL');

        $this->addSql("UPDATE postal_addresses SET text = CONCAT(COALESCE(address_country,''),'_',COALESCE(address_locality,''),'_',COALESCE(address_region,''),'_',COALESCE(building_name,''),'_',COALESCE(floor,''),'_',COALESCE(house_number,''),'_',COALESCE(postal_code,''),'_',COALESCE(street_address,''),'_',COALESCE(type,''),'_',COALESCE(unit_number,''))");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE contract_postal_addresses_id_seq CASCADE');
        $this->addSql('CREATE TABLE contracts_postal_addresses (contract_id INT NOT NULL, address_id INT NOT NULL, PRIMARY KEY(contract_id, address_id))');
        $this->addSql('CREATE INDEX idx_c954053d2576e0fd ON contracts_postal_addresses (contract_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_c954053df5b7af75 ON contracts_postal_addresses (address_id)');
        $this->addSql('ALTER TABLE contracts_postal_addresses ADD CONSTRAINT fk_c954053df5b7af75 FOREIGN KEY (address_id) REFERENCES postal_addresses (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE contracts_postal_addresses ADD CONSTRAINT fk_c954053d2576e0fd FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE contract_postal_addresses');
        $this->addSql('ALTER TABLE postal_addresses DROP text');
    }
}
