<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181120112032 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE partner_commission_statements ADD statement_number VARCHAR(128) DEFAULT NULL');
        $this->addSql('UPDATE partner_commission_statements SET statement_number = id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_99FDAF26AFA166DA ON partner_commission_statements (statement_number)');
        $this->addSql('ALTER TABLE partner_commission_statements ALTER statement_number SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX UNIQ_99FDAF26AFA166DA');
        $this->addSql('ALTER TABLE partner_commission_statements DROP statement_number');
    }
}
