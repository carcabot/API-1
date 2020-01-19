<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190808062417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE quotation_price_configurations ADD duration_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE quotation_price_configurations ADD is_based_on_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE quotation_price_configurations ADD offer_input_max_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE quotation_price_configurations ADD offer_input_min_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE quotation_price_configurations ADD offer_input_unit_code TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE quotation_price_configurations ADD offer_input_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE quotation_price_configurations ADD CONSTRAINT FK_E71CBAF637B987D8 FOREIGN KEY (duration_id) REFERENCES contract_durations (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quotation_price_configurations ADD CONSTRAINT FK_E71CBAF66E363BB FOREIGN KEY (is_based_on_id) REFERENCES quotation_price_configurations (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E71CBAF637B987D8 ON quotation_price_configurations (duration_id)');
        $this->addSql('CREATE INDEX IDX_E71CBAF66E363BB ON quotation_price_configurations (is_based_on_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE quotation_price_configurations DROP CONSTRAINT FK_E71CBAF637B987D8');
        $this->addSql('ALTER TABLE quotation_price_configurations DROP CONSTRAINT FK_E71CBAF66E363BB');
        $this->addSql('DROP INDEX UNIQ_E71CBAF637B987D8');
        $this->addSql('DROP INDEX IDX_E71CBAF66E363BB');
        $this->addSql('ALTER TABLE quotation_price_configurations DROP duration_id');
        $this->addSql('ALTER TABLE quotation_price_configurations DROP is_based_on_id');
        $this->addSql('ALTER TABLE quotation_price_configurations DROP offer_input_max_value');
        $this->addSql('ALTER TABLE quotation_price_configurations DROP offer_input_min_value');
        $this->addSql('ALTER TABLE quotation_price_configurations DROP offer_input_unit_code');
        $this->addSql('ALTER TABLE quotation_price_configurations DROP offer_input_value');
    }
}
