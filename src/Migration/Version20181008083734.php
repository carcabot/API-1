<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181008083734 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE campaigns DROP design_template');
        $this->addSql('ALTER TABLE campaign_templates ADD text TEXT NOT NULL');
        $this->addSql('ALTER TABLE campaign_templates RENAME COLUMN template TO raw_text');
        $this->addSql('ALTER TABLE list_items ALTER name DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE campaigns ADD design_template TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE campaign_templates ADD template TEXT NOT NULL');
        $this->addSql('ALTER TABLE campaign_templates DROP raw_text');
        $this->addSql('ALTER TABLE campaign_templates DROP text');
        $this->addSql('ALTER TABLE list_items ALTER name SET NOT NULL');
    }
}
