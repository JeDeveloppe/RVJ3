<?php

namespace App\Entity;

use App\Repository\QuoteRequestStatusRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuoteRequestStatusRepository::class)]
class QuoteRequestStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 25)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $level = null;

    #[ORM\OneToMany(mappedBy: 'quoteRequestStatus', targetEntity: QuoteRequest::class)]
    private Collection $quoteRequests;

    public function __construct()
    {
        $this->quoteRequests = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;

        return $this;
    }

    /**
     * @return Collection<int, QuoteRequest>
     */
    public function getQuoteRequests(): Collection
    {
        return $this->quoteRequests;
    }

    public function addQuoteRequest(QuoteRequest $quoteRequest): static
    {
        if (!$this->quoteRequests->contains($quoteRequest)) {
            $this->quoteRequests->add($quoteRequest);
            $quoteRequest->setQuoteRequestStatus($this);
        }

        return $this;
    }

    public function removeQuoteRequest(QuoteRequest $quoteRequest): static
    {
        if ($this->quoteRequests->removeElement($quoteRequest)) {
            // set the owning side to null (unless already changed)
            if ($quoteRequest->getQuoteRequestStatus() === $this) {
                $quoteRequest->setQuoteRequestStatus(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
