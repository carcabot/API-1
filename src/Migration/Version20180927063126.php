<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180927063126 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE earn_contract_credits_actions DROP CONSTRAINT FK_B617BE0C65797862');
        $this->addSql('ALTER TABLE earn_contract_credits_actions ALTER scheme_id DROP NOT NULL');
        $this->addSql('ALTER TABLE earn_contract_credits_actions ADD CONSTRAINT FK_B617BE0C65797862 FOREIGN KEY (scheme_id) REFERENCES credits_schemes (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE earn_customer_credits_actions DROP CONSTRAINT FK_1E61856C65797862');
        $this->addSql('ALTER TABLE earn_customer_credits_actions ALTER scheme_id DROP NOT NULL');
        $this->addSql('ALTER TABLE earn_customer_credits_actions ADD CONSTRAINT FK_1E61856C65797862 FOREIGN KEY (scheme_id) REFERENCES credits_schemes (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE earn_contract_credits_actions DROP CONSTRAINT fk_b617be0c65797862');
        $this->addSql('ALTER TABLE earn_contract_credits_actions ALTER scheme_id SET NOT NULL');
        $this->addSql('ALTER TABLE earn_contract_credits_actions ADD CONSTRAINT fk_b617be0c65797862 FOREIGN KEY (scheme_id) REFERENCES credits_schemes (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE earn_customer_credits_actions DROP CONSTRAINT fk_1e61856c65797862');
        $this->addSql('ALTER TABLE earn_customer_credits_actions ALTER scheme_id SET NOT NULL');
        $this->addSql('ALTER TABLE earn_customer_credits_actions ADD CONSTRAINT fk_1e61856c65797862 FOREIGN KEY (scheme_id) REFERENCES credits_schemes (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
