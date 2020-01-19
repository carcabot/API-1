<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180612095555 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE tariff_rate_terms_addon_services (tariff_rate_terms_id INT NOT NULL, addon_service_id INT NOT NULL, PRIMARY KEY(tariff_rate_terms_id, addon_service_id))');
        $this->addSql('CREATE INDEX IDX_CF9E13481C276E8D ON tariff_rate_terms_addon_services (tariff_rate_terms_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CF9E13482BD12FFE ON tariff_rate_terms_addon_services (addon_service_id)');
        $this->addSql('ALTER TABLE tariff_rate_terms_addon_services ADD CONSTRAINT FK_CF9E13481C276E8D FOREIGN KEY (tariff_rate_terms_id) REFERENCES tariff_rate_terms (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tariff_rate_terms_addon_services ADD CONSTRAINT FK_CF9E13482BD12FFE FOREIGN KEY (addon_service_id) REFERENCES addon_services (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE tariff_rate_terms_addon_services');
    }
}
