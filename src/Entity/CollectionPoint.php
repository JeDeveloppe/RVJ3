<?php

namespace App\Entity;

use App\Repository\CollectionPointRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CollectionPointRepository::class)]
class CollectionPoint
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $organization = null;

    #[ORM\Column(length: 255)]
    private ?string $street = null;

    #[ORM\ManyToOne(inversedBy: 'collectionPoints')]
    #[ORM\JoinColumn(nullable: false)]
    private ?City $city = null;

    #[ORM\Column]
    private ?bool $isActivedInCart = null;

    #[ORM\ManyToOne(inversedBy: 'collectionPoints')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ShippingMethod $shippingmethod = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isOriginForWebSiteCmds = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $timeToSend = null;

    #[ORM\ManyToOne(inversedBy: 'collectionPoints')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DocumentStatus $documentStatus = null;

    #[ORM\OneToMany(mappedBy: 'collectionPoint', targetEntity: QuoteRequest::class)]
    private Collection $quoteRequests;

    public function __construct()
    {
        $this->quoteRequests = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function setOrganization(?string $organization): static
    {
        $this->organization = $organization;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): static
    {
        $this->city = $city;

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

    public function __toString()
    {
        if(!is_null($this->organization)){
            $orga = $this->organization.'<br/>';
        }else{
            $orga = '';
        }

        return $orga.$this->firstname.' '.$this->lastname.'<br/>'.$this->street.'<br/>'.$this->city;
    }

    public function getShippingmethod(): ?ShippingMethod
    {
        return $this->shippingmethod;
    }

    public function setShippingmethod(?ShippingMethod $shippingmethod): static
    {
        $this->shippingmethod = $shippingmethod;

        return $this;
    }

    public function isIsOriginForWebSiteCmds(): ?bool
    {
        return $this->isOriginForWebSiteCmds;
    }

    public function setIsOriginForWebSiteCmds(?bool $isOriginForWebSiteCmds): static
    {
        $this->isOriginForWebSiteCmds = $isOriginForWebSiteCmds;

        return $this;
    }

    public function getTimeToSend(): ?string
    {
        return $this->timeToSend;
    }

    public function setTimeToSend(?string $timeToSend): static
    {
        $this->timeToSend = $timeToSend;

        return $this;
    }

    public function getDocumentStatus(): ?DocumentStatus
    {
        return $this->documentStatus;
    }

    public function setDocumentStatus(?DocumentStatus $documentStatus): static
    {
        $this->documentStatus = $documentStatus;

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
            $quoteRequest->setCollectionPoint($this);
        }

        return $this;
    }

    public function removeQuoteRequest(QuoteRequest $quoteRequest): static
    {
        if ($this->quoteRequests->removeElement($quoteRequest)) {
            // set the owning side to null (unless already changed)
            if ($quoteRequest->getCollectionPoint() === $this) {
                $quoteRequest->setCollectionPoint(null);
            }
        }

        return $this;
    }
}
