<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180621045835 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE contracts ADD billing_period_id VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE contracts ADD customized BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE contracts ADD lock_in_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE contracts ADD closure_notice_period_max_value SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE contracts ADD closure_notice_period_min_value SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE contracts ADD closure_notice_period_unit_code TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE contracts ADD closure_notice_period_value SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD customized BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD referral_source VARCHAR(254) DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD specified_referral_source VARCHAR(255) DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN application_requests.referral_source IS \'(DC2Type:referral_source_enum)\'');
        $this->addSql('ALTER TABLE web_pages ADD cover_image_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE web_pages ADD CONSTRAINT FK_ED67E1EBE5A0E336 FOREIGN KEY (cover_image_id) REFERENCES digital_documents (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ED67E1EBE5A0E336 ON web_pages (cover_image_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_requests DROP customized');
        $this->addSql('ALTER TABLE application_requests DROP referral_source');
        $this->addSql('ALTER TABLE application_requests DROP specified_referral_source');
        $this->addSql('ALTER TABLE web_pages DROP CONSTRAINT FK_ED67E1EBE5A0E336');
        $this->addSql('DROP INDEX UNIQ_ED67E1EBE5A0E336');
        $this->addSql('ALTER TABLE web_pages DROP cover_image_id');
        $this->addSql('ALTER TABLE contracts DROP billing_period_id');
        $this->addSql('ALTER TABLE contracts DROP customized');
        $this->addSql('ALTER TABLE contracts DROP lock_in_date');
        $this->addSql('ALTER TABLE contracts DROP closure_notice_period_max_value');
        $this->addSql('ALTER TABLE contracts DROP closure_notice_period_min_value');
        $this->addSql('ALTER TABLE contracts DROP closure_notice_period_unit_code');
        $this->addSql('ALTER TABLE contracts DROP closure_notice_period_value');
    }
}
