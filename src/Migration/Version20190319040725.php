<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190319040725 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE offer_categories ADD is_based_on_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE offer_categories ADD CONSTRAINT FK_A65985BC6E363BB FOREIGN KEY (is_based_on_id) REFERENCES offer_categories (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_A65985BC6E363BB ON offer_categories (is_based_on_id)');
        $this->addSql('ALTER TABLE offers ADD is_based_on_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE offers ALTER offer_number DROP NOT NULL');
        $this->addSql('ALTER TABLE offers ADD CONSTRAINT FK_DA4604276E363BB FOREIGN KEY (is_based_on_id) REFERENCES offers (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_DA4604276E363BB ON offers (is_based_on_id)');
        $this->addSql('ALTER TABLE offer_list_items ADD is_based_on_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE offer_list_items ADD CONSTRAINT FK_A427BE9A6E363BB FOREIGN KEY (is_based_on_id) REFERENCES offer_list_items (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_A427BE9A6E363BB ON offer_list_items (is_based_on_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE offer_categories DROP CONSTRAINT FK_A65985BC6E363BB');
        $this->addSql('DROP INDEX IDX_A65985BC6E363BB');
        $this->addSql('ALTER TABLE offer_categories DROP is_based_on_id');
        $this->addSql('ALTER TABLE offer_list_items DROP CONSTRAINT FK_A427BE9A6E363BB');
        $this->addSql('DROP INDEX IDX_A427BE9A6E363BB');
        $this->addSql('ALTER TABLE offer_list_items DROP is_based_on_id');
        $this->addSql('ALTER TABLE offers DROP CONSTRAINT FK_DA4604276E363BB');
        $this->addSql('DROP INDEX IDX_DA4604276E363BB');
        $this->addSql('ALTER TABLE offers DROP is_based_on_id');
        $this->addSql('ALTER TABLE offers ALTER offer_number SET NOT NULL');
    }
}
