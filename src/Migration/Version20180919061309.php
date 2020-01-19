<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180919061309 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE tariff_rates_notes');
        $this->addSql('ALTER TABLE partners ADD referral_url_hidden BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE free_gifts ADD is_based_on_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE free_gifts ADD CONSTRAINT FK_FE42B89F6E363BB FOREIGN KEY (is_based_on_id) REFERENCES free_gifts (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_FE42B89F6E363BB ON free_gifts (is_based_on_id)');
        $this->addSql('ALTER TABLE security_deposits ADD is_based_on_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE security_deposits ADD CONSTRAINT FK_27F51B4C6E363BB FOREIGN KEY (is_based_on_id) REFERENCES security_deposits (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_27F51B4C6E363BB ON security_deposits (is_based_on_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE tariff_rates_notes (tariff_rate_id INT NOT NULL, note_id INT NOT NULL, PRIMARY KEY(tariff_rate_id, note_id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_e3ba49a626ed0855 ON tariff_rates_notes (note_id)');
        $this->addSql('CREATE INDEX idx_e3ba49a64c86dc06 ON tariff_rates_notes (tariff_rate_id)');
        $this->addSql('ALTER TABLE tariff_rates_notes ADD CONSTRAINT fk_e3ba49a626ed0855 FOREIGN KEY (note_id) REFERENCES notes (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tariff_rates_notes ADD CONSTRAINT fk_e3ba49a64c86dc06 FOREIGN KEY (tariff_rate_id) REFERENCES tariff_rates (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE free_gifts DROP CONSTRAINT FK_FE42B89F6E363BB');
        $this->addSql('DROP INDEX IDX_FE42B89F6E363BB');
        $this->addSql('ALTER TABLE free_gifts DROP is_based_on_id');
        $this->addSql('ALTER TABLE security_deposits DROP CONSTRAINT FK_27F51B4C6E363BB');
        $this->addSql('DROP INDEX IDX_27F51B4C6E363BB');
        $this->addSql('ALTER TABLE security_deposits DROP is_based_on_id');
        $this->addSql('ALTER TABLE partners DROP referral_url_hidden');
    }
}
