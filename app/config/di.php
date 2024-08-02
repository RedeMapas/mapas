<?php

declare(strict_types=1);

use App\Repository\AgentRepository;
use App\Repository\EventRepository;
use App\Repository\Interface\AgentRepositoryInterface;
use App\Repository\Interface\EventRepositoryInterface;
use App\Repository\Interface\OpportunityRepositoryInterface;
use App\Repository\Interface\ProjectRepositoryInterface;
use App\Repository\Interface\SealRepositoryInterface;
use App\Repository\Interface\SpaceRepositoryInterface;
use App\Repository\Interface\TermRepositoryInterface;
use App\Repository\Interface\UserRepositoryInterface;
use App\Repository\OpportunityRepository;
use App\Repository\ProjectRepository;
use App\Repository\SealRepository;
use App\Repository\SpaceRepository;
use App\Repository\TermRepository;
use App\Repository\UserRepository;
use App\Service\AgentService;
use App\Service\EventService;
use App\Service\Interface\AgentServiceInterface;
use App\Service\Interface\EventServiceInterface;
use App\Service\Interface\OpportunityServiceInterface;
use App\Service\Interface\ProjectServiceInterface;
use App\Service\Interface\SealServiceInterface;
use App\Service\Interface\SpaceServiceInterface;
use App\Service\Interface\TermServiceInterface;
use App\Service\Interface\UserServiceInterface;
use App\Service\OpportunityService;
use App\Service\ProjectService;
use App\Service\SealService;
use App\Service\SpaceService;
use App\Service\TermService;
use App\Service\UserService;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

return [
    SerializerInterface::class => fn () => new Serializer([new ObjectNormalizer()]),
    ValidatorInterface::class => fn () => Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
    ...repositories(),
    ...services(),
];

function repositories(): array
{
    return [
        AgentRepositoryInterface::class => fn () => new AgentRepository(),
        EventRepositoryInterface::class => fn () => new EventRepository(),
        OpportunityRepositoryInterface::class => fn () => new OpportunityRepository(),
        ProjectRepositoryInterface::class => fn () => new ProjectRepository(),
        SealRepositoryInterface::class => fn () => new SealRepository(),
        SpaceRepositoryInterface::class => fn () => new SpaceRepository(),
        TermRepositoryInterface::class => fn () => new TermRepository(),
        UserRepositoryInterface::class => fn () => new UserRepository(),
    ];
}

function services(): array
{
    return [
        AgentServiceInterface::class => fn () => new AgentService(new AgentRepository(), new UserRepository(), new Serializer([new ObjectNormalizer()])),
        EventServiceInterface::class => fn () => new EventService(new EventRepository(), new Serializer([new ObjectNormalizer()])),
        OpportunityServiceInterface::class => fn () => new OpportunityService(new OpportunityRepository(), new Serializer([new ObjectNormalizer()])),
        ProjectServiceInterface::class => fn () => new ProjectService(new ProjectRepository(), new Serializer([new ObjectNormalizer()])),
        SealServiceInterface::class => fn () => new SealService(new AgentRepository(), new SealRepository(), new Serializer([new ObjectNormalizer()])),
        SpaceServiceInterface::class => fn () => new SpaceService(new Serializer([new ObjectNormalizer()]), new SpaceRepository()),
        TermServiceInterface::class => fn () => new TermService(new Serializer([new ObjectNormalizer()]), new TermRepository()),
        UserServiceInterface::class => fn () => new UserService(new UserRepository()),
    ];
}
