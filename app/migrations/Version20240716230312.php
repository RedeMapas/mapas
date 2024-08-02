<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240716230312 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Modify column auth_token to table user';
    }

    public function preUp(Schema $schema): void
    {
        $this->addSql('ALTER TABLE usr DROP COLUMN auth_token');
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE usr ADD COLUMN auth_token TEXT NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE usr DROP COLUMN auth_token');
        $this->addSql('ALTER TABLE usr ADD auth_token VARCHAR(255) NULL;');
    }
}
