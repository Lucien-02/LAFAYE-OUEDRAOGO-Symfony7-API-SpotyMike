<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240419074108 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE label_has_artist MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE label_has_artist DROP FOREIGN KEY FK_FF9D48D9ED5A2859');
        $this->addSql('ALTER TABLE label_has_artist DROP FOREIGN KEY FK_FF9D48D91F48AE04');
        $this->addSql('DROP INDEX IDX_FF9D48D9ED5A2859 ON label_has_artist');
        $this->addSql('DROP INDEX IDX_FF9D48D91F48AE04 ON label_has_artist');
        $this->addSql('DROP INDEX `primary` ON label_has_artist');
        $this->addSql('ALTER TABLE label_has_artist ADD label_id INT NOT NULL, ADD artist_id INT NOT NULL, DROP id, DROP label_id_id, DROP artist_id_id');
        $this->addSql('ALTER TABLE label_has_artist ADD CONSTRAINT FK_FF9D48D933B92F39 FOREIGN KEY (label_id) REFERENCES label (id)');
        $this->addSql('ALTER TABLE label_has_artist ADD CONSTRAINT FK_FF9D48D9B7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id)');
        $this->addSql('CREATE INDEX IDX_FF9D48D933B92F39 ON label_has_artist (label_id)');
        $this->addSql('CREATE INDEX IDX_FF9D48D9B7970CF8 ON label_has_artist (artist_id)');
        $this->addSql('ALTER TABLE label_has_artist ADD PRIMARY KEY (label_id, artist_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE label_has_artist DROP FOREIGN KEY FK_FF9D48D933B92F39');
        $this->addSql('ALTER TABLE label_has_artist DROP FOREIGN KEY FK_FF9D48D9B7970CF8');
        $this->addSql('DROP INDEX IDX_FF9D48D933B92F39 ON label_has_artist');
        $this->addSql('DROP INDEX IDX_FF9D48D9B7970CF8 ON label_has_artist');
        $this->addSql('ALTER TABLE label_has_artist ADD id INT AUTO_INCREMENT NOT NULL, ADD label_id_id INT DEFAULT NULL, ADD artist_id_id INT DEFAULT NULL, DROP label_id, DROP artist_id, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE label_has_artist ADD CONSTRAINT FK_FF9D48D9ED5A2859 FOREIGN KEY (label_id_id) REFERENCES label (id)');
        $this->addSql('ALTER TABLE label_has_artist ADD CONSTRAINT FK_FF9D48D91F48AE04 FOREIGN KEY (artist_id_id) REFERENCES artist (id)');
        $this->addSql('CREATE INDEX IDX_FF9D48D9ED5A2859 ON label_has_artist (label_id_id)');
        $this->addSql('CREATE INDEX IDX_FF9D48D91F48AE04 ON label_has_artist (artist_id_id)');
    }
}
