<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190415061043 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE customer_blacklists_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE customer_blacklists (id INT NOT NULL, customer_id INT DEFAULT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, action TEXT NOT NULL, identification TEXT NOT NULL, name TEXT NOT NULL, reason TEXT DEFAULT NULL, remarks TEXT DEFAULT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7B2EAB759395C3F3 ON customer_blacklists (customer_id)');
        $this->addSql('CREATE INDEX IDX_7B2EAB753414710B ON customer_blacklists (agent_id)');
        $this->addSql('CREATE INDEX IDX_7B2EAB7561220EA6 ON customer_blacklists (creator_id)');
        $this->addSql('ALTER TABLE customer_blacklists ADD CONSTRAINT FK_7B2EAB759395C3F3 FOREIGN KEY (customer_id) REFERENCES customer_accounts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE customer_blacklists ADD CONSTRAINT FK_7B2EAB753414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE customer_blacklists ADD CONSTRAINT FK_7B2EAB7561220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE customer_accounts RENAME COLUMN date_black_listed TO date_blacklisted');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE customer_blacklists_id_seq CASCADE');
        $this->addSql('DROP TABLE customer_blacklists');
        $this->addSql('ALTER TABLE customer_accounts RENAME COLUMN date_blacklisted TO date_black_listed');
    }
}
