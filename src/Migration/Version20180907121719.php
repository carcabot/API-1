<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180907121719 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE campaigns ADD thumbnail_image_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE campaigns ADD CONSTRAINT FK_E3737470145E489E FOREIGN KEY (thumbnail_image_id) REFERENCES digital_documents (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E3737470145E489E ON campaigns (thumbnail_image_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE campaigns DROP CONSTRAINT FK_E3737470145E489E');
        $this->addSql('DROP INDEX UNIQ_E3737470145E489E');
        $this->addSql('ALTER TABLE campaigns DROP thumbnail_image_id');
    }
}
