<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181205085330 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service_level_agreement_actions ADD previous_status VARCHAR(254) DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN service_level_agreement_actions.previous_status IS \'(DC2Type:ticket_status_enum)\'');
        $this->addSql('COMMENT ON COLUMN service_level_agreement_actions.status IS \'(DC2Type:ticket_status_enum)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service_level_agreement_actions DROP previous_status');
        $this->addSql('COMMENT ON COLUMN service_level_agreement_actions.status IS \'(DC2Type:sla_action_status_enum)\'');
    }
}
