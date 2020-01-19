<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181108112929 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_requests ADD keywords tsvector DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN application_requests.keywords IS \'(DC2Type:tsvector)\'');
        $this->addSql('CREATE INDEX IDX_C4BE0942BA06829F ON application_requests (application_request_number)');
        $this->addSql('CREATE INDEX IDX_C4BE0942AA5FB55E ON application_requests USING gin(keywords)');
        $this->addSql(<<<'SQL'
            WITH application_request_ts_keywords AS (
                SELECT id AS app_id, to_tsvector(application_request_number) AS ts_keywords from application_requests
            )
            UPDATE application_requests SET keywords = ts_keywords
            FROM application_request_ts_keywords
            WHERE id = app_id
SQL
        );

        $this->addSql('ALTER TABLE leads ADD keywords tsvector DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN leads.keywords IS \'(DC2Type:tsvector)\'');
        $this->addSql('CREATE INDEX IDX_17904552AA5FB55E ON leads USING gin(keywords)');
        $this->addSql(<<<'SQL'
            WITH lead_ts_keywords AS (
                SELECT id AS lead_id, to_tsvector(lead_number) AS ts_keywords from leads
            )
            UPDATE leads SET keywords = ts_keywords
            FROM lead_ts_keywords
            WHERE id = lead_id
SQL
        );

        $this->addSql('ALTER TABLE people ADD keywords tsvector DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN people.keywords IS \'(DC2Type:tsvector)\'');
        $this->addSql('CREATE INDEX IDX_28166A265E237E06 ON people (name)');
        $this->addSql('CREATE INDEX IDX_28166A26AA5FB55E ON people USING gin(keywords)');
        $this->addSql(<<<'SQL'
            WITH people_ts_keywords AS (
                SELECT id AS person_id, to_tsvector(name) AS ts_keywords from people
            )
            UPDATE people SET keywords = ts_keywords
            FROM people_ts_keywords
            WHERE id = person_id
SQL
        );

        $this->addSql('ALTER TABLE corporations ADD keywords tsvector DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN corporations.keywords IS \'(DC2Type:tsvector)\'');
        $this->addSql('CREATE INDEX IDX_6F3B37C7AA5FB55E ON corporations USING gin(keywords)');
        $this->addSql(<<<'SQL'
            WITH corporations_ts_keywords AS (
                SELECT id AS corporation_id, to_tsvector(name) AS ts_keywords from corporations
            )
            UPDATE corporations SET keywords = ts_keywords
            FROM corporations_ts_keywords
            WHERE id = corporation_id
SQL
        );

        $this->addSql('ALTER TABLE customer_accounts ADD keywords tsvector DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN customer_accounts.keywords IS \'(DC2Type:tsvector)\'');
        $this->addSql('CREATE INDEX IDX_E60DDD83AA5FB55E ON customer_accounts USING gin(keywords)');
        $this->addSql(<<<'SQL'
            WITH customer_accounts_ts_keywords AS (
                SELECT id AS customer_account_id, to_tsvector(account_number) AS ts_keywords from customer_accounts
            )
            UPDATE customer_accounts SET keywords = ts_keywords
            FROM customer_accounts_ts_keywords
            WHERE id = customer_account_id
SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX IDX_C4BE0942BA06829F');
        $this->addSql('DROP INDEX IDX_C4BE0942AA5FB55E');
        $this->addSql('ALTER TABLE application_requests DROP keywords');
        $this->addSql('DROP INDEX IDX_6F3B37C7AA5FB55E');
        $this->addSql('ALTER TABLE corporations DROP keywords');
        $this->addSql('DROP INDEX IDX_E60DDD83AA5FB55E');
        $this->addSql('ALTER TABLE customer_accounts DROP keywords');
        $this->addSql('DROP INDEX IDX_17904552AA5FB55E');
        $this->addSql('ALTER TABLE leads DROP keywords');
        $this->addSql('DROP INDEX IDX_28166A26AA5FB55E');
        $this->addSql('DROP INDEX IDX_28166A265E237E06');
        $this->addSql('ALTER TABLE people DROP keywords');
    }
}
