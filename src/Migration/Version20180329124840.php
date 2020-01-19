<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20180329124840 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE bridge_users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE bridge_users (id INT NOT NULL, user_id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, auth_token TEXT NOT NULL, bridge_user_id TEXT NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_195602EEA09D7ECC ON bridge_users (bridge_user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_195602EEA76ED395 ON bridge_users (user_id)');
        $this->addSql('CREATE INDEX IDX_195602EE3414710B ON bridge_users (agent_id)');
        $this->addSql('CREATE INDEX IDX_195602EE61220EA6 ON bridge_users (creator_id)');
        $this->addSql('CREATE INDEX IDX_195602EEA09D7ECC ON bridge_users (bridge_user_id)');
        $this->addSql('ALTER TABLE bridge_users ADD CONSTRAINT FK_195602EEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE bridge_users ADD CONSTRAINT FK_195602EE3414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE bridge_users ADD CONSTRAINT FK_195602EE61220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE bridge_users_id_seq CASCADE');
        $this->addSql('DROP TABLE bridge_users');
    }
}
