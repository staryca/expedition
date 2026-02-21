<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use League\Csv\Reader;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260220131146 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add district table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE district_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE district (id INT NOT NULL, name VARCHAR(40) NOT NULL, playlist VARCHAR(40) DEFAULT NULL, PRIMARY KEY(id))');

        $this->addSql('INSERT INTO district (id, name, playlist) SELECT id, name, playlist FROM region');
        $this->addSql('TRUNCATE TABLE region');
        $regions = $this->getArrayFromFile('src/DataFixtures/regions.csv');
        foreach ($regions as $key => $region) {
            $this->addSql('INSERT INTO region (id, name, playlist) VALUES (' . $key . ', \'' . $region . '\', NULL)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE district_id_seq CASCADE');
        $this->addSql('DROP TABLE district');
    }

    private function getArrayFromFile(string $filename): array
    {
        $result = [];

        $csv = Reader::from($filename);
        $csv->setDelimiter(';');
        foreach ($csv->getRecords() as $record) {
            $result[$record[0]] = $record[1];
        }

        return $result;
    }
}
