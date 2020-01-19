<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190927072003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE campaign_objectives ADD enabled BOOLEAN NULL');
        $this->addSql('ALTER TABLE campaign_expectations ADD enabled BOOLEAN NULL');
        $this->addSql('UPDATE campaign_objectives SET enabled = true');
        $this->addSql('UPDATE campaign_expectations SET enabled = true');
        $this->addSql('ALTER TABLE campaign_objectives ALTER enabled SET NOT NULL');
        $this->addSql('ALTER TABLE campaign_expectations ALTER enabled SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE campaign_objectives DROP enabled');
        $this->addSql('ALTER TABLE campaign_expectations DROP enabled');
    }
}
