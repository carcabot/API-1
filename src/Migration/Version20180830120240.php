<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180830120240 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE contracts_payments (contract_id INT NOT NULL, payment_id INT NOT NULL, PRIMARY KEY(contract_id, payment_id))');
        $this->addSql('CREATE INDEX IDX_33E4659A2576E0FD ON contracts_payments (contract_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_33E4659A4C3A3BB ON contracts_payments (payment_id)');
        $this->addSql('ALTER TABLE contracts_payments ADD CONSTRAINT FK_33E4659A2576E0FD FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE contracts_payments ADD CONSTRAINT FK_33E4659A4C3A3BB FOREIGN KEY (payment_id) REFERENCES payments (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payments ADD payment_url TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE contracts_payments');
        $this->addSql('ALTER TABLE payments DROP payment_url');
    }
}
