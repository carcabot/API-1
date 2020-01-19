<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190109094357 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');
        $this->addSql('DELETE FROM orders where object_id is null');
        $this->addSql('ALTER TABLE orders DROP CONSTRAINT FK_E52FFDEE232D562B');
        $this->addSql('ALTER TABLE orders ALTER object_id SET NOT NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE232D562B FOREIGN KEY (object_id) REFERENCES contracts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE orders DROP CONSTRAINT fk_e52ffdee232d562b');
        $this->addSql('ALTER TABLE orders ALTER object_id DROP NOT NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT fk_e52ffdee232d562b FOREIGN KEY (object_id) REFERENCES contracts (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
