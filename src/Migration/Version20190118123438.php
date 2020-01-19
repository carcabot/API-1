<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190118123438 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE credits_schemes ADD is_based_on_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE credits_schemes ADD CONSTRAINT FK_41D99D626E363BB FOREIGN KEY (is_based_on_id) REFERENCES credits_schemes (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_41D99D626E363BB ON credits_schemes (is_based_on_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE credits_schemes DROP CONSTRAINT FK_41D99D626E363BB');
        $this->addSql('DROP INDEX IDX_41D99D626E363BB');
        $this->addSql('ALTER TABLE credits_schemes DROP is_based_on_id');
    }
}
