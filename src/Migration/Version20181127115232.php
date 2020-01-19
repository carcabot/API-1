<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181127115232 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');
        $this->addSql(<<<'SQL'
            WITH app_request_data AS (
                SELECT contract_id, self_read_meter_option, recurring_option from application_requests
                WHERE contract_id IS NOT NULL
            )
            UPDATE contracts SET self_read_meter_option = app_request_data.self_read_meter_option, recurring_option = app_request_data.recurring_option
            FROM app_request_data
            WHERE id = app_request_data.contract_id;
SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');
    }
}
