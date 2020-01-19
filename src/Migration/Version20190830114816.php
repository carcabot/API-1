<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190830114816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE quotations ADD accepted_offer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE quotations ADD file_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE quotations ADD url_token_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE quotations ALTER assignee_id DROP NOT NULL');
        $this->addSql('ALTER TABLE quotations ALTER contact_person_id DROP NOT NULL');
        $this->addSql('ALTER TABLE quotations ALTER customer_id DROP NOT NULL');
        $this->addSql('ALTER TABLE quotations ALTER contract_subtype DROP NOT NULL');
        $this->addSql('ALTER TABLE quotations ADD CONSTRAINT FK_A9F48EAE8920E704 FOREIGN KEY (accepted_offer_id) REFERENCES quotation_price_configurations (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quotations ADD CONSTRAINT FK_A9F48EAE93CB796C FOREIGN KEY (file_id) REFERENCES digital_documents (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quotations ADD CONSTRAINT FK_A9F48EAE23BE8930 FOREIGN KEY (url_token_id) REFERENCES url_tokens (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A9F48EAE8920E704 ON quotations (accepted_offer_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A9F48EAE93CB796C ON quotations (file_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A9F48EAE23BE8930 ON quotations (url_token_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE quotations DROP CONSTRAINT FK_A9F48EAE8920E704');
        $this->addSql('ALTER TABLE quotations DROP CONSTRAINT FK_A9F48EAE93CB796C');
        $this->addSql('ALTER TABLE quotations DROP CONSTRAINT FK_A9F48EAE23BE8930');
        $this->addSql('DROP INDEX UNIQ_A9F48EAE8920E704');
        $this->addSql('DROP INDEX UNIQ_A9F48EAE93CB796C');
        $this->addSql('DROP INDEX UNIQ_A9F48EAE23BE8930');
        $this->addSql('ALTER TABLE quotations DROP accepted_offer_id');
        $this->addSql('ALTER TABLE quotations DROP file_id');
        $this->addSql('ALTER TABLE quotations DROP url_token_id');
        $this->addSql('ALTER TABLE quotations ALTER assignee_id SET NOT NULL');
        $this->addSql('ALTER TABLE quotations ALTER contact_person_id SET NOT NULL');
        $this->addSql('ALTER TABLE quotations ALTER customer_id SET NOT NULL');
        $this->addSql('ALTER TABLE quotations ALTER contract_subtype SET NOT NULL');
    }
}
