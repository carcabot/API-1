<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180816042257 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE tickets DROP CONSTRAINT fk_54469df4f7bfe87c');
        $this->addSql('ALTER TABLE tickets DROP CONSTRAINT fk_54469df46076db78');
        $this->addSql('ALTER TABLE tickets DROP CONSTRAINT FK_54469DF49395C3F3');
        $this->addSql('DROP INDEX idx_54469df4f7bfe87c');
        $this->addSql('DROP INDEX uniq_54469df4c54c8c93');
        $this->addSql('DROP INDEX idx_54469df46076db78');
        $this->addSql('DROP INDEX uniq_54469df4727aca70');
        $this->addSql('ALTER TABLE tickets ADD assignee_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tickets ADD subcategory_id INT NOT NULL');
        $this->addSql('ALTER TABLE tickets ADD date_closed TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE tickets DROP employee_assigned_id');
        $this->addSql('ALTER TABLE tickets DROP sub_category_id');
        $this->addSql('ALTER TABLE tickets DROP completion_date');
        $this->addSql('ALTER TABLE tickets ALTER category_id SET NOT NULL');
        $this->addSql('ALTER TABLE tickets ALTER type_id SET NOT NULL');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF459EC7D60 FOREIGN KEY (assignee_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF45DC6FE57 FOREIGN KEY (subcategory_id) REFERENCES ticket_categories (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF49395C3F3 FOREIGN KEY (customer_id) REFERENCES customer_accounts (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_54469DF459EC7D60 ON tickets (assignee_id)');
        $this->addSql('CREATE INDEX IDX_54469DF4727ACA70 ON tickets (parent_id)');
        $this->addSql('CREATE INDEX IDX_54469DF45DC6FE57 ON tickets (subcategory_id)');
        $this->addSql('CREATE INDEX IDX_54469DF4C54C8C93 ON tickets (type_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE tickets DROP CONSTRAINT FK_54469DF459EC7D60');
        $this->addSql('ALTER TABLE tickets DROP CONSTRAINT FK_54469DF45DC6FE57');
        $this->addSql('ALTER TABLE tickets DROP CONSTRAINT fk_54469df49395c3f3');
        $this->addSql('DROP INDEX IDX_54469DF459EC7D60');
        $this->addSql('DROP INDEX IDX_54469DF4727ACA70');
        $this->addSql('DROP INDEX IDX_54469DF45DC6FE57');
        $this->addSql('DROP INDEX IDX_54469DF4C54C8C93');
        $this->addSql('ALTER TABLE tickets ADD sub_category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tickets ADD completion_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE tickets DROP subcategory_id');
        $this->addSql('ALTER TABLE tickets DROP date_closed');
        $this->addSql('ALTER TABLE tickets ALTER category_id DROP NOT NULL');
        $this->addSql('ALTER TABLE tickets ALTER type_id DROP NOT NULL');
        $this->addSql('ALTER TABLE tickets RENAME COLUMN assignee_id TO employee_assigned_id');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT fk_54469df4f7bfe87c FOREIGN KEY (sub_category_id) REFERENCES ticket_categories (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT fk_54469df46076db78 FOREIGN KEY (employee_assigned_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT fk_54469df49395c3f3 FOREIGN KEY (customer_id) REFERENCES customer_accounts (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_54469df4f7bfe87c ON tickets (sub_category_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_54469df4c54c8c93 ON tickets (type_id)');
        $this->addSql('CREATE INDEX idx_54469df46076db78 ON tickets (employee_assigned_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_54469df4727aca70 ON tickets (parent_id)');
    }
}
