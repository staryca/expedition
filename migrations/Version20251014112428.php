<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Additional\FileMarkerAdditional;
use App\Entity\Type\CategoryType;
use App\Helper\FileHelper;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251014112428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adding playlists';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE category_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE dance_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE improvisation_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE region_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE tradition_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE category (id INT NOT NULL, playlist VARCHAR(40) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE dance (id INT NOT NULL, name VARCHAR(100) NOT NULL, playlist VARCHAR(40) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE improvisation (id INT NOT NULL, name VARCHAR(30) NOT NULL, playlist VARCHAR(40) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE region (id INT NOT NULL, name VARCHAR(60) NOT NULL, playlist VARCHAR(40) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE tradition (id INT NOT NULL, name VARCHAR(50) NOT NULL, playlist VARCHAR(40) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE pack ADD playlist VARCHAR(40) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ALTER roles DROP DEFAULT');

        foreach (CategoryType::TYPES as $key => $type) {
            $this->addSql('INSERT INTO category (id, playlist) VALUES (' . $key . ', NULL)');
        }

        $dances = FileHelper::getArrayFromFile('src/DataFixtures/dances.csv');
        foreach ($dances as $key => $dance) {
            $this->addSql('INSERT INTO dance (id, name, playlist) VALUES (' . $key . ', \'' . $dance . '\', NULL)');
        }

        foreach (FileMarkerAdditional::getAllImprovisations() as $key => $improvisation) {
            $this->addSql('INSERT INTO improvisation (id, name, playlist) VALUES (' . $key . ', \'' . $improvisation . '\', NULL)');
        }

        $regions = FileHelper::getArrayFromFile('src/DataFixtures/regions.csv');
        foreach ($regions as $key => $region) {
            $this->addSql('INSERT INTO region (id, name, playlist) VALUES (' . $key . ', \'' . $region . '\', NULL)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE category_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE dance_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE improvisation_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE region_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE tradition_id_seq CASCADE');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE dance');
        $this->addSql('DROP TABLE improvisation');
        $this->addSql('DROP TABLE region');
        $this->addSql('DROP TABLE tradition');
        $this->addSql('ALTER TABLE "user" ALTER roles SET DEFAULT \'\'');
        $this->addSql('ALTER TABLE pack DROP playlist');
    }
}
