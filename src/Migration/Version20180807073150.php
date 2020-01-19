<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180807073150 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE campaign_categories_id_seq CASCADE');
        $this->addSql('DROP TABLE campaign_categories');
        $this->addSql('ALTER TABLE campaigns DROP CONSTRAINT FK_E373747091225532');
        $this->addSql('ALTER TABLE campaigns ALTER assignee_id DROP NOT NULL');
        $this->addSql('ALTER TABLE campaigns ALTER current_stage DROP NOT NULL');
        $this->addSql('ALTER TABLE campaigns ALTER priority DROP NOT NULL');
        $this->addSql('ALTER TABLE campaigns ADD CONSTRAINT FK_E373747091225532 FOREIGN KEY (expectation_list_id) REFERENCES campaign_expectation_lists (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE campaign_categories_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE campaign_categories (id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_fde466c03414710b ON campaign_categories (agent_id)');
        $this->addSql('CREATE INDEX idx_fde466c061220ea6 ON campaign_categories (creator_id)');
        $this->addSql('ALTER TABLE campaign_categories ADD CONSTRAINT fk_fde466c061220ea6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_categories ADD CONSTRAINT fk_fde466c03414710b FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaigns DROP CONSTRAINT fk_e373747091225532');
        $this->addSql('ALTER TABLE campaigns ALTER assignee_id SET NOT NULL');
        $this->addSql('ALTER TABLE campaigns ALTER current_stage SET NOT NULL');
        $this->addSql('ALTER TABLE campaigns ALTER priority SET NOT NULL');
        $this->addSql('ALTER TABLE campaigns ADD CONSTRAINT fk_e373747091225532 FOREIGN KEY (expectation_list_id) REFERENCES campaign_expectation_lists (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
