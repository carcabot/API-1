<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190123082158 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE item_lists_list_items DROP CONSTRAINT FK_F120ABDBCE208F53');
        $this->addSql('ALTER TABLE item_lists_list_items DROP CONSTRAINT FK_F120ABDB36F330DF');
        $this->addSql('ALTER TABLE item_lists_list_items ADD CONSTRAINT FK_F120ABDBCE208F53 FOREIGN KEY (list_item_id) REFERENCES list_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE item_lists_list_items ADD CONSTRAINT FK_F120ABDB36F330DF FOREIGN KEY (item_list_id) REFERENCES item_lists (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE offer_serial_numbers DROP CONSTRAINT FK_C289550A291980E8');
        $this->addSql('ALTER TABLE offer_serial_numbers ADD CONSTRAINT FK_C289550A291980E8 FOREIGN KEY (offer_list_item_id) REFERENCES offer_list_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE offer_serial_numbers DROP CONSTRAINT fk_c289550a291980e8');
        $this->addSql('ALTER TABLE offer_serial_numbers ADD CONSTRAINT fk_c289550a291980e8 FOREIGN KEY (offer_list_item_id) REFERENCES offer_list_items (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE item_lists_list_items DROP CONSTRAINT fk_f120abdb36f330df');
        $this->addSql('ALTER TABLE item_lists_list_items DROP CONSTRAINT fk_f120abdbce208f53');
        $this->addSql('ALTER TABLE item_lists_list_items ADD CONSTRAINT fk_f120abdb36f330df FOREIGN KEY (item_list_id) REFERENCES item_lists (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE item_lists_list_items ADD CONSTRAINT fk_f120abdbce208f53 FOREIGN KEY (list_item_id) REFERENCES list_items (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
