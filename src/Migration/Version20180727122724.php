<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180727122724 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE addon_service_list_items (id INT NOT NULL, item_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2E3FABD1126F525E ON addon_service_list_items (item_id)');
        $this->addSql('CREATE TABLE free_gift_lists (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE free_gift_list_items (id INT NOT NULL, item_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_28EE55AB126F525E ON free_gift_list_items (item_id)');
        $this->addSql('CREATE TABLE addon_service_lists (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE addon_service_list_items ADD CONSTRAINT FK_2E3FABD1126F525E FOREIGN KEY (item_id) REFERENCES addon_services (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE addon_service_list_items ADD CONSTRAINT FK_2E3FABD1BF396750 FOREIGN KEY (id) REFERENCES list_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE free_gift_lists ADD CONSTRAINT FK_AF919C1BF396750 FOREIGN KEY (id) REFERENCES item_lists (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE free_gift_list_items ADD CONSTRAINT FK_28EE55AB126F525E FOREIGN KEY (item_id) REFERENCES free_gifts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE free_gift_list_items ADD CONSTRAINT FK_28EE55ABBF396750 FOREIGN KEY (id) REFERENCES list_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE addon_service_lists ADD CONSTRAINT FK_4DB1401CBF396750 FOREIGN KEY (id) REFERENCES item_lists (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE tariff_rate_terms_addon_services');
        $this->addSql('ALTER TABLE tariff_rate_terms ADD addon_service_list_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tariff_rate_terms ADD free_gift_list_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tariff_rate_terms DROP free_gifts');
        $this->addSql('ALTER TABLE tariff_rate_terms ADD CONSTRAINT FK_BC7CB4EE1EC589A8 FOREIGN KEY (addon_service_list_id) REFERENCES addon_service_lists (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tariff_rate_terms ADD CONSTRAINT FK_BC7CB4EE38E49CE2 FOREIGN KEY (free_gift_list_id) REFERENCES free_gift_lists (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_BC7CB4EE1EC589A8 ON tariff_rate_terms (addon_service_list_id)');
        $this->addSql('CREATE INDEX IDX_BC7CB4EE38E49CE2 ON tariff_rate_terms (free_gift_list_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE tariff_rate_terms DROP CONSTRAINT FK_BC7CB4EE38E49CE2');
        $this->addSql('ALTER TABLE tariff_rate_terms DROP CONSTRAINT FK_BC7CB4EE1EC589A8');
        $this->addSql('CREATE TABLE tariff_rate_terms_addon_services (tariff_rate_terms_id INT NOT NULL, addon_service_id INT NOT NULL, PRIMARY KEY(tariff_rate_terms_id, addon_service_id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_cf9e13482bd12ffe ON tariff_rate_terms_addon_services (addon_service_id)');
        $this->addSql('CREATE INDEX idx_cf9e13481c276e8d ON tariff_rate_terms_addon_services (tariff_rate_terms_id)');
        $this->addSql('ALTER TABLE tariff_rate_terms_addon_services ADD CONSTRAINT fk_cf9e13482bd12ffe FOREIGN KEY (addon_service_id) REFERENCES addon_services (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tariff_rate_terms_addon_services ADD CONSTRAINT fk_cf9e13481c276e8d FOREIGN KEY (tariff_rate_terms_id) REFERENCES tariff_rate_terms (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE addon_service_list_items');
        $this->addSql('DROP TABLE free_gift_lists');
        $this->addSql('DROP TABLE free_gift_list_items');
        $this->addSql('DROP TABLE addon_service_lists');
        $this->addSql('DROP INDEX IDX_BC7CB4EE1EC589A8');
        $this->addSql('DROP INDEX IDX_BC7CB4EE38E49CE2');
        $this->addSql('ALTER TABLE tariff_rate_terms ADD free_gifts TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE tariff_rate_terms DROP addon_service_list_id');
        $this->addSql('ALTER TABLE tariff_rate_terms DROP free_gift_list_id');
    }
}
