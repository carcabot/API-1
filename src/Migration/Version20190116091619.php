<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190116091619 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE offer_list_items DROP status');
        $this->addSql('ALTER TABLE offer_list_items ALTER valid_from DROP NOT NULL');
        $this->addSql('ALTER TABLE offer_list_items ALTER valid_through DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE offer_list_items ADD status TEXT NOT NULL');
        $this->addSql('ALTER TABLE offer_list_items ALTER valid_from SET NOT NULL');
        $this->addSql('ALTER TABLE offer_list_items ALTER valid_through SET NOT NULL');
    }
}
