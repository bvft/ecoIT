<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220323154449 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE instructor_details (id INT AUTO_INCREMENT NOT NULL, person_details_id INT NOT NULL, desc_specs LONGTEXT NOT NULL, picture VARCHAR(255) NOT NULL, status SMALLINT DEFAULT NULL, UNIQUE INDEX UNIQ_1084AE8A8DA16437 (person_details_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE instructor_details ADD CONSTRAINT FK_1084AE8A8DA16437 FOREIGN KEY (person_details_id) REFERENCES person_details (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE instructor_details');
    }
}
