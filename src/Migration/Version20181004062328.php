<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181004062328 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE withdraw_credits_actions ADD instrument_id INT NOT NULL');
        $this->addSql('ALTER TABLE withdraw_credits_actions ADD CONSTRAINT FK_5663374DCF11D9C FOREIGN KEY (instrument_id) REFERENCES payments (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_5663374DCF11D9C ON withdraw_credits_actions (instrument_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE withdraw_credits_actions DROP CONSTRAINT FK_5663374DCF11D9C');
        $this->addSql('DROP INDEX IDX_5663374DCF11D9C');
        $this->addSql('ALTER TABLE withdraw_credits_actions DROP instrument_id');
    }
}
