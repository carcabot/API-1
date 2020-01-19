<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180823090303 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE mailgun_events_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE email_campaign_schedules_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE mailgun_events (id INT NOT NULL, campaign_id INT NOT NULL, recipient_id INT NOT NULL, mailgun_event_id VARCHAR(255) NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, entity_type VARCHAR(128) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_AD7A93A5F639F774 ON mailgun_events (campaign_id)');
        $this->addSql('CREATE INDEX IDX_AD7A93A5E92F8F78 ON mailgun_events (recipient_id)');
        $this->addSql('CREATE TABLE mailgun_open_events (id INT NOT NULL, dates_opened JSONB NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE mailgun_complain_events (id INT NOT NULL, date_complained TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE mailgun_deliver_events (id INT NOT NULL, date_delivered TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE mailgun_click_events (id INT NOT NULL, url VARCHAR(255) NOT NULL, dates_clicked JSONB NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE mailgun_bounce_events (id INT NOT NULL, date_bounced TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE email_campaign_schedules (id INT NOT NULL, campaign_id INT NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, from_position INT NOT NULL, to_position INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F4E69CF9F639F774 ON email_campaign_schedules (campaign_id)');
        $this->addSql('ALTER TABLE mailgun_events ADD CONSTRAINT FK_AD7A93A5F639F774 FOREIGN KEY (campaign_id) REFERENCES campaigns (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mailgun_events ADD CONSTRAINT FK_AD7A93A5E92F8F78 FOREIGN KEY (recipient_id) REFERENCES email_campaign_source_list_items (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mailgun_open_events ADD CONSTRAINT FK_DE274AB0BF396750 FOREIGN KEY (id) REFERENCES mailgun_events (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mailgun_complain_events ADD CONSTRAINT FK_648C8981BF396750 FOREIGN KEY (id) REFERENCES mailgun_events (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mailgun_deliver_events ADD CONSTRAINT FK_5973394CBF396750 FOREIGN KEY (id) REFERENCES mailgun_events (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mailgun_click_events ADD CONSTRAINT FK_BC434A05BF396750 FOREIGN KEY (id) REFERENCES mailgun_events (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mailgun_bounce_events ADD CONSTRAINT FK_18F713B1BF396750 FOREIGN KEY (id) REFERENCES mailgun_events (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE email_campaign_schedules ADD CONSTRAINT FK_F4E69CF9F639F774 FOREIGN KEY (campaign_id) REFERENCES campaigns (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE mailgun_open_events DROP CONSTRAINT FK_DE274AB0BF396750');
        $this->addSql('ALTER TABLE mailgun_complain_events DROP CONSTRAINT FK_648C8981BF396750');
        $this->addSql('ALTER TABLE mailgun_deliver_events DROP CONSTRAINT FK_5973394CBF396750');
        $this->addSql('ALTER TABLE mailgun_click_events DROP CONSTRAINT FK_BC434A05BF396750');
        $this->addSql('ALTER TABLE mailgun_bounce_events DROP CONSTRAINT FK_18F713B1BF396750');
        $this->addSql('DROP SEQUENCE mailgun_events_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE email_campaign_schedules_id_seq CASCADE');
        $this->addSql('DROP TABLE mailgun_events');
        $this->addSql('DROP TABLE mailgun_open_events');
        $this->addSql('DROP TABLE mailgun_complain_events');
        $this->addSql('DROP TABLE mailgun_deliver_events');
        $this->addSql('DROP TABLE mailgun_click_events');
        $this->addSql('DROP TABLE mailgun_bounce_events');
        $this->addSql('DROP TABLE email_campaign_schedules');
    }
}
