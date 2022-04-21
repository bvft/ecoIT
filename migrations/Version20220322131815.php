<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220322131815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE person_details (id INT AUTO_INCREMENT NOT NULL, person_login_info_id INT NOT NULL, name VARCHAR(45) DEFAULT NULL, first_name VARCHAR(45) DEFAULT NULL, pseudo VARCHAR(45) DEFAULT NULL, UNIQUE INDEX UNIQ_50B8B8034528EC48 (person_login_info_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE person_login_info (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_3B40D907E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE person_details ADD CONSTRAINT FK_50B8B8034528EC48 FOREIGN KEY (person_login_info_id) REFERENCES person_login_info (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE person_details DROP FOREIGN KEY FK_50B8B8034528EC48');
        $this->addSql('DROP TABLE person_details');
        $this->addSql('DROP TABLE person_login_info');
    }
}
