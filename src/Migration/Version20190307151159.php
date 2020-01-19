<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190307151159 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE tickets ADD keywords tsvector DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN tickets.keywords IS \'(DC2Type:tsvector)\'');
        $this->addSql('CREATE INDEX IDX_54469DF4AA5FB55E ON tickets USING gin(keywords)');
        $this->addSql(<<<'SQL'
            WITH ticket_ts_keywords AS (
                SELECT id AS ticket_id, to_tsvector(ticket_number) AS ts_keywords from tickets
            )
            UPDATE tickets SET keywords = ts_keywords
            FROM ticket_ts_keywords
            WHERE id = ticket_id
SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX IDX_54469DF4AA5FB55E');
        $this->addSql('ALTER TABLE tickets DROP keywords');
    }
}
