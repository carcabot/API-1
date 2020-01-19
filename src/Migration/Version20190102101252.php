<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190102101252 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE ticket_service_level_agreements_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE ticket_service_level_agreements (id INT NOT NULL, is_based_on_id INT DEFAULT NULL, ticket_id INT NOT NULL, description TEXT DEFAULT NULL, name TEXT NOT NULL, timer_max_value NUMERIC(19, 4) DEFAULT NULL, timer_min_value NUMERIC(19, 4) DEFAULT NULL, timer_unit_code TEXT DEFAULT NULL, timer_value NUMERIC(19, 4) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C7976FBB6E363BB ON ticket_service_level_agreements (is_based_on_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C7976FBB700047D2 ON ticket_service_level_agreements (ticket_id)');
        $this->addSql('CREATE TABLE ticket_service_level_agreements_opening_hours_specifications (ticket_service_level_agreement_id INT NOT NULL, opening_hours_specification_id INT NOT NULL, PRIMARY KEY(ticket_service_level_agreement_id, opening_hours_specification_id))');
        $this->addSql('CREATE INDEX IDX_83477E21545214F9 ON ticket_service_level_agreements_opening_hours_specifications (ticket_service_level_agreement_id)');
        $this->addSql('CREATE INDEX IDX_83477E21AF738CDC ON ticket_service_level_agreements_opening_hours_specifications (opening_hours_specification_id)');
        $this->addSql('ALTER TABLE ticket_service_level_agreements ADD CONSTRAINT FK_C7976FBB6E363BB FOREIGN KEY (is_based_on_id) REFERENCES service_level_agreements (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_service_level_agreements ADD CONSTRAINT FK_C7976FBB700047D2 FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_service_level_agreements_opening_hours_specifications ADD CONSTRAINT FK_83477E21545214F9 FOREIGN KEY (ticket_service_level_agreement_id) REFERENCES ticket_service_level_agreements (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_service_level_agreements_opening_hours_specifications ADD CONSTRAINT FK_83477E21AF738CDC FOREIGN KEY (opening_hours_specification_id) REFERENCES opening_hours_specifications (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tickets ADD service_level_agreement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF4C771E43A FOREIGN KEY (service_level_agreement_id) REFERENCES ticket_service_level_agreements (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_54469DF4C771E43A ON tickets (service_level_agreement_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE tickets DROP CONSTRAINT FK_54469DF4C771E43A');
        $this->addSql('ALTER TABLE ticket_service_level_agreements_opening_hours_specifications DROP CONSTRAINT FK_83477E21545214F9');
        $this->addSql('DROP SEQUENCE ticket_service_level_agreements_id_seq CASCADE');
        $this->addSql('DROP TABLE ticket_service_level_agreements');
        $this->addSql('DROP TABLE ticket_service_level_agreements_opening_hours_specifications');
        $this->addSql('DROP INDEX UNIQ_54469DF4C771E43A');
        $this->addSql('ALTER TABLE tickets DROP service_level_agreement_id');
    }
}
