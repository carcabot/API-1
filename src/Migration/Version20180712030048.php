<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180712030048 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE security_deposits_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE security_deposit_lists (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE security_deposit_list_items (id INT NOT NULL, item_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A38946E0126F525E ON security_deposit_list_items (item_id)');
        $this->addSql('CREATE TABLE security_deposits (id INT NOT NULL, name TEXT DEFAULT NULL, type TEXT DEFAULT NULL, amount_currency VARCHAR(3) DEFAULT NULL, amount_value NUMERIC(19, 4) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE security_deposit_lists ADD CONSTRAINT FK_B993C60BF396750 FOREIGN KEY (id) REFERENCES item_lists (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE security_deposit_list_items ADD CONSTRAINT FK_A38946E0126F525E FOREIGN KEY (item_id) REFERENCES security_deposits (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE security_deposit_list_items ADD CONSTRAINT FK_A38946E0BF396750 FOREIGN KEY (id) REFERENCES list_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tariff_rate_terms ADD security_deposit_list_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tariff_rate_terms DROP deposit');
        $this->addSql('ALTER TABLE tariff_rate_terms DROP deposit_amount');
        $this->addSql('ALTER TABLE tariff_rate_terms DROP deposit_type');
        $this->addSql('ALTER TABLE tariff_rate_terms ADD CONSTRAINT FK_BC7CB4EEC4FCC46D FOREIGN KEY (security_deposit_list_id) REFERENCES security_deposit_lists (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_BC7CB4EEC4FCC46D ON tariff_rate_terms (security_deposit_list_id)');
        $this->addSql('ALTER TABLE item_lists ADD entity_type VARCHAR(128) NOT NULL');
        $this->addSql('ALTER TABLE item_lists DROP type');
        $this->addSql('ALTER TABLE customer_accounts ADD date_deleted TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE tariff_rate_terms DROP CONSTRAINT FK_BC7CB4EEC4FCC46D');
        $this->addSql('ALTER TABLE security_deposit_list_items DROP CONSTRAINT FK_A38946E0126F525E');
        $this->addSql('DROP SEQUENCE security_deposits_id_seq CASCADE');
        $this->addSql('DROP TABLE security_deposit_lists');
        $this->addSql('DROP TABLE security_deposit_list_items');
        $this->addSql('DROP TABLE security_deposits');
        $this->addSql('ALTER TABLE customer_accounts DROP date_deleted');
        $this->addSql('DROP INDEX IDX_BC7CB4EEC4FCC46D');
        $this->addSql('ALTER TABLE tariff_rate_terms ADD deposit TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE tariff_rate_terms ADD deposit_amount TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE tariff_rate_terms ADD deposit_type TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE tariff_rate_terms DROP security_deposit_list_id');
        $this->addSql('ALTER TABLE item_lists ADD type VARCHAR(254) NOT NULL');
        $this->addSql('ALTER TABLE item_lists DROP entity_type');
        $this->addSql('COMMENT ON COLUMN item_lists.type IS \'(DC2Type:item_list_type_enum)\'');
    }
}
