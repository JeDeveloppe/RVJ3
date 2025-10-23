<?php

namespace App\Entity;

use App\Repository\OpeningHoursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OpeningHoursRepository::class)]
class OpeningHours
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $dayOfWeek = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $openingTime = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $closingTime = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isClosed = null;

    #[ORM\ManyToMany(targetEntity: Store::class, inversedBy: 'openingHours')]
    private Collection $store;

    public function __construct()
    {
        $this->store = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDayOfWeek(): ?string
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(string $dayOfWeek): static
    {
        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    public function getOpeningTime(): ?string
    {
        return $this->openingTime;
    }

    public function setOpeningTime(?string $openingTime): static
    {
        $this->openingTime = $openingTime;

        return $this;
    }

    public function getClosingTime(): ?string
    {
        return $this->closingTime;
    }

    public function setClosingTime(?string $closingTime): static
    {
        $this->closingTime = $closingTime;

        return $this;
    }

    public function isIsClosed(): ?bool
    {
        return $this->isClosed;
    }

    public function setIsClosed(?bool $isClosed): static
    {
        $this->isClosed = $isClosed;

        return $this;
    }

    /**
     * @return Collection<int, Store>
     */
    public function getStore(): Collection
    {
        return $this->store;
    }

    public function addStore(Store $store): static
    {
        if (!$this->store->contains($store)) {
            $this->store->add($store);
        }

        return $this;
    }

    public function removeStore(Store $store): static
    {
        $this->store->removeElement($store);

        return $this;
    }

    public function __toString(): string
    {
        return $this->dayOfWeek. ' De ' . $this->openingTime . ' à ' . $this->closingTime . ' (' . ($this->isClosed ? 'Fermer' : 'Ouvert') . ')';
    }
}
