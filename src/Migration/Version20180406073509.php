<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20180406073509 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE customer_accounts ADD status VARCHAR(254) NOT NULL');
        $this->addSql('COMMENT ON COLUMN customer_accounts.status IS \'(DC2Type:customer_account_status_enum)\'');
        $this->addSql('UPDATE customer_accounts SET STATUS = \'INACTIVE\'');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE customer_accounts DROP status');
    }
}
