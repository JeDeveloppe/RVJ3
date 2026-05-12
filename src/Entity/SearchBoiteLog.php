<?php

namespace App\Entity;

use App\Repository\SearchBoiteLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SearchBoiteLogRepository::class)]
class SearchBoiteLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $query = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?int $resultsCount = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function setQuery(string $query): static
    {
        $this->query = $query;

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

    public function getResultsCount(): ?int
    {
        return $this->resultsCount;
    }

    public function setResultsCount(int $resultsCount): static
    {
        $this->resultsCount = $resultsCount;

        return $this;
    }
}
