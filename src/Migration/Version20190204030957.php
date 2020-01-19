<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190204030957 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_requests ADD payment_mode VARCHAR(254) DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN application_requests.payment_mode IS \'(DC2Type:payment_mode_enum)\'');
        $this->addSql('ALTER TABLE contracts ADD payment_mode VARCHAR(254) DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN contracts.payment_mode IS \'(DC2Type:payment_mode_enum)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_requests DROP payment_mode');
        $this->addSql('ALTER TABLE contracts DROP payment_mode');
    }
}
