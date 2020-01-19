<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190528070824 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE cron_job_schedules_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE disque_jobs_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE cron_job_schedules (id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, enabled BOOLEAN NOT NULL, intervals VARCHAR(255) DEFAULT NULL, job_type VARCHAR(255) NOT NULL, queue VARCHAR(254) NOT NULL, time TIME(0) WITHOUT TIME ZONE DEFAULT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_4A5839963414710B ON cron_job_schedules (agent_id)');
        $this->addSql('CREATE INDEX IDX_4A58399661220EA6 ON cron_job_schedules (creator_id)');
        $this->addSql('COMMENT ON COLUMN cron_job_schedules.intervals IS \'(DC2Type:dateinterval)\'');
        $this->addSql('COMMENT ON COLUMN cron_job_schedules.queue IS \'(DC2Type:queue_name_enum)\'');
        $this->addSql('CREATE TABLE disque_jobs (id INT NOT NULL, agent_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, body JSONB NOT NULL, job_number TEXT NOT NULL, queue VARCHAR(254) NOT NULL, status VARCHAR(254) NOT NULL, type TEXT NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DA519E543414710B ON disque_jobs (agent_id)');
        $this->addSql('CREATE INDEX IDX_DA519E5461220EA6 ON disque_jobs (creator_id)');
        $this->addSql('COMMENT ON COLUMN disque_jobs.queue IS \'(DC2Type:queue_name_enum)\'');
        $this->addSql('COMMENT ON COLUMN disque_jobs.status IS \'(DC2Type:job_status_enum)\'');
        $this->addSql('ALTER TABLE cron_job_schedules ADD CONSTRAINT FK_4A5839963414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE cron_job_schedules ADD CONSTRAINT FK_4A58399661220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE disque_jobs ADD CONSTRAINT FK_DA519E543414710B FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE disque_jobs ADD CONSTRAINT FK_DA519E5461220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE cron_job_schedules_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE disque_jobs_id_seq CASCADE');
        $this->addSql('DROP TABLE cron_job_schedules');
        $this->addSql('DROP TABLE disque_jobs');
    }
}
