<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180927085954 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE email_campaign_source_lists (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE direct_mail_campaign_source_list_items (id INT NOT NULL, item_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C2DED481126F525E ON direct_mail_campaign_source_list_items (item_id)');
        $this->addSql('CREATE TABLE direct_mail_campaign_source_lists (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE sms_campaign_source_lists (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE email_campaign_source_lists ADD CONSTRAINT FK_C88BDBE6BF396750 FOREIGN KEY (id) REFERENCES item_lists (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE direct_mail_campaign_source_list_items ADD CONSTRAINT FK_C2DED481126F525E FOREIGN KEY (item_id) REFERENCES postal_addresses (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE direct_mail_campaign_source_list_items ADD CONSTRAINT FK_C2DED481BF396750 FOREIGN KEY (id) REFERENCES list_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE direct_mail_campaign_source_lists ADD CONSTRAINT FK_E6BEA8C4BF396750 FOREIGN KEY (id) REFERENCES item_lists (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sms_campaign_source_lists ADD CONSTRAINT FK_2F1302AEBF396750 FOREIGN KEY (id) REFERENCES item_lists (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE direct_mail_source_list_items');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE direct_mail_source_list_items (id INT NOT NULL, item_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_cdb258e4126f525e ON direct_mail_source_list_items (item_id)');
        $this->addSql('ALTER TABLE direct_mail_source_list_items ADD CONSTRAINT fk_cdb258e4bf396750 FOREIGN KEY (id) REFERENCES list_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE direct_mail_source_list_items ADD CONSTRAINT fk_cdb258e4126f525e FOREIGN KEY (item_id) REFERENCES postal_addresses (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE email_campaign_source_lists');
        $this->addSql('DROP TABLE direct_mail_campaign_source_list_items');
        $this->addSql('DROP TABLE direct_mail_campaign_source_lists');
        $this->addSql('DROP TABLE sms_campaign_source_lists');
    }
}
