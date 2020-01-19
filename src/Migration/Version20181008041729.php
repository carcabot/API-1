<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181008041729 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE campaigns DROP CONSTRAINT fk_e3737470145e489e');
        $this->addSql('DROP INDEX uniq_e3737470145e489e');
        $this->addSql('ALTER TABLE campaigns ADD design_template TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE campaigns DROP thumbnail_image_id');
        $this->addSql('ALTER TABLE campaign_templates ADD is_based_on_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE campaign_templates ADD thumbnail_image_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE campaign_templates ADD CONSTRAINT FK_AFA061F56E363BB FOREIGN KEY (is_based_on_id) REFERENCES campaign_templates (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_templates ADD CONSTRAINT FK_AFA061F5145E489E FOREIGN KEY (thumbnail_image_id) REFERENCES digital_documents (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_AFA061F56E363BB ON campaign_templates (is_based_on_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AFA061F5145E489E ON campaign_templates (thumbnail_image_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE campaigns ADD thumbnail_image_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE campaigns DROP design_template');
        $this->addSql('ALTER TABLE campaigns ADD CONSTRAINT fk_e3737470145e489e FOREIGN KEY (thumbnail_image_id) REFERENCES digital_documents (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_e3737470145e489e ON campaigns (thumbnail_image_id)');
        $this->addSql('ALTER TABLE campaign_templates DROP CONSTRAINT FK_AFA061F56E363BB');
        $this->addSql('ALTER TABLE campaign_templates DROP CONSTRAINT FK_AFA061F5145E489E');
        $this->addSql('DROP INDEX IDX_AFA061F56E363BB');
        $this->addSql('DROP INDEX UNIQ_AFA061F5145E489E');
        $this->addSql('ALTER TABLE campaign_templates DROP is_based_on_id');
        $this->addSql('ALTER TABLE campaign_templates DROP thumbnail_image_id');
    }
}
