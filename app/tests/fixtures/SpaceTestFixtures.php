<?php

declare(strict_types=1);

namespace App\Tests\fixtures;

final class SpaceTestFixtures extends AbstractTestFixtures implements TestFixtures
{
    public static function partial(): self
    {
        return new self([
            'name' => 'Secretaria da Cultura do Estado do Ceará - SECULT',
            'type' => 41,
            'shortDescription' => 'A Secretaria da Cultura do Estado do Ceará (Secult) foi criada pela Lei nº 8.541, de 9 de agosto de 1966, durante o governo de Virgílio Távora. A Secult tem como missão executar, superintender e coordenar as atividades de proteção do patrimônio cultural do Ceará, difusão da cultura e aprimoramento cultural do povo cearense.',
            'terms' => [
                'tag' => [],
                'area' => ['Gestão Cultural'],
            ],
        ]);
    }

    public static function complete(): array
    {
        return [];
    }
}
