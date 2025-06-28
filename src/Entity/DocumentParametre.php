<?php

namespace App\Entity;

use App\Repository\DocumentParametreRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentParametreRepository::class)]
class DocumentParametre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private ?string $billingTag = null;

    #[ORM\Column(length: 10)]
    private ?string $quoteTag = null;

    #[ORM\Column]
    private ?int $delayBeforeDeleteDevis = null;

    #[ORM\Column]
    private ?bool $isOnline = null;

    #[ORM\ManyToOne(inversedBy: 'documentParametres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $updatedBy = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?int $preparation = null;

    #[ORM\Column(nullable: true)]
    private ?int $delay_to_delete_cart_in_hours = null;

    #[ORM\Column(nullable: true)]
    private ?int $delayBeforeDeleteQuoteRequest = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBillingTag(): ?string
    {
        return $this->billingTag;
    }

    public function setBillingTag(string $billingTag): static
    {
        $this->billingTag = $billingTag;

        return $this;
    }

    public function getQuoteTag(): ?string
    {
        return $this->quoteTag;
    }

    public function setQuoteTag(string $quoteTag): static
    {
        $this->quoteTag = $quoteTag;

        return $this;
    }

    public function getDelayBeforeDeleteDevis(): ?int
    {
        return $this->delayBeforeDeleteDevis;
    }

    public function setDelayBeforeDeleteDevis(int $delayBeforeDeleteDevis): static
    {
        $this->delayBeforeDeleteDevis = $delayBeforeDeleteDevis;

        return $this;
    }

    public function getIsOnline(): ?bool
    {
        return $this->isOnline;
    }

    public function setIsOnline(bool $isOnline): static
    {
        $this->isOnline = $isOnline;

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

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getPreparation(): ?int
    {
        return $this->preparation;
    }

    public function setPreparation(int $preparation): static
    {
        $this->preparation = $preparation;

        return $this;
    }

    public function getDelayToDeleteCartInHours(): ?int
    {
        return $this->delay_to_delete_cart_in_hours;
    }

    public function setDelayToDeleteCartInHours(?int $delay_to_delete_cart_in_hours): static
    {
        $this->delay_to_delete_cart_in_hours = $delay_to_delete_cart_in_hours;

        return $this;
    }

    public function getIndexDefaultLine(): string
    {
        return 'Configuration actuelle';
    }

    public function getDelayBeforeDeleteQuoteRequest(): ?int
    {
        return $this->delayBeforeDeleteQuoteRequest;
    }

    public function setDelayBeforeDeleteQuoteRequest(?int $delayBeforeDeleteQuoteRequest): static
    {
        $this->delayBeforeDeleteQuoteRequest = $delayBeforeDeleteQuoteRequest;

        return $this;
    }
}
