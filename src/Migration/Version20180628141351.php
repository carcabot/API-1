<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180628141351 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE partners ALTER payout_cycle_max_value TYPE NUMERIC(19, 4) USING payout_cycle_max_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE partners ALTER payout_cycle_max_value DROP DEFAULT');
        $this->addSql('ALTER TABLE partners ALTER payout_cycle_min_value TYPE NUMERIC(19, 4) USING payout_cycle_min_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE partners ALTER payout_cycle_min_value DROP DEFAULT');
        $this->addSql('ALTER TABLE partners ALTER payout_cycle_value TYPE NUMERIC(19, 4) USING payout_cycle_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE partners ALTER payout_cycle_value DROP DEFAULT');
        $this->addSql('ALTER TABLE tariff_rates ALTER inventory_level_max_value TYPE NUMERIC(19, 4) USING inventory_level_max_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE tariff_rates ALTER inventory_level_max_value DROP DEFAULT');
        $this->addSql('ALTER TABLE tariff_rates ALTER inventory_level_min_value TYPE NUMERIC(19, 4) USING inventory_level_min_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE tariff_rates ALTER inventory_level_min_value DROP DEFAULT');
        $this->addSql('ALTER TABLE tariff_rates ALTER inventory_level_value TYPE NUMERIC(19, 4) USING inventory_level_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE tariff_rates ALTER inventory_level_value DROP DEFAULT');
        $this->addSql('ALTER TABLE tariff_rates ALTER min_contract_term_max_value TYPE NUMERIC(19, 4) USING min_contract_term_max_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE tariff_rates ALTER min_contract_term_max_value DROP DEFAULT');
        $this->addSql('ALTER TABLE tariff_rates ALTER min_contract_term_min_value TYPE NUMERIC(19, 4) USING min_contract_term_min_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE tariff_rates ALTER min_contract_term_min_value DROP DEFAULT');
        $this->addSql('ALTER TABLE tariff_rates ALTER min_contract_term_value TYPE NUMERIC(19, 4) USING min_contract_term_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE tariff_rates ALTER min_contract_term_value DROP DEFAULT');
        $this->addSql('ALTER TABLE contracts ALTER average_consumption_max_value TYPE NUMERIC(19, 4) USING average_consumption_max_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE contracts ALTER average_consumption_max_value DROP DEFAULT');
        $this->addSql('ALTER TABLE contracts ALTER average_consumption_min_value TYPE NUMERIC(19, 4) USING average_consumption_min_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE contracts ALTER average_consumption_min_value DROP DEFAULT');
        $this->addSql('ALTER TABLE contracts ALTER average_consumption_value TYPE NUMERIC(19, 4) USING average_consumption_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE contracts ALTER average_consumption_value DROP DEFAULT');
        $this->addSql('ALTER TABLE contracts ALTER closure_notice_period_max_value TYPE NUMERIC(19, 4) USING closure_notice_period_max_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE contracts ALTER closure_notice_period_max_value DROP DEFAULT');
        $this->addSql('ALTER TABLE contracts ALTER closure_notice_period_min_value TYPE NUMERIC(19, 4) USING closure_notice_period_min_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE contracts ALTER closure_notice_period_min_value DROP DEFAULT');
        $this->addSql('ALTER TABLE contracts ALTER closure_notice_period_value TYPE NUMERIC(19, 4) USING closure_notice_period_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE contracts ALTER closure_notice_period_value DROP DEFAULT');
        $this->addSql('ALTER TABLE application_requests ALTER average_consumption_max_value TYPE NUMERIC(19, 4) USING average_consumption_max_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE application_requests ALTER average_consumption_max_value DROP DEFAULT');
        $this->addSql('ALTER TABLE application_requests ALTER average_consumption_min_value TYPE NUMERIC(19, 4) USING average_consumption_min_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE application_requests ALTER average_consumption_min_value DROP DEFAULT');
        $this->addSql('ALTER TABLE application_requests ALTER average_consumption_value TYPE NUMERIC(19, 4) USING average_consumption_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE application_requests ALTER average_consumption_value DROP DEFAULT');
        $this->addSql('ALTER TABLE leads ALTER average_consumption_max_value TYPE NUMERIC(19, 4) USING average_consumption_max_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE leads ALTER average_consumption_max_value DROP DEFAULT');
        $this->addSql('ALTER TABLE leads ALTER average_consumption_min_value TYPE NUMERIC(19, 4) USING average_consumption_min_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE leads ALTER average_consumption_min_value DROP DEFAULT');
        $this->addSql('ALTER TABLE leads ALTER average_consumption_value TYPE NUMERIC(19, 4) USING average_consumption_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE leads ALTER average_consumption_value DROP DEFAULT');
        $this->addSql('ALTER TABLE leads ALTER purchase_time_frame_max_value TYPE NUMERIC(19, 4) USING purchase_time_frame_max_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE leads ALTER purchase_time_frame_max_value DROP DEFAULT');
        $this->addSql('ALTER TABLE leads ALTER purchase_time_frame_min_value TYPE NUMERIC(19, 4) USING purchase_time_frame_min_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE leads ALTER purchase_time_frame_min_value DROP DEFAULT');
        $this->addSql('ALTER TABLE leads ALTER purchase_time_frame_value TYPE NUMERIC(19, 4) USING purchase_time_frame_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE leads ALTER purchase_time_frame_value DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE tariff_rates ALTER inventory_level_max_value TYPE TEXT');
        $this->addSql('ALTER TABLE tariff_rates ALTER inventory_level_max_value DROP DEFAULT');
        $this->addSql('ALTER TABLE tariff_rates ALTER inventory_level_min_value TYPE TEXT');
        $this->addSql('ALTER TABLE tariff_rates ALTER inventory_level_min_value DROP DEFAULT');
        $this->addSql('ALTER TABLE tariff_rates ALTER inventory_level_value TYPE TEXT');
        $this->addSql('ALTER TABLE tariff_rates ALTER inventory_level_value DROP DEFAULT');
        $this->addSql('ALTER TABLE tariff_rates ALTER min_contract_term_max_value TYPE TEXT');
        $this->addSql('ALTER TABLE tariff_rates ALTER min_contract_term_max_value DROP DEFAULT');
        $this->addSql('ALTER TABLE tariff_rates ALTER min_contract_term_min_value TYPE TEXT');
        $this->addSql('ALTER TABLE tariff_rates ALTER min_contract_term_min_value DROP DEFAULT');
        $this->addSql('ALTER TABLE tariff_rates ALTER min_contract_term_value TYPE TEXT');
        $this->addSql('ALTER TABLE tariff_rates ALTER min_contract_term_value DROP DEFAULT');
        $this->addSql('ALTER TABLE application_requests ALTER average_consumption_max_value TYPE TEXT');
        $this->addSql('ALTER TABLE application_requests ALTER average_consumption_max_value DROP DEFAULT');
        $this->addSql('ALTER TABLE application_requests ALTER average_consumption_min_value TYPE TEXT');
        $this->addSql('ALTER TABLE application_requests ALTER average_consumption_min_value DROP DEFAULT');
        $this->addSql('ALTER TABLE application_requests ALTER average_consumption_value TYPE TEXT');
        $this->addSql('ALTER TABLE application_requests ALTER average_consumption_value DROP DEFAULT');
        $this->addSql('ALTER TABLE partners ALTER payout_cycle_max_value TYPE TEXT');
        $this->addSql('ALTER TABLE partners ALTER payout_cycle_max_value DROP DEFAULT');
        $this->addSql('ALTER TABLE partners ALTER payout_cycle_min_value TYPE TEXT');
        $this->addSql('ALTER TABLE partners ALTER payout_cycle_min_value DROP DEFAULT');
        $this->addSql('ALTER TABLE partners ALTER payout_cycle_value TYPE TEXT');
        $this->addSql('ALTER TABLE partners ALTER payout_cycle_value DROP DEFAULT');
        $this->addSql('ALTER TABLE leads ALTER average_consumption_max_value TYPE TEXT');
        $this->addSql('ALTER TABLE leads ALTER average_consumption_max_value DROP DEFAULT');
        $this->addSql('ALTER TABLE leads ALTER average_consumption_min_value TYPE TEXT');
        $this->addSql('ALTER TABLE leads ALTER average_consumption_min_value DROP DEFAULT');
        $this->addSql('ALTER TABLE leads ALTER average_consumption_value TYPE TEXT');
        $this->addSql('ALTER TABLE leads ALTER average_consumption_value DROP DEFAULT');
        $this->addSql('ALTER TABLE leads ALTER purchase_time_frame_max_value TYPE TEXT');
        $this->addSql('ALTER TABLE leads ALTER purchase_time_frame_max_value DROP DEFAULT');
        $this->addSql('ALTER TABLE leads ALTER purchase_time_frame_min_value TYPE TEXT');
        $this->addSql('ALTER TABLE leads ALTER purchase_time_frame_min_value DROP DEFAULT');
        $this->addSql('ALTER TABLE leads ALTER purchase_time_frame_value TYPE TEXT');
        $this->addSql('ALTER TABLE leads ALTER purchase_time_frame_value DROP DEFAULT');
        $this->addSql('ALTER TABLE contracts ALTER average_consumption_max_value TYPE TEXT');
        $this->addSql('ALTER TABLE contracts ALTER average_consumption_max_value DROP DEFAULT');
        $this->addSql('ALTER TABLE contracts ALTER average_consumption_min_value TYPE TEXT');
        $this->addSql('ALTER TABLE contracts ALTER average_consumption_min_value DROP DEFAULT');
        $this->addSql('ALTER TABLE contracts ALTER average_consumption_value TYPE TEXT');
        $this->addSql('ALTER TABLE contracts ALTER average_consumption_value DROP DEFAULT');
        $this->addSql('ALTER TABLE contracts ALTER closure_notice_period_max_value TYPE TEXT');
        $this->addSql('ALTER TABLE contracts ALTER closure_notice_period_max_value DROP DEFAULT');
        $this->addSql('ALTER TABLE contracts ALTER closure_notice_period_min_value TYPE TEXT');
        $this->addSql('ALTER TABLE contracts ALTER closure_notice_period_min_value DROP DEFAULT');
        $this->addSql('ALTER TABLE contracts ALTER closure_notice_period_value TYPE TEXT');
        $this->addSql('ALTER TABLE contracts ALTER closure_notice_period_value DROP DEFAULT');
    }
}
