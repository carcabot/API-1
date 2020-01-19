<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190130044509 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE contracts ALTER tariff_rate_id DROP NOT NULL');
        $this->addSql('ALTER TABLE contracts ALTER subtype DROP NOT NULL');
        $this->addSql('ALTER TABLE contracts ALTER customer_type DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE contracts ALTER tariff_rate_id SET NOT NULL');
        $this->addSql('ALTER TABLE contracts ALTER customer_type SET NOT NULL');
        $this->addSql('ALTER TABLE contracts ALTER subtype SET NOT NULL');
    }
}
