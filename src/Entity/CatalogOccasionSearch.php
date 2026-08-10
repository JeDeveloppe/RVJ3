<?php

namespace App\Entity;

use App\Repository\CatalogOccasionSearchRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CatalogOccasionSearchRepository::class)]
class CatalogOccasionSearch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phrase = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $ages = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $players = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $durations = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhrase(): ?string
    {
        return $this->phrase;
    }

    public function setPhrase(?string $phrase): static
    {
        $this->phrase = $phrase;

        return $this;
    }

    public function getAges(): ?array
    {
        return $this->ages;
    }

    public function setAges(?array $ages): static
    {
        $this->ages = $ages;

        return $this;
    }

    public function getPlayers(): ?array
    {
        return $this->players;
    }

    public function setPlayers(?array $players): static
    {
        $this->players = $players;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getDurations(): ?array
    {
        return $this->durations;
    }

    public function setDurations(?array $durations): static
    {
        $this->durations = $durations;

        return $this;
    }
}
