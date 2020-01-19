<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190219080516 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE contract_actions ALTER action_status SET NOT NULL');
        $this->addSql('ALTER TABLE contract_actions ADD CONSTRAINT FK_D198C277A7B643 FOREIGN KEY (result_id) REFERENCES contracts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_D198C277A7B643 ON contract_actions (result_id)');
        $this->addSql('ALTER TABLE postal_addresses ADD agent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE postal_addresses ADD creator_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE postal_addresses ADD date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE postal_addresses ADD date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE postal_addresses ADD CONSTRAINT FK_E1CD665B3414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE postal_addresses ADD CONSTRAINT FK_E1CD665B61220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_E1CD665B3414710B ON postal_addresses (agent_id)');
        $this->addSql('CREATE INDEX IDX_E1CD665B61220EA6 ON postal_addresses (creator_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE contract_actions DROP CONSTRAINT FK_D198C277A7B643');
        $this->addSql('DROP INDEX IDX_D198C277A7B643');
        $this->addSql('ALTER TABLE contract_actions ALTER action_status DROP NOT NULL');
        $this->addSql('ALTER TABLE postal_addresses DROP CONSTRAINT FK_E1CD665B3414710B');
        $this->addSql('ALTER TABLE postal_addresses DROP CONSTRAINT FK_E1CD665B61220EA6');
        $this->addSql('DROP INDEX IDX_E1CD665B3414710B');
        $this->addSql('DROP INDEX IDX_E1CD665B61220EA6');
        $this->addSql('ALTER TABLE postal_addresses DROP agent_id');
        $this->addSql('ALTER TABLE postal_addresses DROP creator_id');
        $this->addSql('ALTER TABLE postal_addresses DROP date_created');
        $this->addSql('ALTER TABLE postal_addresses DROP date_modified');
    }
}
