<?php

declare(strict_types=1);

namespace App\DTO;

use App\Validator\Constraints\UniqueInEntity;
use MapasCulturais\Entities\Term;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\Constraints\Type;

final class TermDto
{
    #[Sequentially([new NotBlank()], groups: ['post'])]
    #[Sequentially([new Type('string'), new Length(['min' => 2])], groups: ['patch', 'post'])]
    public mixed $taxonomy;

    #[Sequentially([new NotBlank()], groups: ['post'])]
    #[Sequentially([new Type('string'), new Length(['min' => 2]), new UniqueInEntity(Term::class, 'term')], groups: ['patch', 'post'])]
    public mixed $term;

    #[Sequentially([new NotBlank()], groups: ['post'])]
    #[Sequentially([new Type('string'), new Length(['min' => 2])], groups: ['patch', 'post'])]
    public mixed $description;
}
