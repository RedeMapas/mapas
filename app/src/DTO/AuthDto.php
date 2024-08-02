<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\Constraints\Type;

final class AuthDto
{
    #[Sequentially([new NotBlank(), new Type('string')], groups: ['post'])]
    public mixed $email;

    #[Sequentially([new NotBlank(), new Type('string')], groups: ['post'])]
    public mixed $password;
}
