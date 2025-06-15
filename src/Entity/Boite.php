<?php

namespace App\Entity;

use App\Entity\Panier;
use App\Entity\Occasion;
use App\Entity\DocumentLine;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\BoiteRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BoiteRepository::class)]
#[Vich\Uploadable]
class Boite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // NOTE: This is not a mapped field of entity metadata, just a simple property.
    #[Vich\UploadableField(mapping: 'boites', fileNameProperty: 'image')]
    private ?File $imageFile = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $initeditor = null;

    #[ORM\ManyToOne(inversedBy: 'boites')]
    private ?Editor $editor = null;

    #[ORM\Column(nullable: true)]
    private ?int $year = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column]
    private ?bool $isDeliverable = null;

    #[ORM\Column]
    private ?bool $isOccasion = null;

    #[ORM\Column(nullable: true)]
    private ?int $weigth = null;

    #[ORM\Column]
    private ?int $age = null;

    #[ORM\Column(nullable: true)]
    private ?int $htPrice = null;

    #[ORM\ManyToOne(inversedBy: 'boites')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column]
    private ?bool $isDeee = null;

    #[ORM\Column]
    private ?bool $isOnline = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contentMessage = null;

    #[ORM\OneToMany(mappedBy: 'boite', targetEntity: Occasion::class)]
    private Collection $occasions;

    #[ORM\OneToMany(mappedBy: 'boite', targetEntity: DocumentLine::class)]
    private Collection $documentLines;

    #[ORM\OneToMany(mappedBy: 'boite', targetEntity: Panier::class)]
    private Collection $paniers;

    #[ORM\ManyToOne(inversedBy: 'boites')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\LessThanOrEqual(
        propertyPath:"playersMax.name",
        message: "Cette valeur ne peut être supérieure au nombre de joueurs max !"
        )]
    private ?NumbersOfPlayers $playersMin = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $rvj2id = null;

    #[ORM\ManyToMany(targetEntity: Item::class, mappedBy: 'BoiteOrigine')]
    private Collection $itemsOrigine;

    #[ORM\ManyToMany(targetEntity: Item::class, inversedBy: 'BoiteSecondaire')]
    private Collection $itemsSecondaire;

    #[ORM\Column(length: 400, nullable: true)]
    private ?string $linktopresentationvideo = null;

    #[ORM\ManyToOne(inversedBy: 'boitesUpdated')]
    private ?User $updatedBy = null;

    #[ORM\ManyToOne(inversedBy: 'boitesMax')]
    private ?NumbersOfPlayers $playersMax = null;

    #[ORM\ManyToOne(inversedBy: 'boites')]
    private ?DurationOfGame $durationGame = null;

    private int $numberOfItemWithStockNotNull;

    #[ORM\OneToMany(mappedBy: 'boite', targetEntity: QuoteRequestLine::class)]
    private Collection $quoteRequestLines;

    public function __construct()
    {
        $this->occasions = new ArrayCollection();
        $this->documentLines = new ArrayCollection();
        $this->paniers = new ArrayCollection();
        $this->itemsOrigine = new ArrayCollection();
        $this->itemsSecondaire = new ArrayCollection();
        $this->quoteRequestLines = new ArrayCollection();
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

    public function getIniteditor(): ?string
    {
        return $this->initeditor;
    }

    public function setIniteditor(?string $initeditor): static
    {
        $this->initeditor = $initeditor;

        return $this;
    }

    public function getEditor(): ?Editor
    {
        return $this->editor;
    }

    public function setEditor(?Editor $editor): static
    {
        $this->editor = $editor;

        return $this;
    }

    public function getYear(): ?int
    {
        if($this->year > 0){

            return $this->year;

        }else{
            
            return 0;
        }
    }

    public function setYear(?int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function isIsDeliverable(): ?bool
    {
        return $this->isDeliverable;
    }

    public function setIsDeliverable(bool $isDeliverable): static
    {
        $this->isDeliverable = $isDeliverable;

        return $this;
    }

    public function isIsOccasion(): ?bool
    {
        return $this->isOccasion;
    }

    public function setIsOccasion(bool $isOccasion): static
    {
        $this->isOccasion = $isOccasion;

        return $this;
    }

    public function getWeigth(): ?int
    {
        return $this->weigth;
    }

    public function setWeigth(?int $weigth): static
    {
        $this->weigth = $weigth;

        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(int $age): static
    {
        $this->age = $age;

        return $this;
    }

    public function getHtPrice(): ?int
    {
        return $this->htPrice;
    }

    public function setHtPrice(?int $htPrice): static
    {
        $this->htPrice = $htPrice;

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

    public function isIsDeee(): ?bool
    {
        return $this->isDeee;
    }

    public function setIsDeee(bool $isDeee): static
    {
        $this->isDeee = $isDeee;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

        /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile
     */
    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
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

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }
    
    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getContentMessage(): ?string
    {
        return $this->contentMessage;
    }

    public function setContentMessage(?string $contentMessage): static
    {
        $this->contentMessage = $contentMessage;

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
            $occasion->setBoite($this);
        }

        return $this;
    }

    public function removeOccasion(Occasion $occasion): static
    {
        if ($this->occasions->removeElement($occasion)) {
            // set the owning side to null (unless already changed)
            if ($occasion->getBoite() === $this) {
                $occasion->setBoite(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        if($this->year == 0){

            $yearInDatabase = 'ANNÉE INCONNUE';

        }else{

            $yearInDatabase = $this->year;
        }

        return '#'.$this->id.' - '.$this->name.' - '.$this->editor.' - '.$yearInDatabase;
    }

    /**
     * @return Collection<int, DocumentLine>
     */
    public function getDocumentLines(): Collection
    {
        return $this->documentLines;
    }

    public function addDocumentLine(DocumentLine $documentLine): static
    {
        if (!$this->documentLines->contains($documentLine)) {
            $this->documentLines->add($documentLine);
            $documentLine->setBoite($this);
        }

        return $this;
    }

    public function removeDocumentLine(DocumentLine $documentLine): static
    {
        if ($this->documentLines->removeElement($documentLine)) {
            // set the owning side to null (unless already changed)
            if ($documentLine->getBoite() === $this) {
                $documentLine->setBoite(null);
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
            $panier->setBoite($this);
        }

        return $this;
    }

    public function removePanier(Panier $panier): static
    {
        if ($this->paniers->removeElement($panier)) {
            // set the owning side to null (unless already changed)
            if ($panier->getBoite() === $this) {
                $panier->setBoite(null);
            }
        }

        return $this;
    }

    public function getPlayersMin(): ?NumbersOfPlayers
    {
        return $this->playersMin;
    }

    public function setPlayersMin(?NumbersOfPlayers $playersMin): static
    {
        
        $this->playersMin = $playersMin;

        return $this;
    }

    public function getRvj2id(): ?string
    {
        return $this->rvj2id ?? 'RVJ3';
    }

    public function setRvj2id(?string $rvj2id): static
    {
        $this->rvj2id = $rvj2id;

        return $this;
    }

    /**
     * @return Collection<int, Item>
     */
    public function getItemsOrigine(): Collection
    {
        return $this->itemsOrigine;
    }

    public function addItemsOrigine(Item $itemsOrigine): static
    {
        if (!$this->itemsOrigine->contains($itemsOrigine)) {
            $this->itemsOrigine->add($itemsOrigine);
            $itemsOrigine->addBoiteOrigine($this);
        }

        return $this;
    }

    public function removeItemsOrigine(Item $itemsOrigine): static
    {
        if ($this->itemsOrigine->removeElement($itemsOrigine)) {
            $itemsOrigine->removeBoiteOrigine($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Item>
     */
    public function getItemsSecondaire(): Collection
    {
        return $this->itemsSecondaire;
    }

    public function addItemsSecondaire(Item $itemsSecondaire): static
    {
        if (!$this->itemsSecondaire->contains($itemsSecondaire)) {
            $this->itemsSecondaire->add($itemsSecondaire);
            $itemsSecondaire->addBoiteSecondaire($this);
        }

        return $this;
    }

    public function removeItemsSecondaire(Item $itemsSecondaire): static
    {
        if ($this->itemsSecondaire->removeElement($itemsSecondaire)) {
            $itemsSecondaire->removeBoiteSecondaire($this);
        }

        return $this;
    }

    public function getLinktopresentationvideo(): ?string
    {
        return $this->linktopresentationvideo;
    }

    public function setLinktopresentationvideo(?string $linktopresentationvideo): static
    {
        $this->linktopresentationvideo = $linktopresentationvideo;

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

    public function getPlayersMax(): ?NumbersOfPlayers
    {
        return $this->playersMax;
    }

    public function setPlayersMax(?NumbersOfPlayers $playersMax): static
    {
        $this->playersMax = $playersMax;

        return $this;
    }

    public function getDurationGame(): ?DurationOfGame
    {
        return $this->durationGame;
    }

    public function setDurationGame(?DurationOfGame $durationGame): static
    {
        $this->durationGame = $durationGame;

        return $this;
    }

    //get min and max players
    public function getMinAndMaxPlayers(): ?string
    {
        return 'De '.$this->getPlayersMin().' à '.$this->getPlayersMax();
    }

    public function getNumberOfItemWithStockNotNull(): ?int {

        return $this->numberOfItemWithStockNotNull;

    }

    public function setNumberOfItemWithStockNotNull(?int $numberOfItemWithStockNotNull): static
    {
        $this->numberOfItemWithStockNotNull = $numberOfItemWithStockNotNull;

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
            $quoteRequestLine->setBoite($this);
        }

        return $this;
    }

    public function removeQuoteRequestLine(QuoteRequestLine $quoteRequestLine): static
    {
        if ($this->quoteRequestLines->removeElement($quoteRequestLine)) {
            // set the owning side to null (unless already changed)
            if ($quoteRequestLine->getBoite() === $this) {
                $quoteRequestLine->setBoite(null);
            }
        }

        return $this;
    }

    public function getNameAndReference(): ?string
    {
        return $this->name.' (Référence: '.$this->id.')';
    }
}
