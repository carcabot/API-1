<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180912064157 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE customer_account_relationships_contracts (customer_account_relationship_id INT NOT NULL, contract_id INT NOT NULL, PRIMARY KEY(customer_account_relationship_id, contract_id))');
        $this->addSql('CREATE INDEX IDX_E782C63630A0764D ON customer_account_relationships_contracts (customer_account_relationship_id)');
        $this->addSql('CREATE INDEX IDX_E782C6362576E0FD ON customer_account_relationships_contracts (contract_id)');
        $this->addSql('ALTER TABLE customer_account_relationships_contracts ADD CONSTRAINT FK_E782C63630A0764D FOREIGN KEY (customer_account_relationship_id) REFERENCES customer_account_relationships (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE customer_account_relationships_contracts ADD CONSTRAINT FK_E782C6362576E0FD FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql(<<<'SQL'
            WITH relationships AS (
                SELECT id as customer_account_relationship_id, contract_id FROM customer_account_relationships WHERE contract_id IS NOT NULL
            )
            INSERT INTO customer_account_relationships_contracts (customer_account_relationship_id, contract_id)
            SELECT customer_account_relationship_id, contract_id
            FROM relationships
SQL
        );

        $this->addSql('ALTER TABLE customer_account_relationships DROP CONSTRAINT fk_4b2ccb82576e0fd');
        $this->addSql('DROP INDEX idx_4b2ccb82576e0fd');
        $this->addSql('ALTER TABLE customer_account_relationships DROP contract_id');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE customer_account_relationships_contracts');
        $this->addSql('ALTER TABLE customer_account_relationships ADD contract_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_account_relationships ADD CONSTRAINT fk_4b2ccb82576e0fd FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_4b2ccb82576e0fd ON customer_account_relationships (contract_id)');
    }
}
