<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190125070533 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE offer_catalogs ADD cover_image_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE offer_catalogs ADD image_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE offer_catalogs ADD CONSTRAINT FK_8AD08768E5A0E336 FOREIGN KEY (cover_image_id) REFERENCES digital_documents (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE offer_catalogs ADD CONSTRAINT FK_8AD087683DA5256D FOREIGN KEY (image_id) REFERENCES digital_documents (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8AD08768E5A0E336 ON offer_catalogs (cover_image_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8AD087683DA5256D ON offer_catalogs (image_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE offer_catalogs DROP CONSTRAINT FK_8AD08768E5A0E336');
        $this->addSql('ALTER TABLE offer_catalogs DROP CONSTRAINT FK_8AD087683DA5256D');
        $this->addSql('DROP INDEX UNIQ_8AD08768E5A0E336');
        $this->addSql('DROP INDEX UNIQ_8AD087683DA5256D');
        $this->addSql('ALTER TABLE offer_catalogs DROP cover_image_id');
        $this->addSql('ALTER TABLE offer_catalogs DROP image_id');
    }
}
