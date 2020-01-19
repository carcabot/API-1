<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181019064554 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE campaigns ADD about TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE source_lists ADD venn_diagram_formula TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE contracts ADD bill_subscription_types JSONB');
        $this->addSql('UPDATE contracts SET bill_subscription_types = \'[]\'');
        $this->addSql('ALTER TABLE contracts ALTER bill_subscription_types SET NOT NULL');
        $this->addSql('ALTER TABLE contracts ADD location VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD bill_subscription_types JSONB');
        $this->addSql('UPDATE application_requests SET bill_subscription_types = \'[]\'');
        $this->addSql('ALTER TABLE application_requests ALTER bill_subscription_types SET NOT NULL');
        $this->addSql('ALTER TABLE application_requests ADD location VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_accounts DROP e_bill_subscription');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_requests DROP bill_subscription_types');
        $this->addSql('ALTER TABLE application_requests DROP location');
        $this->addSql('ALTER TABLE contracts DROP bill_subscription_types');
        $this->addSql('ALTER TABLE contracts DROP location');
        $this->addSql('ALTER TABLE customer_accounts ADD e_bill_subscription BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE source_lists DROP venn_diagram_formula');
        $this->addSql('ALTER TABLE campaigns DROP about');
    }
}
