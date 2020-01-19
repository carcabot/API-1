<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190208043319 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE merchants ADD agent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE merchants ADD creator_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE merchants ADD date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE merchants ADD date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE merchants ADD CONSTRAINT FK_CC77B6C03414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE merchants ADD CONSTRAINT FK_CC77B6C061220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_CC77B6C03414710B ON merchants (agent_id)');
        $this->addSql('CREATE INDEX IDX_CC77B6C061220EA6 ON merchants (creator_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE merchants DROP CONSTRAINT FK_CC77B6C03414710B');
        $this->addSql('ALTER TABLE merchants DROP CONSTRAINT FK_CC77B6C061220EA6');
        $this->addSql('DROP INDEX IDX_CC77B6C03414710B');
        $this->addSql('DROP INDEX IDX_CC77B6C061220EA6');
        $this->addSql('ALTER TABLE merchants DROP agent_id');
        $this->addSql('ALTER TABLE merchants DROP creator_id');
        $this->addSql('ALTER TABLE merchants DROP date_created');
        $this->addSql('ALTER TABLE merchants DROP date_modified');
    }
}
