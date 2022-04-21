<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220415082337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE student_quiz_status (id INT AUTO_INCREMENT NOT NULL, person_details_id INT DEFAULT NULL, sections_id INT DEFAULT NULL, answers JSON DEFAULT NULL, status SMALLINT NOT NULL, INDEX IDX_41A498468DA16437 (person_details_id), INDEX IDX_41A49846577906E4 (sections_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE student_quiz_status ADD CONSTRAINT FK_41A498468DA16437 FOREIGN KEY (person_details_id) REFERENCES person_details (id)');
        $this->addSql('ALTER TABLE student_quiz_status ADD CONSTRAINT FK_41A49846577906E4 FOREIGN KEY (sections_id) REFERENCES sections (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE student_quiz_status');
    }
}
