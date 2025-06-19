<?php

namespace App\Entity;

use App\Repository\QuoteRequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: QuoteRequestRepository::class)]
class QuoteRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid')]
    private ?Uuid $uuid = null;

    #[ORM\ManyToOne(inversedBy: 'quoteRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?bool $isSendByEmail = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sendByEmailAt = null;

    #[ORM\OneToMany(mappedBy: 'quoteRequest', targetEntity: QuoteRequestLine::class)]
    private Collection $quoteRequestLines;

    #[ORM\ManyToOne(inversedBy: 'quoteRequests')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Address $billingAddress = null;

    #[ORM\ManyToOne(inversedBy: 'quoteRequests')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Address $deliveryAddress = null;

    #[ORM\ManyToOne(inversedBy: 'quoteRequests')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ShippingMethod $shippingMethod = null;

    private ?float $totalPrice = null;

    #[ORM\Column(nullable: true)]
    private ?int $totalWeigth = null;

    #[ORM\Column(nullable: true)]
    private ?int $totalPriceExcludingTax = null;

    #[ORM\OneToOne(inversedBy: 'quoteRequest', cascade: ['persist', 'remove'])]
    private ?Document $document = null;

    #[ORM\ManyToOne(inversedBy: 'quoteRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?QuoteRequestStatus $quoteRequestStatus = null;

    public function __construct()
    {
        $this->quoteRequestLines = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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

    public function isIsSendByEmail(): ?bool
    {
        return $this->isSendByEmail;
    }

    public function setIsSendByEmail(bool $isSendByEmail): static
    {
        $this->isSendByEmail = $isSendByEmail;

        return $this;
    }

    public function getSendByEmailAt(): ?\DateTimeImmutable
    {
        return $this->sendByEmailAt;
    }

    public function setSendByEmailAt(?\DateTimeImmutable $sendByEmailAt): static
    {
        $this->sendByEmailAt = $sendByEmailAt;

        return $this;
    }

    /**
     * @return Collection<int, QuoteRequestLine>
     */
    public function getQuoteRequestLines(): Collection
    {
        return $this->quoteRequestLines;
    }

    public function addQuoteRequestLine(QuoteRequestLine $quoteRequestLine): static
    {
        if (!$this->quoteRequestLines->contains($quoteRequestLine)) {
            $this->quoteRequestLines->add($quoteRequestLine);
            $quoteRequestLine->setQuoteRequest($this);
        }

        return $this;
    }

    public function removeQuoteRequestLine(QuoteRequestLine $quoteRequestLine): static
    {
        if ($this->quoteRequestLines->removeElement($quoteRequestLine)) {
            // set the owning side to null (unless already changed)
            if ($quoteRequestLine->getQuoteRequest() === $this) {
                $quoteRequestLine->setQuoteRequest(null);
            }
        }

        return $this;
    }

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(Address $billingAddress): static
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    public function getDeliveryAddress(): ?Address
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(Address $deliveryAddress): static
    {
        $this->deliveryAddress = $deliveryAddress;

        return $this;
    }

    public function getShippingMethod(): ?ShippingMethod
    {
        return $this->shippingMethod;
    }

    public function setShippingMethod(ShippingMethod $shippingMethod): static
    {
        $this->shippingMethod = $shippingMethod;

        return $this;
    }

    public function getTotalPrice(): ?float
    {
        $lines = $this->getQuoteRequestLines();
        $total = 0;
        foreach ($lines as $line) {
            $total += $line->getPriceExcludingTax();
        }
        return $total;
    }

    public function getTotalWeigth(): ?int
    {
        return $this->totalWeigth;
    }

    public function setTotalWeigth(?int $totalWeigth): static
    {
        $this->totalWeigth = $totalWeigth;

        return $this;
    }

    public function getTotalPriceExcludingTax(): ?int
    {
        return $this->totalPriceExcludingTax;
    }

    public function setTotalPriceExcludingTax(?int $totalPriceExcludingTax): static
    {
        $this->totalPriceExcludingTax = $totalPriceExcludingTax;

        return $this;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): static
    {
        $this->document = $document;

        return $this;
    }

    public function getQuoteRequestStatus(): ?QuoteRequestStatus
    {
        return $this->quoteRequestStatus;
    }

    public function setQuoteRequestStatus(?QuoteRequestStatus $quoteRequestStatus): static
    {
        $this->quoteRequestStatus = $quoteRequestStatus;

        return $this;
    }

}
