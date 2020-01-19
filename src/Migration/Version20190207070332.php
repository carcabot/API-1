<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190207070332 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE contracts ALTER contract_number DROP NOT NULL');
        $this->addSql('ALTER TABLE contract_actions ADD instrument_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contract_actions ADD result_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contract_actions ALTER action_status DROP NOT NULL');
        $this->addSql('ALTER TABLE contract_actions ADD CONSTRAINT FK_D198C27CF11D9C FOREIGN KEY (instrument_id) REFERENCES application_requests (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_D198C27CF11D9C ON contract_actions (instrument_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE contracts ALTER contract_number SET NOT NULL');
        $this->addSql('ALTER TABLE contract_actions DROP CONSTRAINT FK_D198C27CF11D9C');
        $this->addSql('DROP INDEX IDX_D198C27CF11D9C');
        $this->addSql('ALTER TABLE contract_actions DROP instrument_id');
        $this->addSql('ALTER TABLE contract_actions DROP result_id');
        $this->addSql('ALTER TABLE contract_actions ALTER action_status SET NOT NULL');
    }
}
