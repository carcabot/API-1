<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190816103923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE quotation_notes_contract_durations');
        $this->addSql('ALTER TABLE quotation_notes ADD terms JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE quotation_notes_contract_durations (quotation_note_id INT NOT NULL, contract_duration_id INT NOT NULL, PRIMARY KEY(quotation_note_id, contract_duration_id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_319e91dd75125e15 ON quotation_notes_contract_durations (contract_duration_id)');
        $this->addSql('CREATE INDEX idx_319e91dd36cd02c5 ON quotation_notes_contract_durations (quotation_note_id)');
        $this->addSql('ALTER TABLE quotation_notes_contract_durations ADD CONSTRAINT fk_319e91dd75125e15 FOREIGN KEY (contract_duration_id) REFERENCES contract_durations (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quotation_notes_contract_durations ADD CONSTRAINT fk_319e91dd36cd02c5 FOREIGN KEY (quotation_note_id) REFERENCES quotation_notes (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quotation_notes DROP terms');
    }
}
