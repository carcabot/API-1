<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180723085711 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE tariff_rate_lists (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE tariff_rate_list_items (id INT NOT NULL, item_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6DBE1778126F525E ON tariff_rate_list_items (item_id)');
        $this->addSql('ALTER TABLE tariff_rate_lists ADD CONSTRAINT FK_3CF8143ABF396750 FOREIGN KEY (id) REFERENCES item_lists (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tariff_rate_list_items ADD CONSTRAINT FK_6DBE1778126F525E FOREIGN KEY (item_id) REFERENCES tariff_rates (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tariff_rate_list_items ADD CONSTRAINT FK_6DBE1778BF396750 FOREIGN KEY (id) REFERENCES list_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE partners ADD tariff_rate_list_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE partners ADD CONSTRAINT FK_EFEB5164B11DEE28 FOREIGN KEY (tariff_rate_list_id) REFERENCES tariff_rate_lists (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_EFEB5164B11DEE28 ON partners (tariff_rate_list_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE partners DROP CONSTRAINT FK_EFEB5164B11DEE28');
        $this->addSql('DROP TABLE tariff_rate_lists');
        $this->addSql('DROP TABLE tariff_rate_list_items');
        $this->addSql('DROP INDEX IDX_EFEB5164B11DEE28');
        $this->addSql('ALTER TABLE partners DROP tariff_rate_list_id');
    }
}
