<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180704041113 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE customer_accounts_activities (customer_account_id INT NOT NULL, activity_id INT NOT NULL, PRIMARY KEY(customer_account_id, activity_id))');
        $this->addSql('CREATE INDEX IDX_26A6C34B66A25B38 ON customer_accounts_activities (customer_account_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_26A6C34B81C06096 ON customer_accounts_activities (activity_id)');
        $this->addSql('CREATE TABLE customer_accounts_digital_documents (customer_account_id INT NOT NULL, file_id INT NOT NULL, PRIMARY KEY(customer_account_id, file_id))');
        $this->addSql('CREATE INDEX IDX_43A8B5F66A25B38 ON customer_accounts_digital_documents (customer_account_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_43A8B5F93CB796C ON customer_accounts_digital_documents (file_id)');
        $this->addSql('ALTER TABLE customer_accounts_activities ADD CONSTRAINT FK_26A6C34B66A25B38 FOREIGN KEY (customer_account_id) REFERENCES customer_accounts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE customer_accounts_activities ADD CONSTRAINT FK_26A6C34B81C06096 FOREIGN KEY (activity_id) REFERENCES activities (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE customer_accounts_digital_documents ADD CONSTRAINT FK_43A8B5F66A25B38 FOREIGN KEY (customer_account_id) REFERENCES customer_accounts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE customer_accounts_digital_documents ADD CONSTRAINT FK_43A8B5F93CB796C FOREIGN KEY (file_id) REFERENCES digital_documents (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE customer_accounts_activities');
        $this->addSql('DROP TABLE customer_accounts_digital_documents');
    }
}
