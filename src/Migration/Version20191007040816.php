<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191007040816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE faq_configuration_categories_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE faq_configuration_categories (id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, position INT DEFAULT NULL, enabled BOOLEAN NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_CCEA82543414710B ON faq_configuration_categories (agent_id)');
        $this->addSql('CREATE INDEX IDX_CCEA825461220EA6 ON faq_configuration_categories (creator_id)');
        $this->addSql('ALTER TABLE faq_configuration_categories ADD CONSTRAINT FK_CCEA82543414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE faq_configuration_categories ADD CONSTRAINT FK_CCEA825461220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE faq_configurations ADD category_id INT');
        $this->addSql('ALTER TABLE faq_configurations ADD CONSTRAINT FK_35731EC12469DE2 FOREIGN KEY (category_id) REFERENCES faq_configuration_categories (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_35731EC12469DE2 ON faq_configurations (category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE faq_configurations DROP CONSTRAINT FK_35731EC12469DE2');
        $this->addSql('DROP SEQUENCE faq_configuration_categories_id_seq CASCADE');
        $this->addSql('DROP TABLE faq_configuration_categories');
        $this->addSql('DROP INDEX IDX_35731EC12469DE2');
        $this->addSql('ALTER TABLE faq_configurations DROP category_id');
    }
}
