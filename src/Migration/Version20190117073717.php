<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190117073717 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE credits_schemes DROP CONSTRAINT fk_41d99d6273f32dd8');
        $this->addSql('ALTER TABLE credits_configurations DROP CONSTRAINT fk_6f8f6b9b12469de2');
        $this->addSql('DROP SEQUENCE credits_configurations_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE credits_categories_id_seq CASCADE');
        $this->addSql('DROP TABLE credits_configurations');
        $this->addSql('DROP TABLE credits_categories');
        $this->addSql('DROP INDEX idx_41d99d6273f32dd8');
        $this->addSql('ALTER TABLE credits_schemes ADD description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE credits_schemes ADD valid_period_max_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE credits_schemes ADD valid_period_min_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE credits_schemes ADD valid_period_unit_code TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE credits_schemes ADD valid_period_value NUMERIC(19, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE credits_schemes DROP configuration_id');
        $this->addSql('ALTER TABLE payments ADD return_message TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE credits_configurations_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE credits_categories_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE credits_configurations (id INT NOT NULL, category_id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, description TEXT DEFAULT NULL, name TEXT NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, valid_period_max_value NUMERIC(19, 4) DEFAULT NULL, valid_period_min_value NUMERIC(19, 4) DEFAULT NULL, valid_period_unit_code TEXT DEFAULT NULL, valid_period_value NUMERIC(19, 4) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_6f8f6b9b61220ea6 ON credits_configurations (creator_id)');
        $this->addSql('CREATE INDEX idx_6f8f6b9b3414710b ON credits_configurations (agent_id)');
        $this->addSql('CREATE INDEX idx_6f8f6b9b12469de2 ON credits_configurations (category_id)');
        $this->addSql('CREATE TABLE credits_categories (id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, description TEXT DEFAULT NULL, name TEXT NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_68a9c6593414710b ON credits_categories (agent_id)');
        $this->addSql('CREATE INDEX idx_68a9c65961220ea6 ON credits_categories (creator_id)');
        $this->addSql('ALTER TABLE credits_configurations ADD CONSTRAINT fk_6f8f6b9b61220ea6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE credits_configurations ADD CONSTRAINT fk_6f8f6b9b3414710b FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE credits_configurations ADD CONSTRAINT fk_6f8f6b9b12469de2 FOREIGN KEY (category_id) REFERENCES credits_categories (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE credits_categories ADD CONSTRAINT fk_68a9c65961220ea6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE credits_categories ADD CONSTRAINT fk_68a9c6593414710b FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE credits_schemes ADD configuration_id INT NOT NULL');
        $this->addSql('ALTER TABLE credits_schemes DROP description');
        $this->addSql('ALTER TABLE credits_schemes DROP valid_period_max_value');
        $this->addSql('ALTER TABLE credits_schemes DROP valid_period_min_value');
        $this->addSql('ALTER TABLE credits_schemes DROP valid_period_unit_code');
        $this->addSql('ALTER TABLE credits_schemes DROP valid_period_value');
        $this->addSql('ALTER TABLE credits_schemes ADD CONSTRAINT fk_41d99d6273f32dd8 FOREIGN KEY (configuration_id) REFERENCES credits_configurations (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_41d99d6273f32dd8 ON credits_schemes (configuration_id)');
        $this->addSql('ALTER TABLE payments DROP return_message');
    }
}
