<?php

namespace App\Entity;

use App\Repository\JobPostRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobPostRepository::class)]
class JobPost
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    private ?string $contractType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column]
    private ?bool $isOnLine = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startPublished = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $endPublished = null;

    #[ORM\ManyToOne(inversedBy: 'jobPosts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'jobPostsUpdated')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $updatedBy = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = strtolower($slug);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getContractType(): ?string
    {
        return $this->contractType;
    }

    public function setContractType(string $contractType): static
    {
        $this->contractType = $contractType;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function isIsOnLine(): ?bool
    {
        return $this->isOnLine;
    }

    public function setIsOnLine(bool $isOnLine): static
    {
        $this->isOnLine = $isOnLine;

        return $this;
    }

    public function getStartPublished(): ?\DateTimeImmutable
    {
        return $this->startPublished;
    }

    public function setStartPublished(\DateTimeImmutable $startPublished): static
    {
        $this->startPublished = $startPublished;

        return $this;
    }

    public function getEndPublished(): ?\DateTimeImmutable
    {
        return $this->endPublished;
    }

    public function setEndPublished(\DateTimeImmutable $endPublished): static
    {
        $this->endPublished = $endPublished;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

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

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): static
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function isExpired(): bool
    {
        return $this->endPublished !== null && $this->endPublished < new \DateTimeImmutable('now');
    }

    //?Doit rester une vraie methode (pas un simple TextField::formatValue() dans le
    //?CrudController) : sans accesseur reel, EasyAdmin ne peut pas lire la "propriete"
    //?status et affiche "Inaccessible" a la place, meme avec setVirtual(true).
    public function getStatus(): string
    {
        if (!$this->isOnLine) {
            return '⚪ Hors ligne';
        }
        if ($this->isExpired()) {
            return '🔴 Expirée';
        }
        if ($this->startPublished > new \DateTimeImmutable('now')) {
            return '🔵 Programmée';
        }

        return '🟢 En ligne';
    }
}
