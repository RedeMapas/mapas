<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use MapasCulturais\Entities\TermRelation;

#[ORM\Entity]
class Term
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'term_id_seq', allocationSize: 1, initialValue: 1)]
    private int $id;

    #[ORM\Column(name: 'taxonomy', type: 'string', length: 64)]
    private string $taxonomy;

    #[ORM\Column(name: 'term', type: 'string', length: 255)]
    private string $term;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\OneToMany(targetEntity: TermRelation::class, mappedBy: 'term', cascade: ['remove'], orphanRemoval: true)]
    protected $relations;

    public function getId(): int
    {
        return $this->id;
    }

    public function getTaxonomy(): string
    {
        return $this->taxonomy;
    }

    public function setTaxonomy(string $taxonomy): void
    {
        $this->taxonomy = $taxonomy;
    }

    public function getTerm(): string
    {
        return $this->term;
    }

    public function setTerm(string $term): void
    {
        $this->term = $term;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
