<?php

declare(strict_types=1);

namespace Danilocgsilva\EntityClone\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260728165755 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE database_credentials (id INT AUTO_INCREMENT NOT NULL, host VARCHAR(255) NOT NULL, user VARCHAR(255) NOT NULL, password LONGTEXT NOT NULL, database_name VARCHAR(255) DEFAULT NULL, port INT NOT NULL, PRIMARY KEY (id))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE database_credentials');
    }
}
