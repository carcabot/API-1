<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181009070741 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE campaigns ADD template_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE campaigns DROP template');
        $this->addSql('ALTER TABLE campaigns ADD CONSTRAINT FK_E37374705DA0FB8 FOREIGN KEY (template_id) REFERENCES campaign_templates (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E37374705DA0FB8 ON campaigns (template_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE campaigns DROP CONSTRAINT FK_E37374705DA0FB8');
        $this->addSql('DROP INDEX UNIQ_E37374705DA0FB8');
        $this->addSql('ALTER TABLE campaigns ADD template VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE campaigns DROP template_id');
    }
}
