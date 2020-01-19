<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190506084800 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE customer_account_postal_addresses DROP CONSTRAINT FK_AAD553BC66A25B38');
        $this->addSql('ALTER TABLE customer_account_postal_addresses ADD CONSTRAINT FK_AAD553BC66A25B38 FOREIGN KEY (customer_account_id) REFERENCES customer_accounts (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE customer_account_postal_addresses DROP CONSTRAINT fk_aad553bc66a25b38');
        $this->addSql('ALTER TABLE customer_account_postal_addresses ADD CONSTRAINT fk_aad553bc66a25b38 FOREIGN KEY (customer_account_id) REFERENCES customer_accounts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
