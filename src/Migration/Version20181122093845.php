<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181122093845 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE offers DROP CONSTRAINT fk_da460427c54c8c93');
        $this->addSql('DROP SEQUENCE offer_types_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE offer_serial_numbers_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE offer_serial_numbers (id INT NOT NULL, offer_list_item_id INT NOT NULL, order_item_id INT DEFAULT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, date_purchased TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, serial_number VARCHAR(255) NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C289550A291980E8 ON offer_serial_numbers (offer_list_item_id)');
        $this->addSql('CREATE INDEX IDX_C289550AE415FB15 ON offer_serial_numbers (order_item_id)');
        $this->addSql('CREATE INDEX IDX_C289550A3414710B ON offer_serial_numbers (agent_id)');
        $this->addSql('CREATE INDEX IDX_C289550A61220EA6 ON offer_serial_numbers (creator_id)');
        $this->addSql('ALTER TABLE offer_serial_numbers ADD CONSTRAINT FK_C289550A291980E8 FOREIGN KEY (offer_list_item_id) REFERENCES offer_list_items (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE offer_serial_numbers ADD CONSTRAINT FK_C289550AE415FB15 FOREIGN KEY (order_item_id) REFERENCES order_items (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE offer_serial_numbers ADD CONSTRAINT FK_C289550A3414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE offer_serial_numbers ADD CONSTRAINT FK_C289550A61220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE offer_types');
        $this->addSql('DROP INDEX idx_da460427c54c8c93');
        $this->addSql('ALTER TABLE offers ADD type VARCHAR(254) NULL');
        $this->addSql('UPDATE offers set type=\'VOUCHER\'');
        $this->addSql('ALTER TABLE offers ALTER type SET NOT NULL');
        $this->addSql('ALTER TABLE offers DROP type_id');
        $this->addSql('COMMENT ON COLUMN offers.type IS \'(DC2Type:offer_type_enum)\'');
        $this->addSql('ALTER TABLE orders ADD object_id INT NULL');
        $this->addSql('ALTER TABLE orders ADD total_price_max_price NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD total_price_min_price NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD total_price_price NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD total_price_price_currency VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE232D562B FOREIGN KEY (object_id) REFERENCES contracts (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_E52FFDEE232D562B ON orders (object_id)');
        $this->addSql('ALTER TABLE order_items DROP CONSTRAINT fk_62809db053c674ee');
        $this->addSql('DROP INDEX idx_62809db053c674ee');
        $this->addSql('DELETE FROM order_items');
        $this->addSql('ALTER TABLE order_items RENAME COLUMN offer_id TO offer_list_item_id');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB0291980E8 FOREIGN KEY (offer_list_item_id) REFERENCES offer_list_items (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_62809DB0291980E8 ON order_items (offer_list_item_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE offer_serial_numbers_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE offer_types_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE offer_types (id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, description TEXT DEFAULT NULL, name TEXT NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_da1df4d961220ea6 ON offer_types (creator_id)');
        $this->addSql('CREATE INDEX idx_da1df4d93414710b ON offer_types (agent_id)');
        $this->addSql('ALTER TABLE offer_types ADD CONSTRAINT fk_da1df4d961220ea6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE offer_types ADD CONSTRAINT fk_da1df4d93414710b FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE offer_serial_numbers');
        $this->addSql('ALTER TABLE offers DROP type');
        $this->addSql('ALTER TABLE orders DROP CONSTRAINT FK_E52FFDEE232D562B');
        $this->addSql('DROP INDEX IDX_E52FFDEE232D562B');
        $this->addSql('ALTER TABLE orders DROP object_id');
        $this->addSql('ALTER TABLE orders DROP total_price_max_price');
        $this->addSql('ALTER TABLE orders DROP total_price_min_price');
        $this->addSql('ALTER TABLE orders DROP total_price_price');
        $this->addSql('ALTER TABLE orders DROP total_price_price_currency');
        $this->addSql('ALTER TABLE order_items DROP CONSTRAINT FK_62809DB0291980E8');
        $this->addSql('DROP INDEX IDX_62809DB0291980E8');
        $this->addSql('ALTER TABLE order_items RENAME COLUMN offer_list_item_id TO offer_id');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT fk_62809db053c674ee FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_62809db053c674ee ON order_items (offer_id)');
    }
}
