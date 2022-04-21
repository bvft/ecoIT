<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220330085231 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE formations (id INT AUTO_INCREMENT NOT NULL, rubrics_id INT NOT NULL, person_details_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, picture VARCHAR(255) NOT NULL, short_text VARCHAR(255) NOT NULL, create_at DATETIME NOT NULL, status SMALLINT NOT NULL, number VARCHAR(10) NOT NULL, INDEX IDX_4090213753D89DD2 (rubrics_id), INDEX IDX_409021378DA16437 (person_details_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE lessons (id INT AUTO_INCREMENT NOT NULL, sections_id INT NOT NULL, content LONGTEXT NOT NULL, rank_order INT NOT NULL, INDEX IDX_3F4218D9577906E4 (sections_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE quiz (id INT AUTO_INCREMENT NOT NULL, sections_id INT NOT NULL, question VARCHAR(255) NOT NULL, answers JSON NOT NULL, solution SMALLINT NOT NULL, UNIQUE INDEX UNIQ_A412FA92577906E4 (sections_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rubrics (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, status SMALLINT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sections (id INT AUTO_INCREMENT NOT NULL, formations_id INT NOT NULL, rank_order INT NOT NULL, title VARCHAR(255) NOT NULL, number VARCHAR(10) NOT NULL, INDEX IDX_2B9643983BF5B0C2 (formations_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE student_formation_status (id INT AUTO_INCREMENT NOT NULL, status SMALLINT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE student_lesson_status (id INT AUTO_INCREMENT NOT NULL, status SMALLINT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE formations ADD CONSTRAINT FK_4090213753D89DD2 FOREIGN KEY (rubrics_id) REFERENCES rubrics (id)');
        $this->addSql('ALTER TABLE formations ADD CONSTRAINT FK_409021378DA16437 FOREIGN KEY (person_details_id) REFERENCES person_details (id)');
        $this->addSql('ALTER TABLE lessons ADD CONSTRAINT FK_3F4218D9577906E4 FOREIGN KEY (sections_id) REFERENCES sections (id)');
        $this->addSql('ALTER TABLE quiz ADD CONSTRAINT FK_A412FA92577906E4 FOREIGN KEY (sections_id) REFERENCES sections (id)');
        $this->addSql('ALTER TABLE sections ADD CONSTRAINT FK_2B9643983BF5B0C2 FOREIGN KEY (formations_id) REFERENCES formations (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sections DROP FOREIGN KEY FK_2B9643983BF5B0C2');
        $this->addSql('ALTER TABLE formations DROP FOREIGN KEY FK_4090213753D89DD2');
        $this->addSql('ALTER TABLE lessons DROP FOREIGN KEY FK_3F4218D9577906E4');
        $this->addSql('ALTER TABLE quiz DROP FOREIGN KEY FK_A412FA92577906E4');
        $this->addSql('DROP TABLE formations');
        $this->addSql('DROP TABLE lessons');
        $this->addSql('DROP TABLE quiz');
        $this->addSql('DROP TABLE rubrics');
        $this->addSql('DROP TABLE sections');
        $this->addSql('DROP TABLE student_formation_status');
        $this->addSql('DROP TABLE student_lesson_status');
    }
}
