<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180628225844 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE affiliate_programs (id INT NOT NULL, terms_id INT DEFAULT NULL, description TEXT DEFAULT NULL, disambiguating_description TEXT DEFAULT NULL, position INT DEFAULT NULL, return_url TEXT DEFAULT NULL, status VARCHAR(254) NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, base_tracking_url TEXT DEFAULT NULL, name TEXT DEFAULT NULL, provider VARCHAR(254) NOT NULL, valid_from TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, valid_through TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_39C3D30E53742F27 ON affiliate_programs (terms_id)');
        $this->addSql('COMMENT ON COLUMN affiliate_programs.status IS \'(DC2Type:web_page_status_enum)\'');
        $this->addSql('COMMENT ON COLUMN affiliate_programs.provider IS \'(DC2Type:affiliate_web_service_partner_enum)\'');
        $this->addSql('ALTER TABLE affiliate_programs ADD CONSTRAINT FK_39C3D30E53742F27 FOREIGN KEY (terms_id) REFERENCES web_pages (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE affiliate_programs');
    }
}
