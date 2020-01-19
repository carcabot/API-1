<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181108065824 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE customer_accounts ADD default_credits_contract_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_accounts ADD CONSTRAINT FK_E60DDD83DD26511A FOREIGN KEY (default_credits_contract_id) REFERENCES contracts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_E60DDD83DD26511A ON customer_accounts (default_credits_contract_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE customer_accounts DROP CONSTRAINT FK_E60DDD83DD26511A');
        $this->addSql('DROP INDEX IDX_E60DDD83DD26511A');
        $this->addSql('ALTER TABLE customer_accounts DROP default_credits_contract_id');
    }
}
