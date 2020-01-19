<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190619092316 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE recurring_email_campaigns_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE recurring_email_campaigns (id INT NOT NULL, campaign_id INT NOT NULL, source_list_generator_expression VARCHAR(255) NOT NULL, from_position INT NOT NULL, to_position INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_67B2E2ADF639F774 ON recurring_email_campaigns (campaign_id)');
        $this->addSql('ALTER TABLE recurring_email_campaigns ADD CONSTRAINT FK_67B2E2ADF639F774 FOREIGN KEY (campaign_id) REFERENCES campaigns (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE recurring_email_campaigns_id_seq CASCADE');
        $this->addSql('DROP TABLE recurring_email_campaigns');
    }
}
