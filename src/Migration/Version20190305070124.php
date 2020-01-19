<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190305070124 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE earn_customer_affiliate_credits_actions ADD instrument_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE earn_customer_affiliate_credits_actions ADD CONSTRAINT FK_5C805CC5CF11D9C FOREIGN KEY (instrument_id) REFERENCES contracts (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_5C805CC5CF11D9C ON earn_customer_affiliate_credits_actions (instrument_id)');
        $this->addSql('ALTER TABLE earn_customer_credits_actions ADD instrument_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE earn_customer_credits_actions ADD CONSTRAINT FK_1E61856CCF11D9C FOREIGN KEY (instrument_id) REFERENCES contracts (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_1E61856CCF11D9C ON earn_customer_credits_actions (instrument_id)');
        $this->addSql('ALTER TABLE earn_contract_credits_actions ADD instrument_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE earn_contract_credits_actions ADD CONSTRAINT FK_B617BE0CCF11D9C FOREIGN KEY (instrument_id) REFERENCES contracts (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_B617BE0CCF11D9C ON earn_contract_credits_actions (instrument_id)');
        $this->addSql('ALTER TABLE earn_contract_affiliate_credits_actions ADD instrument_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE earn_contract_affiliate_credits_actions ADD CONSTRAINT FK_3F7803A4CF11D9C FOREIGN KEY (instrument_id) REFERENCES contracts (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_3F7803A4CF11D9C ON earn_contract_affiliate_credits_actions (instrument_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE earn_contract_credits_actions DROP CONSTRAINT FK_B617BE0CCF11D9C');
        $this->addSql('DROP INDEX IDX_B617BE0CCF11D9C');
        $this->addSql('ALTER TABLE earn_contract_credits_actions DROP instrument_id');
        $this->addSql('ALTER TABLE earn_customer_credits_actions DROP CONSTRAINT FK_1E61856CCF11D9C');
        $this->addSql('DROP INDEX IDX_1E61856CCF11D9C');
        $this->addSql('ALTER TABLE earn_customer_credits_actions DROP instrument_id');
        $this->addSql('ALTER TABLE earn_contract_affiliate_credits_actions DROP CONSTRAINT FK_3F7803A4CF11D9C');
        $this->addSql('DROP INDEX IDX_3F7803A4CF11D9C');
        $this->addSql('ALTER TABLE earn_contract_affiliate_credits_actions DROP instrument_id');
        $this->addSql('ALTER TABLE earn_customer_affiliate_credits_actions DROP CONSTRAINT FK_5C805CC5CF11D9C');
        $this->addSql('DROP INDEX IDX_5C805CC5CF11D9C');
        $this->addSql('ALTER TABLE earn_customer_affiliate_credits_actions DROP instrument_id');
    }
}
