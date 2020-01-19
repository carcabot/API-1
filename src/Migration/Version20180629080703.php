<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180629080703 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE affiliate_programs_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE partners ALTER payout_cycle_max_value TYPE NUMERIC(19, 4) USING payout_cycle_max_value::numeric(19, 4)');
        $this->addSql('ALTER TABLE partners ALTER payout_cycle_max_value DROP DEFAULT');
        $this->addSql('ALTER TABLE affiliate_programs ADD image_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE affiliate_programs ADD agent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE affiliate_programs ADD creator_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE affiliate_programs DROP return_url');
        $this->addSql('ALTER TABLE affiliate_programs RENAME COLUMN position TO cover_image_id');
        $this->addSql('ALTER TABLE affiliate_programs ADD CONSTRAINT FK_39C3D30EE5A0E336 FOREIGN KEY (cover_image_id) REFERENCES digital_documents (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE affiliate_programs ADD CONSTRAINT FK_39C3D30E3DA5256D FOREIGN KEY (image_id) REFERENCES digital_documents (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE affiliate_programs ADD CONSTRAINT FK_39C3D30E3414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE affiliate_programs ADD CONSTRAINT FK_39C3D30E61220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_39C3D30EE5A0E336 ON affiliate_programs (cover_image_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_39C3D30E3DA5256D ON affiliate_programs (image_id)');
        $this->addSql('CREATE INDEX IDX_39C3D30E3414710B ON affiliate_programs (agent_id)');
        $this->addSql('CREATE INDEX IDX_39C3D30E61220EA6 ON affiliate_programs (creator_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE affiliate_programs_id_seq CASCADE');
        $this->addSql('ALTER TABLE affiliate_programs DROP CONSTRAINT FK_39C3D30EE5A0E336');
        $this->addSql('ALTER TABLE affiliate_programs DROP CONSTRAINT FK_39C3D30E3DA5256D');
        $this->addSql('ALTER TABLE affiliate_programs DROP CONSTRAINT FK_39C3D30E3414710B');
        $this->addSql('ALTER TABLE affiliate_programs DROP CONSTRAINT FK_39C3D30E61220EA6');
        $this->addSql('DROP INDEX UNIQ_39C3D30EE5A0E336');
        $this->addSql('DROP INDEX UNIQ_39C3D30E3DA5256D');
        $this->addSql('DROP INDEX IDX_39C3D30E3414710B');
        $this->addSql('DROP INDEX IDX_39C3D30E61220EA6');
        $this->addSql('ALTER TABLE affiliate_programs ADD "position" INT DEFAULT NULL');
        $this->addSql('ALTER TABLE affiliate_programs ADD return_url TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE affiliate_programs DROP cover_image_id');
        $this->addSql('ALTER TABLE affiliate_programs DROP image_id');
        $this->addSql('ALTER TABLE affiliate_programs DROP agent_id');
        $this->addSql('ALTER TABLE affiliate_programs DROP creator_id');
        $this->addSql('ALTER TABLE partners ALTER payout_cycle_max_value TYPE TEXT');
        $this->addSql('ALTER TABLE partners ALTER payout_cycle_max_value DROP DEFAULT');
    }
}
