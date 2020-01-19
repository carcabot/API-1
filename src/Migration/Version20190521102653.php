<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190521102653 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE users ADD two_factor_authentication BOOLEAN');
        $this->addSql('UPDATE users SET two_factor_authentication = FALSE');
        $this->addSql('ALTER TABLE users ADD two_factor_authentication_code VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD two_factor_authentication_recipient VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD two_factor_authentication_status VARCHAR(254) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD two_factor_authentication_type VARCHAR(254) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ALTER two_factor_authentication SET NOT NULL');
        $this->addSql('COMMENT ON COLUMN users.two_factor_authentication_status IS \'(DC2Type:two_factor_authentication_status_enum)\'');
        $this->addSql('COMMENT ON COLUMN users.two_factor_authentication_type IS \'(DC2Type:two_factor_authentication_type_enum)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('postgresql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE users DROP two_factor_authentication');
        $this->addSql('ALTER TABLE users DROP two_factor_authentication_code');
        $this->addSql('ALTER TABLE users DROP two_factor_authentication_recipient');
        $this->addSql('ALTER TABLE users DROP two_factor_authentication_status');
        $this->addSql('ALTER TABLE users DROP two_factor_authentication_type');
    }
}
