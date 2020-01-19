<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181016073600 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE withdraw_credits_actions ADD contract_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE withdraw_credits_actions ADD CONSTRAINT FK_5663374D2576E0FD FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_5663374D2576E0FD ON withdraw_credits_actions (contract_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE withdraw_credits_actions DROP CONSTRAINT FK_5663374D2576E0FD');
        $this->addSql('DROP INDEX IDX_5663374D2576E0FD');
        $this->addSql('ALTER TABLE withdraw_credits_actions DROP contract_id');
    }
}
