<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180801145618 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE sms_histories_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE activity_sms_histories_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE sms_histories (id INT NOT NULL, direction VARCHAR(254) NOT NULL, message TEXT DEFAULT NULL, provider VARCHAR(254) NOT NULL, raw_message TEXT DEFAULT NULL, recipient_mobile_number VARCHAR(35) NOT NULL, sender_mobile_number VARCHAR(35) NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN sms_histories.direction IS \'(DC2Type:sms_direction_enum)\'');
        $this->addSql('COMMENT ON COLUMN sms_histories.provider IS \'(DC2Type:sms_web_service_partner_enum)\'');
        $this->addSql('COMMENT ON COLUMN sms_histories.recipient_mobile_number IS \'(DC2Type:phone_number)\'');
        $this->addSql('COMMENT ON COLUMN sms_histories.sender_mobile_number IS \'(DC2Type:phone_number)\'');
        $this->addSql('CREATE TABLE activity_sms_histories (id INT NOT NULL, activity_id INT NOT NULL, inbound_sms_id INT DEFAULT NULL, outbound_sms_id INT NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B942C1D081C06096 ON activity_sms_histories (activity_id)');
        $this->addSql('CREATE INDEX IDX_B942C1D0903B31F ON activity_sms_histories (inbound_sms_id)');
        $this->addSql('CREATE INDEX IDX_B942C1D0DBC222FC ON activity_sms_histories (outbound_sms_id)');
        $this->addSql('ALTER TABLE activity_sms_histories ADD CONSTRAINT FK_B942C1D081C06096 FOREIGN KEY (activity_id) REFERENCES activities (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE activity_sms_histories ADD CONSTRAINT FK_B942C1D0903B31F FOREIGN KEY (inbound_sms_id) REFERENCES sms_histories (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE activity_sms_histories ADD CONSTRAINT FK_B942C1D0DBC222FC FOREIGN KEY (outbound_sms_id) REFERENCES sms_histories (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE activity_sms_histories DROP CONSTRAINT FK_B942C1D0903B31F');
        $this->addSql('ALTER TABLE activity_sms_histories DROP CONSTRAINT FK_B942C1D0DBC222FC');
        $this->addSql('DROP SEQUENCE sms_histories_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE activity_sms_histories_id_seq CASCADE');
        $this->addSql('DROP TABLE sms_histories');
        $this->addSql('DROP TABLE activity_sms_histories');
    }
}
