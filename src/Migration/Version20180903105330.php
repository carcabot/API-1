<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180903105330 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE ticket_categories_ticket_categories');
        $this->addSql('ALTER TABLE ticket_categories ADD parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_categories ADD CONSTRAINT FK_AC60D43C727ACA70 FOREIGN KEY (parent_id) REFERENCES ticket_categories (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_AC60D43C727ACA70 ON ticket_categories (parent_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE ticket_categories_ticket_categories (ticket_category_source INT NOT NULL, ticket_category_target INT NOT NULL, PRIMARY KEY(ticket_category_source, ticket_category_target))');
        $this->addSql('CREATE INDEX idx_36bc05164b138e96 ON ticket_categories_ticket_categories (ticket_category_target)');
        $this->addSql('CREATE INDEX idx_36bc051652f6de19 ON ticket_categories_ticket_categories (ticket_category_source)');
        $this->addSql('ALTER TABLE ticket_categories_ticket_categories ADD CONSTRAINT fk_36bc05164b138e96 FOREIGN KEY (ticket_category_target) REFERENCES ticket_categories (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_categories_ticket_categories ADD CONSTRAINT fk_36bc051652f6de19 FOREIGN KEY (ticket_category_source) REFERENCES ticket_categories (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_categories DROP CONSTRAINT FK_AC60D43C727ACA70');
        $this->addSql('DROP INDEX IDX_AC60D43C727ACA70');
        $this->addSql('ALTER TABLE ticket_categories DROP parent_id');
    }
}
