<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Un compte avec cet email existe déjà !!')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nickname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $lastvisite = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $membership = null;

    #[ORM\Column(nullable: true)]
    private ?int $rvj2id = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Country $country = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Address::class)]
    private Collection $addresses;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Boite::class)]
    private Collection $boites;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: OffSiteOccasionSale::class)]
    private Collection $offSiteOccasionSales;

    #[ORM\OneToMany(mappedBy: 'updatedBy', targetEntity: LegalInformation::class)]
    private Collection $legalInformation;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Document::class)]
    private Collection $documents;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Occasion::class)]
    private Collection $occasions;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Panier::class)]
    private Collection $paniers;

    #[ORM\OneToMany(mappedBy: 'updatedBy', targetEntity: DocumentParametre::class)]
    private Collection $documentParametres;

    #[ORM\ManyToOne(inversedBy: 'users')]
    private ?Level $level = null;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: VoucherDiscount::class)]
    private Collection $voucherDiscounts;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Item::class)]
    private Collection $items;

    #[ORM\OneToMany(mappedBy: 'updatedBy', targetEntity: Item::class)]
    private Collection $itemsUpdated;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Media::class)]
    private Collection $media;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Reserve::class)]
    private Collection $reserves;

    #[ORM\OneToMany(mappedBy: 'updatedBy', targetEntity: Boite::class)]
    private Collection $boitesUpdated;

    #[ORM\Column(length: 10)]
    private ?string $accountnumber = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isMemberStructure = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: QuoteRequest::class)]
    private Collection $quoteRequests;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
        $this->boites = new ArrayCollection();
        $this->offSiteOccasionSales = new ArrayCollection();
        $this->legalInformation = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->occasions = new ArrayCollection();
        $this->paniers = new ArrayCollection();
        $this->documentParametres = new ArrayCollection();
        $this->voucherDiscounts = new ArrayCollection();
        $this->items = new ArrayCollection();
        $this->itemsUpdated = new ArrayCollection();
        $this->media = new ArrayCollection();
        $this->reserves = new ArrayCollection();
        $this->boitesUpdated = new ArrayCollection();
        $this->catalogOccasionSearches = new ArrayCollection();
        $this->quoteRequests = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        $this->isMemberStructure = in_array('ROLE_STRUCTURE_ADHERENTE', $this->roles, true);
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): static
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

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

    public function getLastvisite(): ?\DateTimeImmutable
    {
        return $this->lastvisite;
    }

    public function setLastvisite(\DateTimeImmutable $lastvisite): static
    {
        $this->lastvisite = $lastvisite;

        return $this;
    }

    public function getMembership(): ?\DateTimeImmutable
    {
        return $this->membership;
    }

    public function setMembership(?\DateTimeImmutable $membership): static
    {
        $this->membership = $membership;

        return $this;
    }

    public function getRvj2id(): ?int
    {
        return $this->rvj2id;
    }

    public function setRvj2id(?int $rvj2id): static
    {
        $this->rvj2id = $rvj2id;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): static
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return Collection<int, Address>
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function addAddress(Address $address): static
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses->add($address);
            $address->setUser($this);
        }

        return $this;
    }

    public function removeAddress(Address $address): static
    {
        if ($this->addresses->removeElement($address)) {
            // set the owning side to null (unless already changed)
            if ($address->getUser() === $this) {
                $address->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Boite>
     */
    public function getBoites(): Collection
    {
        return $this->boites;
    }

    public function addBoite(Boite $boite): static
    {
        if (!$this->boites->contains($boite)) {
            $this->boites->add($boite);
            $boite->setCreatedBy($this);
        }

        return $this;
    }

    public function removeBoite(Boite $boite): static
    {
        if ($this->boites->removeElement($boite)) {
            // set the owning side to null (unless already changed)
            if ($boite->getCreatedBy() === $this) {
                $boite->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, OffSiteOccasionSale>
     */
    public function getOffSiteOccasionSales(): Collection
    {
        return $this->offSiteOccasionSales;
    }

    public function addOffSiteOccasionSale(OffSiteOccasionSale $offSiteOccasionSale): static
    {
        if (!$this->offSiteOccasionSales->contains($offSiteOccasionSale)) {
            $this->offSiteOccasionSales->add($offSiteOccasionSale);
            $offSiteOccasionSale->setCreatedBy($this);
        }

        return $this;
    }

    public function removeOffSiteOccasionSale(OffSiteOccasionSale $offSiteOccasionSale): static
    {
        if ($this->offSiteOccasionSales->removeElement($offSiteOccasionSale)) {
            // set the owning side to null (unless already changed)
            if ($offSiteOccasionSale->getCreatedBy() === $this) {
                $offSiteOccasionSale->setCreatedBy(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        if(!is_null($this->nickname )){
            return '(ADMIN) '.$this->nickname;
        }else{
            return $this->accountnumber;
        }
    }

    /**
     * @return Collection<int, LegalInformation>
     */
    public function getLegalInformation(): Collection
    {
        return $this->legalInformation;
    }

    public function addLegalInformation(LegalInformation $legalInformation): static
    {
        if (!$this->legalInformation->contains($legalInformation)) {
            $this->legalInformation->add($legalInformation);
            $legalInformation->setUpdatedBy($this);
        }

        return $this;
    }

    public function removeLegalInformation(LegalInformation $legalInformation): static
    {
        if ($this->legalInformation->removeElement($legalInformation)) {
            // set the owning side to null (unless already changed)
            if ($legalInformation->getUpdatedBy() === $this) {
                $legalInformation->setUpdatedBy(null);
            }
        }

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
            $document->setUser($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getUser() === $this) {
                $document->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Occasion>
     */
    public function getOccasions(): Collection
    {
        return $this->occasions;
    }

    public function addOccasion(Occasion $occasion): static
    {
        if (!$this->occasions->contains($occasion)) {
            $this->occasions->add($occasion);
            $occasion->setCreatedBy($this);
        }

        return $this;
    }

    public function removeOccasion(Occasion $occasion): static
    {
        if ($this->occasions->removeElement($occasion)) {
            // set the owning side to null (unless already changed)
            if ($occasion->getCreatedBy() === $this) {
                $occasion->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Panier>
     */
    public function getPaniers(): Collection
    {
        return $this->paniers;
    }

    public function addPanier(Panier $panier): static
    {
        if (!$this->paniers->contains($panier)) {
            $this->paniers->add($panier);
            $panier->setUser($this);
        }

        return $this;
    }

    public function removePanier(Panier $panier): static
    {
        if ($this->paniers->removeElement($panier)) {
            // set the owning side to null (unless already changed)
            if ($panier->getUser() === $this) {
                $panier->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DocumentParametre>
     */
    public function getDocumentParametres(): Collection
    {
        return $this->documentParametres;
    }

    public function addDocumentParametre(DocumentParametre $documentParametre): static
    {
        if (!$this->documentParametres->contains($documentParametre)) {
            $this->documentParametres->add($documentParametre);
            $documentParametre->setUpdatedBy($this);
        }

        return $this;
    }

    public function removeDocumentParametre(DocumentParametre $documentParametre): static
    {
        if ($this->documentParametres->removeElement($documentParametre)) {
            // set the owning side to null (unless already changed)
            if ($documentParametre->getUpdatedBy() === $this) {
                $documentParametre->setUpdatedBy(null);
            }
        }

        return $this;
    }

    public function getLevel(): ?Level
    {
        return $this->level;
    }

    public function setLevel(?Level $level): static
    {
        $this->level = $level;

        return $this;
    }

    /**
     * @return Collection<int, VoucherDiscount>
     */
    public function getVoucherDiscounts(): Collection
    {
        return $this->voucherDiscounts;
    }

    public function addVoucherDiscount(VoucherDiscount $voucherDiscount): static
    {
        if (!$this->voucherDiscounts->contains($voucherDiscount)) {
            $this->voucherDiscounts->add($voucherDiscount);
            $voucherDiscount->setCreatedBy($this);
        }

        return $this;
    }

    public function removeVoucherDiscount(VoucherDiscount $voucherDiscount): static
    {
        if ($this->voucherDiscounts->removeElement($voucherDiscount)) {
            // set the owning side to null (unless already changed)
            if ($voucherDiscount->getCreatedBy() === $this) {
                $voucherDiscount->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Item>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(Item $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setCreatedBy($this);
        }

        return $this;
    }

    public function removeItem(Item $item): static
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getCreatedBy() === $this) {
                $item->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Item>
     */
    public function getItemsUpdated(): Collection
    {
        return $this->itemsUpdated;
    }

    public function addItemsUpdated(Item $itemsUpdated): static
    {
        if (!$this->itemsUpdated->contains($itemsUpdated)) {
            $this->itemsUpdated->add($itemsUpdated);
            $itemsUpdated->setUpdatedBy($this);
        }

        return $this;
    }

    public function removeItemsUpdated(Item $itemsUpdated): static
    {
        if ($this->itemsUpdated->removeElement($itemsUpdated)) {
            // set the owning side to null (unless already changed)
            if ($itemsUpdated->getUpdatedBy() === $this) {
                $itemsUpdated->setUpdatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Media>
     */
    public function getMedia(): Collection
    {
        return $this->media;
    }

    public function addMedium(Media $medium): static
    {
        if (!$this->media->contains($medium)) {
            $this->media->add($medium);
            $medium->setCreatedBy($this);
        }

        return $this;
    }

    public function removeMedium(Media $medium): static
    {
        if ($this->media->removeElement($medium)) {
            // set the owning side to null (unless already changed)
            if ($medium->getCreatedBy() === $this) {
                $medium->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Reserve>
     */
    public function getReserves(): Collection
    {
        return $this->reserves;
    }

    public function addReserf(Reserve $reserf): static
    {
        if (!$this->reserves->contains($reserf)) {
            $this->reserves->add($reserf);
            $reserf->setCreatedBy($this);
        }

        return $this;
    }

    public function removeReserf(Reserve $reserf): static
    {
        if ($this->reserves->removeElement($reserf)) {
            // set the owning side to null (unless already changed)
            if ($reserf->getCreatedBy() === $this) {
                $reserf->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Boite>
     */
    public function getBoitesUpdated(): Collection
    {
        return $this->boitesUpdated;
    }

    public function addBoitesUpdated(Boite $boitesUpdated): static
    {
        if (!$this->boitesUpdated->contains($boitesUpdated)) {
            $this->boitesUpdated->add($boitesUpdated);
            $boitesUpdated->setUpdatedBy($this);
        }

        return $this;
    }

    public function removeBoitesUpdated(Boite $boitesUpdated): static
    {
        if ($this->boitesUpdated->removeElement($boitesUpdated)) {
            // set the owning side to null (unless already changed)
            if ($boitesUpdated->getUpdatedBy() === $this) {
                $boitesUpdated->setUpdatedBy(null);
            }
        }

        return $this;
    }

    public function getAccountnumber(): ?string
    {
        return $this->accountnumber;
    }

    public function setAccountnumber(string $accountnumber): static
    {
        $this->accountnumber = $accountnumber;

        return $this;
    }

    public function getIsMemberStructure(): ?bool
    {
        return $this->isMemberStructure;
    }

    public function setIsMemberStructure(?bool $isMemberStructure): static
    {
        $this->isMemberStructure = $isMemberStructure;

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
            $quoteRequest->setUser($this);
        }

        return $this;
    }

    public function removeQuoteRequest(QuoteRequest $quoteRequest): static
    {
        if ($this->quoteRequests->removeElement($quoteRequest)) {
            // set the owning side to null (unless already changed)
            if ($quoteRequest->getUser() === $this) {
                $quoteRequest->setUser(null);
            }
        }

        return $this;
    }

    public function returnEmailAndPhoneForQuoteRequestInAdminPanel(): string
    {
        return $this->getEmail() . ' - ' . $this->getPhone();
    }
}
