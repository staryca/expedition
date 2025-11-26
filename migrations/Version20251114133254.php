<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Dto\TreeItemDto;
use App\Service\RitualService;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use League\Csv\Reader;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251114133254 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the ritual table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE ritual_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE ritual (id INT NOT NULL, parent_id INT DEFAULT NULL, name VARCHAR(120) NOT NULL, playlist VARCHAR(40) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_ED8FF2B0727ACA70 ON ritual (parent_id)');
        $this->addSql('ALTER TABLE ritual ADD CONSTRAINT FK_ED8FF2B0727ACA70 FOREIGN KEY (parent_id) REFERENCES ritual (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $rituals = $this->getListFromFile('src/DataFixtures/rituals.csv');
        $tree = RitualService::getTreeFromList($rituals);

        /** @var TreeItemDto[] $items */
        $items = [];
        $this->setParentListFromTree($tree, null, $items);

        foreach ($items as $item) {
            $this->addSql('INSERT INTO ritual (id, name, parent_id) VALUES (' . $item->getId() . ', \'' . $item->getName() . '\', ' . ($item->getParentId() ?: 'NULL') . ')');
        }

        $this->addSql('ALTER TABLE file_marker ADD ritual_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE file_marker ADD CONSTRAINT FK_3AD80641F8922643 FOREIGN KEY (ritual_id) REFERENCES ritual (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_3AD80641F8922643 ON file_marker (ritual_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE ritual_id_seq CASCADE');
        $this->addSql('ALTER TABLE ritual DROP CONSTRAINT FK_ED8FF2B0727ACA70');
        $this->addSql('DROP TABLE ritual');

        $this->addSql('ALTER TABLE "file_marker" DROP CONSTRAINT FK_3AD80641F8922643');
        $this->addSql('DROP INDEX IDX_3AD80641F8922643');
        $this->addSql('ALTER TABLE "file_marker" DROP ritual_id');
    }

    private function getListFromFile(string $filename): array
    {
        $result = [];

        $csv = Reader::from($filename);
        $csv->setDelimiter(';');
        foreach ($csv->getRecords() as $record) {
            $result[] = $record[0];
        }

        return $result;
    }

    private function setParentListFromTree(array $tree, ?int $parentId, array &$list): void
    {
        foreach ($tree as $name => $children) {
            $item = new TreeItemDto(count($list) + 1, $name, $parentId);
            $list[] = $item;

            $this->setParentListFromTree($children, $item->getId(), $list);
        }
    }
}
