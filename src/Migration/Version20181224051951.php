<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181224051951 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE referral_credits_schemes (id INT NOT NULL, referral_amount_max_value NUMERIC(19, 4) DEFAULT NULL, referral_amount_min_value NUMERIC(19, 4) DEFAULT NULL, referral_amount_unit_code TEXT DEFAULT NULL, referral_amount_value NUMERIC(19, 4) DEFAULT NULL, referee_amount_max_value NUMERIC(19, 4) DEFAULT NULL, referee_amount_min_value NUMERIC(19, 4) DEFAULT NULL, referee_amount_unit_code TEXT DEFAULT NULL, referee_amount_value NUMERIC(19, 4) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE referral_credits_schemes ADD CONSTRAINT FK_A81ADDFCBF396750 FOREIGN KEY (id) REFERENCES credits_schemes (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users ADD referral_code VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E96447454A ON users (referral_code)');
        $this->addSql('ALTER TABLE application_requests ADD referral_code VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE credits_schemes ADD entity_type VARCHAR(128) NULL');
        $this->addSql('UPDATE credits_schemes set entity_type = \'credits_scheme\'');
        $this->addSql('ALTER TABLE credits_schemes ALTER column entity_type set NOT NULL');
        $this->addSql('ALTER TABLE offers ADD offer_number VARCHAR(128) NULL');
        $this->addSql('UPDATE offers SET offer_number = concat(\'O00000000\', id)');
        $this->addSql('ALTER TABLE offers ALTER column offer_number set NOT NULL');
        $this->addSql('insert into running_numbers (type, series, number) values (\'offer\', \'9\', (select count(id) from offers where EXISTS (select id from offers where id = 1)))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DA4604274FC568F5 ON offers (offer_number)');
        $this->addSql('ALTER TABLE offer_list_items ADD price_specification_max_price NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE offer_list_items ADD price_specification_min_price NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE offer_list_items ADD price_specification_price NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE offer_list_items DROP price_max_price');
        $this->addSql('ALTER TABLE offer_list_items DROP price_min_price');
        $this->addSql('ALTER TABLE offer_list_items DROP price_price');
        $this->addSql('ALTER TABLE offer_list_items RENAME COLUMN price_price_currency TO price_specification_price_currency');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE referral_credits_schemes');
        $this->addSql('ALTER TABLE application_requests DROP referral_code');
        $this->addSql('ALTER TABLE offer_list_items ADD price_max_price NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE offer_list_items ADD price_min_price NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE offer_list_items ADD price_price NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE offer_list_items DROP price_specification_max_price');
        $this->addSql('ALTER TABLE offer_list_items DROP price_specification_min_price');
        $this->addSql('ALTER TABLE offer_list_items DROP price_specification_price');
        $this->addSql('ALTER TABLE offer_list_items RENAME COLUMN price_specification_price_currency TO price_price_currency');
        $this->addSql('DROP INDEX UNIQ_DA4604274FC568F5');
        $this->addSql('ALTER TABLE offers DROP offer_number');
        $this->addSql('DROP INDEX UNIQ_1483A5E96447454A');
        $this->addSql('ALTER TABLE users DROP referral_code');
        $this->addSql('ALTER TABLE credits_schemes DROP entity_type');
    }
}
