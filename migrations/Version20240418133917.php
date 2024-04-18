<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240418133917 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE label_has_artist (id INT AUTO_INCREMENT NOT NULL, label_id_id INT DEFAULT NULL, artist_id_id INT DEFAULT NULL, joining_date DATETIME NOT NULL, living_date DATETIME NOT NULL, INDEX IDX_FF9D48D9ED5A2859 (label_id_id), INDEX IDX_FF9D48D91F48AE04 (artist_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE label_has_artist ADD CONSTRAINT FK_FF9D48D9ED5A2859 FOREIGN KEY (label_id_id) REFERENCES label (id)');
        $this->addSql('ALTER TABLE label_has_artist ADD CONSTRAINT FK_FF9D48D91F48AE04 FOREIGN KEY (artist_id_id) REFERENCES artist (id)');
        $this->addSql('ALTER TABLE label_artist DROP FOREIGN KEY FK_E673A53633B92F39');
        $this->addSql('ALTER TABLE label_artist DROP FOREIGN KEY FK_E673A536B7970CF8');
        $this->addSql('DROP TABLE label_artist');
        $this->addSql('ALTER TABLE album DROP cover');
        $this->addSql('ALTER TABLE song DROP cover');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE label_artist (label_id INT NOT NULL, artist_id INT NOT NULL, INDEX IDX_E673A53633B92F39 (label_id), INDEX IDX_E673A536B7970CF8 (artist_id), PRIMARY KEY(label_id, artist_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE label_artist ADD CONSTRAINT FK_E673A53633B92F39 FOREIGN KEY (label_id) REFERENCES label (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE label_artist ADD CONSTRAINT FK_E673A536B7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE label_has_artist DROP FOREIGN KEY FK_FF9D48D9ED5A2859');
        $this->addSql('ALTER TABLE label_has_artist DROP FOREIGN KEY FK_FF9D48D91F48AE04');
        $this->addSql('DROP TABLE label_has_artist');
        $this->addSql('ALTER TABLE album ADD cover VARCHAR(125) NOT NULL');
        $this->addSql('ALTER TABLE song ADD cover VARCHAR(125) NOT NULL');
    }
}
