<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180723031444 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE free_gifts_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE free_gifts (id INT NOT NULL, description TEXT DEFAULT NULL, name TEXT DEFAULT NULL, note TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE contracts ADD recurring_option BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE contracts ADD source_url TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD recurring_option BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD source_url TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE leads ADD source_url TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE addon_services ADD optional BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_accounts ADD source_url TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE free_gifts_id_seq CASCADE');
        $this->addSql('DROP TABLE free_gifts');
        $this->addSql('ALTER TABLE application_requests DROP recurring_option');
        $this->addSql('ALTER TABLE application_requests DROP source_url');
        $this->addSql('ALTER TABLE leads DROP source_url');
        $this->addSql('ALTER TABLE customer_accounts DROP source_url');
        $this->addSql('ALTER TABLE addon_services DROP optional');
        $this->addSql('ALTER TABLE contracts DROP recurring_option');
        $this->addSql('ALTER TABLE contracts DROP source_url');
    }
}
