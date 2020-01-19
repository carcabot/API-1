<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181204071155 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE url_tokens_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE url_tokens (id INT NOT NULL, token TEXT NOT NULL, valid_from TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, valid_through TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE application_requests_activities (application_request_id INT NOT NULL, activity_id INT NOT NULL, PRIMARY KEY(application_request_id, activity_id))');
        $this->addSql('CREATE INDEX IDX_DAC186BB35FD8949 ON application_requests_activities (application_request_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DAC186BB81C06096 ON application_requests_activities (activity_id)');
        $this->addSql('ALTER TABLE application_requests_activities ADD CONSTRAINT FK_DAC186BB35FD8949 FOREIGN KEY (application_request_id) REFERENCES application_requests (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE application_requests_activities ADD CONSTRAINT FK_DAC186BB81C06096 FOREIGN KEY (activity_id) REFERENCES activities (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE email_activities ADD type VARCHAR(254) DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN email_activities.type IS \'(DC2Type:email_type_enum)\'');
        $this->addSql('ALTER TABLE application_requests ADD customer_representative_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD representative_details_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD url_token_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests ADD CONSTRAINT FK_C4BE0942E82E0D1B FOREIGN KEY (customer_representative_id) REFERENCES customer_accounts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE application_requests ADD CONSTRAINT FK_C4BE0942CB367455 FOREIGN KEY (representative_details_id) REFERENCES people (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE application_requests ADD CONSTRAINT FK_C4BE094223BE8930 FOREIGN KEY (url_token_id) REFERENCES url_tokens (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C4BE0942E82E0D1B ON application_requests (customer_representative_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C4BE0942CB367455 ON application_requests (representative_details_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C4BE094223BE8930 ON application_requests (url_token_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_requests DROP CONSTRAINT FK_C4BE094223BE8930');
        $this->addSql('DROP SEQUENCE url_tokens_id_seq CASCADE');
        $this->addSql('DROP TABLE url_tokens');
        $this->addSql('DROP TABLE application_requests_activities');
        $this->addSql('ALTER TABLE application_requests DROP CONSTRAINT FK_C4BE0942E82E0D1B');
        $this->addSql('ALTER TABLE application_requests DROP CONSTRAINT FK_C4BE0942CB367455');
        $this->addSql('DROP INDEX IDX_C4BE0942E82E0D1B');
        $this->addSql('DROP INDEX UNIQ_C4BE0942CB367455');
        $this->addSql('DROP INDEX UNIQ_C4BE094223BE8930');
        $this->addSql('ALTER TABLE application_requests DROP customer_representative_id');
        $this->addSql('ALTER TABLE application_requests DROP representative_details_id');
        $this->addSql('ALTER TABLE application_requests DROP url_token_id');
        $this->addSql('ALTER TABLE email_activities DROP type');
    }
}
