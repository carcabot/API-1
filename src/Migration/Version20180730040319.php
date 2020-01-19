<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180730040319 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE customer_account_affiliate_program_urls_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE customer_account_affiliate_program_urls (id INT NOT NULL, affiliate_program_id INT NOT NULL, customer_id INT NOT NULL, status VARCHAR(254) NOT NULL, url TEXT NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_758504E6F374FF7 ON customer_account_affiliate_program_urls (affiliate_program_id)');
        $this->addSql('CREATE INDEX IDX_758504E69395C3F3 ON customer_account_affiliate_program_urls (customer_id)');
        $this->addSql('COMMENT ON COLUMN customer_account_affiliate_program_urls.status IS \'(DC2Type:url_status_enum)\'');
        $this->addSql('ALTER TABLE customer_account_affiliate_program_urls ADD CONSTRAINT FK_758504E6F374FF7 FOREIGN KEY (affiliate_program_id) REFERENCES affiliate_programs (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE customer_account_affiliate_program_urls ADD CONSTRAINT FK_758504E69395C3F3 FOREIGN KEY (customer_id) REFERENCES customer_accounts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE affiliate_programs ADD program_number VARCHAR(128) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE customer_account_affiliate_program_urls_id_seq CASCADE');
        $this->addSql('DROP TABLE customer_account_affiliate_program_urls');
        $this->addSql('ALTER TABLE affiliate_programs DROP program_number');
    }
}
