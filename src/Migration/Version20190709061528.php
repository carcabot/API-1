<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190709061528 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE promotions_tariff_rates');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE promotions_tariff_rates (promotion_id INT NOT NULL, tariff_rate_id INT NOT NULL, PRIMARY KEY(promotion_id, tariff_rate_id))');
        $this->addSql('CREATE INDEX idx_6ad66c854c86dc06 ON promotions_tariff_rates (tariff_rate_id)');
        $this->addSql('CREATE INDEX idx_6ad66c85139df194 ON promotions_tariff_rates (promotion_id)');
        $this->addSql('ALTER TABLE promotions_tariff_rates ADD CONSTRAINT fk_6ad66c854c86dc06 FOREIGN KEY (tariff_rate_id) REFERENCES tariff_rates (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE promotions_tariff_rates ADD CONSTRAINT fk_6ad66c85139df194 FOREIGN KEY (promotion_id) REFERENCES promotions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
