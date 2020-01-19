<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180617185602 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE tariff_rates_notes (tariff_rate_id INT NOT NULL, note_id INT NOT NULL, PRIMARY KEY(tariff_rate_id, note_id))');
        $this->addSql('CREATE INDEX IDX_E3BA49A64C86DC06 ON tariff_rates_notes (tariff_rate_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E3BA49A626ED0855 ON tariff_rates_notes (note_id)');
        $this->addSql('ALTER TABLE tariff_rates_notes ADD CONSTRAINT FK_E3BA49A64C86DC06 FOREIGN KEY (tariff_rate_id) REFERENCES tariff_rates (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tariff_rates_notes ADD CONSTRAINT FK_E3BA49A626ED0855 FOREIGN KEY (note_id) REFERENCES notes (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tariff_rates ADD charge_description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_accounts ALTER status DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE tariff_rates_notes');
        $this->addSql('ALTER TABLE tariff_rates DROP charge_description');
        $this->addSql('ALTER TABLE customer_accounts ALTER status SET DEFAULT \'INACTIVE\'');
    }
}
