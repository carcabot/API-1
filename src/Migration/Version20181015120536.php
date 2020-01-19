<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181015120536 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE mailgun_events DROP CONSTRAINT FK_AD7A93A5E92F8F78');
        $this->addSql('ALTER TABLE mailgun_events ADD CONSTRAINT FK_AD7A93A5E92F8F78 FOREIGN KEY (recipient_id) REFERENCES email_campaign_source_list_items (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_source_list_items DROP CONSTRAINT FK_E8B097855458D');
        $this->addSql('ALTER TABLE campaign_source_list_items DROP CONSTRAINT FK_E8B09789395C3F3');
        $this->addSql('DROP INDEX uniq_e8b09789395c3f3');
        $this->addSql('DROP INDEX uniq_e8b097855458d');
        $this->addSql('ALTER TABLE campaign_source_list_items ADD CONSTRAINT FK_E8B097855458D FOREIGN KEY (lead_id) REFERENCES leads (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_source_list_items ADD CONSTRAINT FK_E8B09789395C3F3 FOREIGN KEY (customer_id) REFERENCES customer_accounts (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_E8B09789395C3F3 ON campaign_source_list_items (customer_id)');
        $this->addSql('CREATE INDEX IDX_E8B097855458D ON campaign_source_list_items (lead_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE mailgun_events DROP CONSTRAINT fk_ad7a93a5e92f8f78');
        $this->addSql('ALTER TABLE mailgun_events ADD CONSTRAINT fk_ad7a93a5e92f8f78 FOREIGN KEY (recipient_id) REFERENCES email_campaign_source_list_items (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_source_list_items DROP CONSTRAINT fk_e8b09789395c3f3');
        $this->addSql('ALTER TABLE campaign_source_list_items DROP CONSTRAINT fk_e8b097855458d');
        $this->addSql('DROP INDEX IDX_E8B09789395C3F3');
        $this->addSql('DROP INDEX IDX_E8B097855458D');
        $this->addSql('ALTER TABLE campaign_source_list_items ADD CONSTRAINT fk_e8b09789395c3f3 FOREIGN KEY (customer_id) REFERENCES customer_accounts (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_source_list_items ADD CONSTRAINT fk_e8b097855458d FOREIGN KEY (lead_id) REFERENCES leads (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_e8b09789395c3f3 ON campaign_source_list_items (customer_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_e8b097855458d ON campaign_source_list_items (lead_id)');
    }
}
