<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20180423101952 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE commission_rates ADD value_currency VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE commission_rates DROP value_max_value');
        $this->addSql('ALTER TABLE commission_rates DROP value_min_value');
        $this->addSql('ALTER TABLE commission_rates DROP value_unit_code');
        $this->addSql('ALTER TABLE commission_rates ALTER value_value TYPE NUMERIC(19, 4)');
        $this->addSql('ALTER TABLE commission_rates ALTER value_value DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE commission_rates ADD value_max_value SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE commission_rates ADD value_min_value SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE commission_rates ADD value_unit_code TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE commission_rates DROP value_currency');
        $this->addSql('ALTER TABLE commission_rates ALTER value_value TYPE SMALLINT');
        $this->addSql('ALTER TABLE commission_rates ALTER value_value DROP DEFAULT');
    }
}
