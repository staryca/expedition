<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251014102841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Base migration';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE file_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "file_marker_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE informant_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE organization_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE organization_informant_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE report_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE report_block_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE subject_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE tag_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE user_report_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE expedition (id INT NOT NULL, geo_point_id BIGINT DEFAULT NULL, name VARCHAR(200) NOT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, is_active BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_692907E5E237E06 ON expedition (name)');
        $this->addSql('CREATE INDEX IDX_692907E2E91B903 ON expedition (geo_point_id)');
        $this->addSql('CREATE TABLE file (id INT NOT NULL, subject_id INT DEFAULT NULL, report_block_id INT DEFAULT NULL, filename VARCHAR(255) DEFAULT NULL, path VARCHAR(300) DEFAULT NULL, type INT NOT NULL, comment TEXT DEFAULT NULL, is_processed BOOLEAN NOT NULL, url VARCHAR(500) DEFAULT NULL, size_text VARCHAR(20) DEFAULT NULL, is_deny BOOLEAN DEFAULT NULL, additional JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8C9F361023EDC87 ON file (subject_id)');
        $this->addSql('CREATE INDEX IDX_8C9F3610E98D1B71 ON file (report_block_id)');
        $this->addSql('CREATE TABLE file_tag (file_id INT NOT NULL, tag_id INT NOT NULL, PRIMARY KEY(file_id, tag_id))');
        $this->addSql('CREATE INDEX IDX_2CCA391A93CB796C ON file_tag (file_id)');
        $this->addSql('CREATE INDEX IDX_2CCA391ABAD26311 ON file_tag (tag_id)');
        $this->addSql('CREATE TABLE "file_marker" (id INT NOT NULL, file_id INT NOT NULL, report_block_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, category INT NOT NULL, start_time TIMESTAMP(3) WITHOUT TIME ZONE DEFAULT NULL, end_time TIMESTAMP(3) WITHOUT TIME ZONE DEFAULT NULL, notes TEXT DEFAULT NULL, decoding TEXT DEFAULT NULL, additional JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3AD8064193CB796C ON "file_marker" (file_id)');
        $this->addSql('CREATE INDEX IDX_3AD80641E98D1B71 ON "file_marker" (report_block_id)');
        $this->addSql('COMMENT ON COLUMN "file_marker".start_time IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "file_marker".end_time IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE file_marker_tag (file_marker_id INT NOT NULL, tag_id INT NOT NULL, PRIMARY KEY(file_marker_id, tag_id))');
        $this->addSql('CREATE INDEX IDX_E62A7B36C94BDF9D ON file_marker_tag (file_marker_id)');
        $this->addSql('CREATE INDEX IDX_E62A7B36BAD26311 ON file_marker_tag (tag_id)');
        $this->addSql('CREATE TABLE geo_point (id BIGINT NOT NULL, name VARCHAR(255) DEFAULT NULL, lat NUMERIC(9, 6) NOT NULL, lon NUMERIC(9, 6) NOT NULL, region VARCHAR(255) DEFAULT NULL, district VARCHAR(255) DEFAULT NULL, name_word_stress VARCHAR(255) DEFAULT NULL, subdistrict VARCHAR(255) DEFAULT NULL, name_ru VARCHAR(255) DEFAULT NULL, prefix_ru VARCHAR(50) DEFAULT NULL, prefix_be VARCHAR(50) DEFAULT NULL, region_id INT DEFAULT NULL, department_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE informant (id INT NOT NULL, geo_point_birth_id BIGINT DEFAULT NULL, geo_point_current_id BIGINT DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, gender INT NOT NULL, year_birth INT DEFAULT NULL, day_birth DATE DEFAULT NULL, year_died INT DEFAULT NULL, is_died BOOLEAN DEFAULT NULL, notes TEXT DEFAULT NULL, place_birth VARCHAR(1000) DEFAULT NULL, place_current VARCHAR(1000) DEFAULT NULL, phone VARCHAR(200) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, year_transfer INT DEFAULT NULL, confession VARCHAR(50) DEFAULT NULL, path_photo VARCHAR(200) DEFAULT NULL, url_photo VARCHAR(1000) DEFAULT NULL, date_created DATE NOT NULL, is_musician BOOLEAN DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6D319BE2C3900DE ON informant (geo_point_birth_id)');
        $this->addSql('CREATE INDEX IDX_6D319BE2CCF66B42 ON informant (geo_point_current_id)');
        $this->addSql('COMMENT ON COLUMN informant.notes IS \'Дадатковыя заўвагі пра інфарматара\'');
        $this->addSql('COMMENT ON COLUMN informant.place_birth IS \'Месца народжэння\'');
        $this->addSql('COMMENT ON COLUMN informant.year_transfer IS \'Год пераезду (напрыклад, пасля шлюбу)\'');
        $this->addSql('COMMENT ON COLUMN informant.confession IS \'Канфесія\'');
        $this->addSql('COMMENT ON COLUMN informant.path_photo IS \'Фотаздымак інфарматара (часова, перанясецца ў іншую табліцу)\'');
        $this->addSql('COMMENT ON COLUMN informant.url_photo IS \'Фотаздымак інфарматара URL (часова, перанясецца ў іншую табліцу)\'');
        $this->addSql('CREATE TABLE organization (id INT NOT NULL, geo_point_id BIGINT DEFAULT NULL, name VARCHAR(255) NOT NULL, type INT NOT NULL, description TEXT DEFAULT NULL, address VARCHAR(250) DEFAULT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, notes TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C1EE637C2E91B903 ON organization (geo_point_id)');
        $this->addSql('CREATE TABLE organization_informant (id INT NOT NULL, organization_id INT NOT NULL, informant_id INT NOT NULL, comments TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_550641E432C8A3DE ON organization_informant (organization_id)');
        $this->addSql('CREATE INDEX IDX_550641E4F4C9278 ON organization_informant (informant_id)');
        $this->addSql('CREATE TABLE pack (id SERIAL NOT NULL, name VARCHAR(100) NOT NULL, is_group BOOLEAN NOT NULL, is_man BOOLEAN NOT NULL, is_woman BOOLEAN NOT NULL, slug VARCHAR(110) NOT NULL, name_plural VARCHAR(30) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_97DE5E235E237E06 ON pack (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_97DE5E23989D9B62 ON pack (slug)');
        $this->addSql('CREATE TABLE report (id INT NOT NULL, expedition_id INT NOT NULL, geo_point_id BIGINT DEFAULT NULL, geo_notes TEXT DEFAULT NULL, date_action TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, lat NUMERIC(9, 6) DEFAULT NULL, lon NUMERIC(9, 6) DEFAULT NULL, notes TEXT DEFAULT NULL, temp JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C42F7784576EF81E ON report (expedition_id)');
        $this->addSql('CREATE INDEX IDX_C42F77842E91B903 ON report (geo_point_id)');
        $this->addSql('CREATE TABLE report_block (id INT NOT NULL, report_id INT NOT NULL, organization_id INT DEFAULT NULL, type INT NOT NULL, date_created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, description TEXT DEFAULT NULL, video_notes TEXT DEFAULT NULL, photo_notes TEXT DEFAULT NULL, user_notes TEXT DEFAULT NULL, additional JSON DEFAULT NULL, search_index TEXT DEFAULT NULL, code VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A907905B4BD2A4C0 ON report_block (report_id)');
        $this->addSql('CREATE INDEX IDX_A907905B32C8A3DE ON report_block (organization_id)');
        $this->addSql('CREATE TABLE report_block_informant (report_block_id INT NOT NULL, informant_id INT NOT NULL, PRIMARY KEY(report_block_id, informant_id))');
        $this->addSql('CREATE INDEX IDX_733A9ADAE98D1B71 ON report_block_informant (report_block_id)');
        $this->addSql('CREATE INDEX IDX_733A9ADAF4C9278 ON report_block_informant (informant_id)');
        $this->addSql('CREATE TABLE report_block_tag (report_block_id INT NOT NULL, tag_id INT NOT NULL, PRIMARY KEY(report_block_id, tag_id))');
        $this->addSql('CREATE INDEX IDX_4FB613A1E98D1B71 ON report_block_tag (report_block_id)');
        $this->addSql('CREATE INDEX IDX_4FB613A1BAD26311 ON report_block_tag (tag_id)');
        $this->addSql('CREATE TABLE subject (id INT NOT NULL, expedition_id INT DEFAULT NULL, report_block_id INT DEFAULT NULL, type INT NOT NULL, name VARCHAR(200) NOT NULL, model VARCHAR(150) DEFAULT NULL, digit VARCHAR(100) DEFAULT NULL, notes TEXT DEFAULT NULL, marked BOOLEAN DEFAULT NULL, has_text BOOLEAN DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FBCE3E7A576EF81E ON subject (expedition_id)');
        $this->addSql('CREATE INDEX IDX_FBCE3E7AE98D1B71 ON subject (report_block_id)');
        $this->addSql('CREATE TABLE tag (id INT NOT NULL, name VARCHAR(150) NOT NULL, sort_order INT NOT NULL, is_base BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_389B7835E237E06 ON tag (name)');
        $this->addSql('CREATE TABLE task (id SERIAL NOT NULL, report_id INT DEFAULT NULL, report_block_id INT DEFAULT NULL, informant_id INT DEFAULT NULL, content TEXT DEFAULT NULL, status INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_527EDB254BD2A4C0 ON task (report_id)');
        $this->addSql('CREATE INDEX IDX_527EDB25E98D1B71 ON task (report_block_id)');
        $this->addSql('CREATE INDEX IDX_527EDB25F4C9278 ON task (informant_id)');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, last_login TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, first_name VARCHAR(150) NOT NULL, last_name VARCHAR(150) NOT NULL, date_joined DATE NOT NULL, is_active BOOLEAN NOT NULL, nicks VARCHAR(200) DEFAULT NULL, email VARCHAR(200) DEFAULT NULL, roles VARCHAR(200) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE user_report (id INT NOT NULL, participant_id INT NOT NULL, report_id INT NOT NULL, role VARCHAR(20) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A17D6CB99D1C3019 ON user_report (participant_id)');
        $this->addSql('CREATE INDEX IDX_A17D6CB94BD2A4C0 ON user_report (report_id)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE expedition ADD CONSTRAINT FK_692907E2E91B903 FOREIGN KEY (geo_point_id) REFERENCES geo_point (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F361023EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F3610E98D1B71 FOREIGN KEY (report_block_id) REFERENCES report_block (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE file_tag ADD CONSTRAINT FK_2CCA391A93CB796C FOREIGN KEY (file_id) REFERENCES file (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE file_tag ADD CONSTRAINT FK_2CCA391ABAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "file_marker" ADD CONSTRAINT FK_3AD8064193CB796C FOREIGN KEY (file_id) REFERENCES file (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "file_marker" ADD CONSTRAINT FK_3AD80641E98D1B71 FOREIGN KEY (report_block_id) REFERENCES report_block (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE file_marker_tag ADD CONSTRAINT FK_E62A7B36C94BDF9D FOREIGN KEY (file_marker_id) REFERENCES "file_marker" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE file_marker_tag ADD CONSTRAINT FK_E62A7B36BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE informant ADD CONSTRAINT FK_6D319BE2C3900DE FOREIGN KEY (geo_point_birth_id) REFERENCES geo_point (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE informant ADD CONSTRAINT FK_6D319BE2CCF66B42 FOREIGN KEY (geo_point_current_id) REFERENCES geo_point (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C2E91B903 FOREIGN KEY (geo_point_id) REFERENCES geo_point (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organization_informant ADD CONSTRAINT FK_550641E432C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organization_informant ADD CONSTRAINT FK_550641E4F4C9278 FOREIGN KEY (informant_id) REFERENCES informant (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F7784576EF81E FOREIGN KEY (expedition_id) REFERENCES expedition (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F77842E91B903 FOREIGN KEY (geo_point_id) REFERENCES geo_point (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE report_block ADD CONSTRAINT FK_A907905B4BD2A4C0 FOREIGN KEY (report_id) REFERENCES report (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE report_block ADD CONSTRAINT FK_A907905B32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE report_block_informant ADD CONSTRAINT FK_733A9ADAE98D1B71 FOREIGN KEY (report_block_id) REFERENCES report_block (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE report_block_informant ADD CONSTRAINT FK_733A9ADAF4C9278 FOREIGN KEY (informant_id) REFERENCES informant (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE report_block_tag ADD CONSTRAINT FK_4FB613A1E98D1B71 FOREIGN KEY (report_block_id) REFERENCES report_block (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE report_block_tag ADD CONSTRAINT FK_4FB613A1BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE subject ADD CONSTRAINT FK_FBCE3E7A576EF81E FOREIGN KEY (expedition_id) REFERENCES expedition (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE subject ADD CONSTRAINT FK_FBCE3E7AE98D1B71 FOREIGN KEY (report_block_id) REFERENCES report_block (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB254BD2A4C0 FOREIGN KEY (report_id) REFERENCES report (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25E98D1B71 FOREIGN KEY (report_block_id) REFERENCES report_block (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25F4C9278 FOREIGN KEY (informant_id) REFERENCES informant (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_report ADD CONSTRAINT FK_A17D6CB99D1C3019 FOREIGN KEY (participant_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_report ADD CONSTRAINT FK_A17D6CB94BD2A4C0 FOREIGN KEY (report_id) REFERENCES report (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE file_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "file_marker_id_seq" CASCADE');
        $this->addSql('DROP SEQUENCE informant_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE organization_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE organization_informant_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE report_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE report_block_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE subject_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE tag_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('DROP SEQUENCE user_report_id_seq CASCADE');
        $this->addSql('ALTER TABLE expedition DROP CONSTRAINT FK_692907E2E91B903');
        $this->addSql('ALTER TABLE file DROP CONSTRAINT FK_8C9F361023EDC87');
        $this->addSql('ALTER TABLE file DROP CONSTRAINT FK_8C9F3610E98D1B71');
        $this->addSql('ALTER TABLE file_tag DROP CONSTRAINT FK_2CCA391A93CB796C');
        $this->addSql('ALTER TABLE file_tag DROP CONSTRAINT FK_2CCA391ABAD26311');
        $this->addSql('ALTER TABLE "file_marker" DROP CONSTRAINT FK_3AD8064193CB796C');
        $this->addSql('ALTER TABLE "file_marker" DROP CONSTRAINT FK_3AD80641E98D1B71');
        $this->addSql('ALTER TABLE file_marker_tag DROP CONSTRAINT FK_E62A7B36C94BDF9D');
        $this->addSql('ALTER TABLE file_marker_tag DROP CONSTRAINT FK_E62A7B36BAD26311');
        $this->addSql('ALTER TABLE informant DROP CONSTRAINT FK_6D319BE2C3900DE');
        $this->addSql('ALTER TABLE informant DROP CONSTRAINT FK_6D319BE2CCF66B42');
        $this->addSql('ALTER TABLE organization DROP CONSTRAINT FK_C1EE637C2E91B903');
        $this->addSql('ALTER TABLE organization_informant DROP CONSTRAINT FK_550641E432C8A3DE');
        $this->addSql('ALTER TABLE organization_informant DROP CONSTRAINT FK_550641E4F4C9278');
        $this->addSql('ALTER TABLE report DROP CONSTRAINT FK_C42F7784576EF81E');
        $this->addSql('ALTER TABLE report DROP CONSTRAINT FK_C42F77842E91B903');
        $this->addSql('ALTER TABLE report_block DROP CONSTRAINT FK_A907905B4BD2A4C0');
        $this->addSql('ALTER TABLE report_block DROP CONSTRAINT FK_A907905B32C8A3DE');
        $this->addSql('ALTER TABLE report_block_informant DROP CONSTRAINT FK_733A9ADAE98D1B71');
        $this->addSql('ALTER TABLE report_block_informant DROP CONSTRAINT FK_733A9ADAF4C9278');
        $this->addSql('ALTER TABLE report_block_tag DROP CONSTRAINT FK_4FB613A1E98D1B71');
        $this->addSql('ALTER TABLE report_block_tag DROP CONSTRAINT FK_4FB613A1BAD26311');
        $this->addSql('ALTER TABLE subject DROP CONSTRAINT FK_FBCE3E7A576EF81E');
        $this->addSql('ALTER TABLE subject DROP CONSTRAINT FK_FBCE3E7AE98D1B71');
        $this->addSql('ALTER TABLE task DROP CONSTRAINT FK_527EDB254BD2A4C0');
        $this->addSql('ALTER TABLE task DROP CONSTRAINT FK_527EDB25E98D1B71');
        $this->addSql('ALTER TABLE task DROP CONSTRAINT FK_527EDB25F4C9278');
        $this->addSql('ALTER TABLE user_report DROP CONSTRAINT FK_A17D6CB99D1C3019');
        $this->addSql('ALTER TABLE user_report DROP CONSTRAINT FK_A17D6CB94BD2A4C0');
        $this->addSql('DROP TABLE expedition');
        $this->addSql('DROP TABLE file');
        $this->addSql('DROP TABLE file_tag');
        $this->addSql('DROP TABLE "file_marker"');
        $this->addSql('DROP TABLE file_marker_tag');
        $this->addSql('DROP TABLE geo_point');
        $this->addSql('DROP TABLE informant');
        $this->addSql('DROP TABLE organization');
        $this->addSql('DROP TABLE organization_informant');
        $this->addSql('DROP TABLE pack');
        $this->addSql('DROP TABLE report');
        $this->addSql('DROP TABLE report_block');
        $this->addSql('DROP TABLE report_block_informant');
        $this->addSql('DROP TABLE report_block_tag');
        $this->addSql('DROP TABLE subject');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE user_report');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
