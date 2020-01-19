<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190115100121 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ticket_categories ADD code VARCHAR(255) NULL');
        $this->addSql('UPDATE ticket_categories SET code = CONCAT(UPPER(name), id)');
        $this->addSql('ALTER TABLE ticket_categories ALTER code SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AC60D43C77153098 ON ticket_categories (code)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX UNIQ_AC60D43C77153098');
        $this->addSql('ALTER TABLE ticket_categories DROP code');
    }
}
