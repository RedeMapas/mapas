<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\Constraints\Type;

final class EventDto
{
    #[Sequentially([new NotBlank()], groups: ['post'])]
    #[Sequentially([new Type('string'), new Length(['min' => 2])], groups: ['patch', 'post'])]
    public mixed $name;

    #[Sequentially([new NotBlank()], groups: ['post'])]
    #[Sequentially([new Type('string'), new Length(['min' => 2])], groups: ['patch', 'post'])]
    public mixed $shortDescription;

    #[Sequentially([new NotBlank()], groups: ['post'])]
    #[Sequentially([new Type('string'), new Length(['min' => 2])], groups: ['patch', 'post'])]
    public mixed $classificacaoEtaria;

    #[Sequentially(
        constraints: [
            new NotBlank(groups: ['post']),
            new Collection(
                fields: [
                    'linguagem' => [
                        new NotBlank(groups: ['post']),
                        new Type(type: 'array', groups: ['post', 'patch']),
                        new All([
                            new Type(type: 'string', groups: ['post', 'patch']),
                        ]),
                        new Count(min: 1, groups: ['post', 'patch']),
                    ],
                    'tag' => [
                        new Optional([
                            new Type(type: 'array', groups: ['post', 'patch']),
                            new All([
                                new Type(type: 'string', groups: ['post', 'patch']),
                            ]),
                        ], groups: ['post', 'patch']),
                    ],
                ],
                groups: ['post', 'patch'],
                allowExtraFields: false
            ),
        ],
        groups: ['post', 'patch']
    )]
    public mixed $terms;
}
