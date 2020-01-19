<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190104061418 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE profiles_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE roles_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE role_profile_modules_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE modules_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE departments_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE profiles (id INT NOT NULL, parent_id INT DEFAULT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, description TEXT DEFAULT NULL, enabled BOOLEAN NOT NULL, name TEXT NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8B308530727ACA70 ON profiles (parent_id)');
        $this->addSql('CREATE INDEX IDX_8B3085303414710B ON profiles (agent_id)');
        $this->addSql('CREATE INDEX IDX_8B30853061220EA6 ON profiles (creator_id)');
        $this->addSql('CREATE TABLE users_roles (user_id INT NOT NULL, role_id INT NOT NULL, PRIMARY KEY(user_id, role_id))');
        $this->addSql('CREATE INDEX IDX_51498A8EA76ED395 ON users_roles (user_id)');
        $this->addSql('CREATE INDEX IDX_51498A8ED60322AC ON users_roles (role_id)');
        $this->addSql('CREATE TABLE roles (id INT NOT NULL, department_id INT DEFAULT NULL, parent_id INT DEFAULT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, description TEXT DEFAULT NULL, enabled BOOLEAN NOT NULL, name TEXT NOT NULL, profile_privilege BOOLEAN NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B63E2EC7AE80F5DF ON roles (department_id)');
        $this->addSql('CREATE INDEX IDX_B63E2EC7727ACA70 ON roles (parent_id)');
        $this->addSql('CREATE INDEX IDX_B63E2EC73414710B ON roles (agent_id)');
        $this->addSql('CREATE INDEX IDX_B63E2EC761220EA6 ON roles (creator_id)');
        $this->addSql('CREATE TABLE roles_profiles (role_id INT NOT NULL, profile_id INT NOT NULL, PRIMARY KEY(role_id, profile_id))');
        $this->addSql('CREATE INDEX IDX_A165F0D2D60322AC ON roles_profiles (role_id)');
        $this->addSql('CREATE INDEX IDX_A165F0D2CCFA12B8 ON roles_profiles (profile_id)');
        $this->addSql('CREATE TABLE role_profile_modules (id INT NOT NULL, parent_id INT DEFAULT NULL, profile_id INT DEFAULT NULL, role_id INT DEFAULT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, category VARCHAR(254) NOT NULL, description TEXT DEFAULT NULL, enabled BOOLEAN NOT NULL, name VARCHAR(254) NOT NULL, permissions JSONB NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_32DA81E1727ACA70 ON role_profile_modules (parent_id)');
        $this->addSql('CREATE INDEX IDX_32DA81E1CCFA12B8 ON role_profile_modules (profile_id)');
        $this->addSql('CREATE INDEX IDX_32DA81E1D60322AC ON role_profile_modules (role_id)');
        $this->addSql('CREATE INDEX IDX_32DA81E13414710B ON role_profile_modules (agent_id)');
        $this->addSql('CREATE INDEX IDX_32DA81E161220EA6 ON role_profile_modules (creator_id)');
        $this->addSql('COMMENT ON COLUMN role_profile_modules.category IS \'(DC2Type:module_category_enum)\'');
        $this->addSql('COMMENT ON COLUMN role_profile_modules.name IS \'(DC2Type:module_type_enum)\'');
        $this->addSql('CREATE TABLE modules (id INT NOT NULL, parent_id INT DEFAULT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, category VARCHAR(254) NOT NULL, description TEXT DEFAULT NULL, enabled BOOLEAN NOT NULL, name VARCHAR(254) NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2EB743D7727ACA70 ON modules (parent_id)');
        $this->addSql('CREATE INDEX IDX_2EB743D73414710B ON modules (agent_id)');
        $this->addSql('CREATE INDEX IDX_2EB743D761220EA6 ON modules (creator_id)');
        $this->addSql('COMMENT ON COLUMN modules.category IS \'(DC2Type:module_category_enum)\'');
        $this->addSql('COMMENT ON COLUMN modules.name IS \'(DC2Type:module_type_enum)\'');
        $this->addSql('CREATE TABLE departments (id INT NOT NULL, parent_id INT DEFAULT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, description TEXT DEFAULT NULL, enabled BOOLEAN NOT NULL, name TEXT NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_16AEB8D4727ACA70 ON departments (parent_id)');
        $this->addSql('CREATE INDEX IDX_16AEB8D43414710B ON departments (agent_id)');
        $this->addSql('CREATE INDEX IDX_16AEB8D461220EA6 ON departments (creator_id)');
        $this->addSql('ALTER TABLE profiles ADD CONSTRAINT FK_8B308530727ACA70 FOREIGN KEY (parent_id) REFERENCES profiles (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE profiles ADD CONSTRAINT FK_8B3085303414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE profiles ADD CONSTRAINT FK_8B30853061220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users_roles ADD CONSTRAINT FK_51498A8EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users_roles ADD CONSTRAINT FK_51498A8ED60322AC FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE roles ADD CONSTRAINT FK_B63E2EC7AE80F5DF FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE roles ADD CONSTRAINT FK_B63E2EC7727ACA70 FOREIGN KEY (parent_id) REFERENCES roles (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE roles ADD CONSTRAINT FK_B63E2EC73414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE roles ADD CONSTRAINT FK_B63E2EC761220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE roles_profiles ADD CONSTRAINT FK_A165F0D2D60322AC FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE roles_profiles ADD CONSTRAINT FK_A165F0D2CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE role_profile_modules ADD CONSTRAINT FK_32DA81E1727ACA70 FOREIGN KEY (parent_id) REFERENCES role_profile_modules (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE role_profile_modules ADD CONSTRAINT FK_32DA81E1CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE role_profile_modules ADD CONSTRAINT FK_32DA81E1D60322AC FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE role_profile_modules ADD CONSTRAINT FK_32DA81E13414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE role_profile_modules ADD CONSTRAINT FK_32DA81E161220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE modules ADD CONSTRAINT FK_2EB743D7727ACA70 FOREIGN KEY (parent_id) REFERENCES modules (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE modules ADD CONSTRAINT FK_2EB743D73414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE modules ADD CONSTRAINT FK_2EB743D761220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE departments ADD CONSTRAINT FK_16AEB8D4727ACA70 FOREIGN KEY (parent_id) REFERENCES departments (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE departments ADD CONSTRAINT FK_16AEB8D43414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE departments ADD CONSTRAINT FK_16AEB8D461220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE password_tokens ALTER token TYPE VARCHAR(50)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE profiles DROP CONSTRAINT FK_8B308530727ACA70');
        $this->addSql('ALTER TABLE roles_profiles DROP CONSTRAINT FK_A165F0D2CCFA12B8');
        $this->addSql('ALTER TABLE role_profile_modules DROP CONSTRAINT FK_32DA81E1CCFA12B8');
        $this->addSql('ALTER TABLE users_roles DROP CONSTRAINT FK_51498A8ED60322AC');
        $this->addSql('ALTER TABLE roles DROP CONSTRAINT FK_B63E2EC7727ACA70');
        $this->addSql('ALTER TABLE roles_profiles DROP CONSTRAINT FK_A165F0D2D60322AC');
        $this->addSql('ALTER TABLE role_profile_modules DROP CONSTRAINT FK_32DA81E1D60322AC');
        $this->addSql('ALTER TABLE role_profile_modules DROP CONSTRAINT FK_32DA81E1727ACA70');
        $this->addSql('ALTER TABLE modules DROP CONSTRAINT FK_2EB743D7727ACA70');
        $this->addSql('ALTER TABLE roles DROP CONSTRAINT FK_B63E2EC7AE80F5DF');
        $this->addSql('ALTER TABLE departments DROP CONSTRAINT FK_16AEB8D4727ACA70');
        $this->addSql('DROP SEQUENCE profiles_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE roles_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE role_profile_modules_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE modules_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE departments_id_seq CASCADE');
        $this->addSql('DROP TABLE profiles');
        $this->addSql('DROP TABLE users_roles');
        $this->addSql('DROP TABLE roles');
        $this->addSql('DROP TABLE roles_profiles');
        $this->addSql('DROP TABLE role_profile_modules');
        $this->addSql('DROP TABLE modules');
        $this->addSql('DROP TABLE departments');
        $this->addSql('ALTER TABLE password_tokens ALTER token TYPE VARCHAR(255)');
    }
}
