<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181010083227 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE merchants_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE merchants (id INT NOT NULL, image_id INT DEFAULT NULL, description TEXT DEFAULT NULL, merchant_number VARCHAR(128) NOT NULL, name TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CC77B6C07EBF58BD ON merchants (merchant_number)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CC77B6C03DA5256D ON merchants (image_id)');
        $this->addSql('ALTER TABLE merchants ADD CONSTRAINT FK_CC77B6C03DA5256D FOREIGN KEY (image_id) REFERENCES digital_documents (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE offer_categories ADD image_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE offer_categories ADD category_number TEXT NOT NULL');
        $this->addSql('ALTER TABLE offer_categories ADD CONSTRAINT FK_A65985BC3DA5256D FOREIGN KEY (image_id) REFERENCES digital_documents (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A65985BC3DA5256D ON offer_categories (image_id)');
        $this->addSql('ALTER TABLE offers ADD seller_id INT NOT NULL');
        $this->addSql('ALTER TABLE offers DROP seller');
        $this->addSql('ALTER TABLE offers ADD CONSTRAINT FK_DA4604278DE820D9 FOREIGN KEY (seller_id) REFERENCES merchants (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_DA4604278DE820D9 ON offers (seller_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE offers DROP CONSTRAINT FK_DA4604278DE820D9');
        $this->addSql('DROP SEQUENCE merchants_id_seq CASCADE');
        $this->addSql('DROP TABLE merchants');
        $this->addSql('ALTER TABLE offer_categories DROP CONSTRAINT FK_A65985BC3DA5256D');
        $this->addSql('DROP INDEX UNIQ_A65985BC3DA5256D');
        $this->addSql('ALTER TABLE offer_categories DROP image_id');
        $this->addSql('ALTER TABLE offer_categories DROP category_number');
        $this->addSql('DROP INDEX IDX_DA4604278DE820D9');
        $this->addSql('ALTER TABLE offers ADD seller TEXT NOT NULL');
        $this->addSql('ALTER TABLE offers DROP seller_id');
    }
}
