<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180823030700 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE redeem_credits_actions ADD object_id INT NOT NULL');
        $this->addSql('ALTER TABLE redeem_credits_actions ADD CONSTRAINT FK_FA9E49E2232D562B FOREIGN KEY (object_id) REFERENCES contracts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_FA9E49E2232D562B ON redeem_credits_actions (object_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE redeem_credits_actions DROP CONSTRAINT FK_FA9E49E2232D562B');
        $this->addSql('DROP INDEX IDX_FA9E49E2232D562B');
        $this->addSql('ALTER TABLE redeem_credits_actions DROP object_id');
    }
}
