<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190227061726 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE users ADD mobile_device_login BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE application_requests DROP CONSTRAINT FK_C4BE094223BE8930');
        $this->addSql('ALTER TABLE application_requests ADD CONSTRAINT FK_C4BE094223BE8930 FOREIGN KEY (url_token_id) REFERENCES url_tokens (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_requests DROP CONSTRAINT fk_c4be094223be8930');
        $this->addSql('ALTER TABLE application_requests ADD CONSTRAINT fk_c4be094223be8930 FOREIGN KEY (url_token_id) REFERENCES url_tokens (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users DROP mobile_device_login');
    }
}
