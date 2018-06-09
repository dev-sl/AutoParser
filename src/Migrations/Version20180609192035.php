<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180609192035 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TEMPORARY TABLE __temp__car AS SELECT id, car_id, site_id FROM car');
        $this->addSql('DROP TABLE car');
        $this->addSql('CREATE TABLE car (id INTEGER NOT NULL, car_id INTEGER NOT NULL, site_id INTEGER NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO car (id, car_id, site_id) SELECT id, car_id, site_id FROM __temp__car');
        $this->addSql('DROP TABLE __temp__car');
        $this->addSql('CREATE UNIQUE INDEX car_unique ON car (car_id, site_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX car_unique');
        $this->addSql('CREATE TEMPORARY TABLE __temp__car AS SELECT id, car_id, site_id FROM car');
        $this->addSql('DROP TABLE car');
        $this->addSql('CREATE TABLE car (id INTEGER NOT NULL, car_id INTEGER NOT NULL, site_id INTEGER NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO car (id, car_id, site_id) SELECT id, car_id, site_id FROM __temp__car');
        $this->addSql('DROP TABLE __temp__car');
    }
}
