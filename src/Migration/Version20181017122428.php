<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181017122428 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX uniq_97094aee4353ca4d');
        $this->addSql('CREATE INDEX IDX_97094AEE4353CA4D ON update_credits_actions (credits_transaction_id)');
        $this->addSql('ALTER TABLE transfer_credits_actions ADD recipient_id INT NOT NULL');
        $this->addSql('ALTER TABLE transfer_credits_actions ADD CONSTRAINT FK_69BEDAA1E92F8F78 FOREIGN KEY (recipient_id) REFERENCES customer_accounts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_69BEDAA1E92F8F78 ON transfer_credits_actions (recipient_id)');
        $this->addSql('ALTER TABLE earn_contract_credits_actions ADD sender_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE earn_contract_credits_actions ADD CONSTRAINT FK_B617BE0CF624B39D FOREIGN KEY (sender_id) REFERENCES customer_accounts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_B617BE0CF624B39D ON earn_contract_credits_actions (sender_id)');
        $this->addSql('ALTER TABLE earn_customer_credits_actions ADD sender_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE earn_customer_credits_actions ADD CONSTRAINT FK_1E61856CF624B39D FOREIGN KEY (sender_id) REFERENCES customer_accounts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_1E61856CF624B39D ON earn_customer_credits_actions (sender_id)');
        $this->addSql('ALTER TABLE allocate_credits_actions DROP CONSTRAINT FK_232D940B232D562B');
        $this->addSql('ALTER TABLE allocate_credits_actions ADD recipient_id INT NOT NULL');
        $this->addSql('ALTER TABLE allocate_credits_actions ADD CONSTRAINT FK_232D940BE92F8F78 FOREIGN KEY (recipient_id) REFERENCES contracts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE allocate_credits_actions ADD CONSTRAINT FK_232D940B232D562B FOREIGN KEY (object_id) REFERENCES customer_accounts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_232D940BE92F8F78 ON allocate_credits_actions (recipient_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE transfer_credits_actions DROP CONSTRAINT FK_69BEDAA1E92F8F78');
        $this->addSql('DROP INDEX IDX_69BEDAA1E92F8F78');
        $this->addSql('ALTER TABLE transfer_credits_actions DROP recipient_id');
        $this->addSql('ALTER TABLE earn_contract_credits_actions DROP CONSTRAINT FK_B617BE0CF624B39D');
        $this->addSql('DROP INDEX IDX_B617BE0CF624B39D');
        $this->addSql('ALTER TABLE earn_contract_credits_actions DROP sender_id');
        $this->addSql('ALTER TABLE earn_customer_credits_actions DROP CONSTRAINT FK_1E61856CF624B39D');
        $this->addSql('DROP INDEX IDX_1E61856CF624B39D');
        $this->addSql('ALTER TABLE earn_customer_credits_actions DROP sender_id');
        $this->addSql('DROP INDEX IDX_97094AEE4353CA4D');
        $this->addSql('CREATE UNIQUE INDEX uniq_97094aee4353ca4d ON update_credits_actions (credits_transaction_id)');
        $this->addSql('ALTER TABLE allocate_credits_actions DROP CONSTRAINT FK_232D940BE92F8F78');
        $this->addSql('ALTER TABLE allocate_credits_actions DROP CONSTRAINT fk_232d940b232d562b');
        $this->addSql('DROP INDEX IDX_232D940BE92F8F78');
        $this->addSql('ALTER TABLE allocate_credits_actions DROP recipient_id');
        $this->addSql('ALTER TABLE allocate_credits_actions ADD CONSTRAINT fk_232d940b232d562b FOREIGN KEY (object_id) REFERENCES contracts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
