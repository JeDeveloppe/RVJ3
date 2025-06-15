<?php

namespace App\Entity;

use App\Repository\ShippingMethodRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShippingMethodRepository::class)]
class ShippingMethod
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'shippingMethod', targetEntity: Delivery::class)]
    private Collection $deliveries;

    #[ORM\Column]
    private ?bool $isActivedInCart = null;

    #[ORM\Column(length: 255)]
    private ?string $price = null;

    #[ORM\Column]
    private ?bool $forOccasionOnly = null;

    #[ORM\OneToMany(mappedBy: 'shippingmethod', targetEntity: Document::class)]
    private Collection $documents;

    #[ORM\OneToMany(mappedBy: 'shippingmethod', targetEntity: CollectionPoint::class)]
    private Collection $collectionPoints;

    #[ORM\OneToMany(mappedBy: 'shippingMethod', targetEntity: QuoteRequest::class)]
    private Collection $quoteRequests;

    public function __construct()
    {
        $this->deliveries = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->collectionPoints = new ArrayCollection();
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

    public function __toString()
    {
        return $this->name;
    }

    /**
     * @return Collection<int, Delivery>
     */
    public function getDeliveries(): Collection
    {
        return $this->deliveries;
    }

    public function addDelivery(Delivery $delivery): static
    {
        if (!$this->deliveries->contains($delivery)) {
            $this->deliveries->add($delivery);
            $delivery->setShippingMethod($this);
        }

        return $this;
    }

    public function removeDelivery(Delivery $delivery): static
    {
        if ($this->deliveries->removeElement($delivery)) {
            // set the owning side to null (unless already changed)
            if ($delivery->getShippingMethod() === $this) {
                $delivery->setShippingMethod(null);
            }
        }

        return $this;
    }

    public function isIsActivedInCart(): ?bool
    {
        return $this->isActivedInCart;
    }

    public function setIsActivedInCart(bool $isActivedInCart): static
    {
        $this->isActivedInCart = $isActivedInCart;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function isForOccasionOnly(): ?bool
    {
        return $this->forOccasionOnly;
    }

    public function setForOccasionOnly(bool $forOccasionOnly): static
    {
        $this->forOccasionOnly = $forOccasionOnly;

        return $this;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setShippingmethod($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getShippingmethod() === $this) {
                $document->setShippingmethod(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CollectionPoint>
     */
    public function getCollectionPoints(): Collection
    {
        return $this->collectionPoints;
    }

    public function addCollectionPoint(CollectionPoint $collectionPoint): static
    {
        if (!$this->collectionPoints->contains($collectionPoint)) {
            $this->collectionPoints->add($collectionPoint);
            $collectionPoint->setShippingmethod($this);
        }

        return $this;
    }

    public function removeCollectionPoint(CollectionPoint $collectionPoint): static
    {
        if ($this->collectionPoints->removeElement($collectionPoint)) {
            // set the owning side to null (unless already changed)
            if ($collectionPoint->getShippingmethod() === $this) {
                $collectionPoint->setShippingmethod(null);
            }
        }

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
            $quoteRequest->setShippingMethod($this);
        }

        return $this;
    }

    public function removeQuoteRequest(QuoteRequest $quoteRequest): static
    {
        if ($this->quoteRequests->removeElement($quoteRequest)) {
            // set the owning side to null (unless already changed)
            if ($quoteRequest->getShippingMethod() === $this) {
                $quoteRequest->setShippingMethod(null);
            }
        }

        return $this;
    }

}