<?php

namespace App\Entity;

use App\Repository\QuoteRequestLineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
// Symfony\Component\HttpFoundation\File\File; // Plus nécessaire ici
// Vich\UploaderBundle\Mapping\Annotation as Vich; // Plus nécessaire ici

#[ORM\Entity(repositoryClass: QuoteRequestLineRepository::class)]
// #[Vich\Uploadable] // Supprimez cette annotation
class QuoteRequestLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'quoteRequestLines')]
    #[ORM\JoinColumn(nullable: false)]
    private ?QuoteRequest $quoteRequest = null;

    #[ORM\ManyToOne(inversedBy: 'quoteRequestLines')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Boite $boite = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $question = null;

    #[ORM\Column(nullable: true)]
    private ?int $priceExcludingTax = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $answer = null;

    #[ORM\Column(nullable: true)]
    private ?int $weight = null;

    // Supprimez les champs imageFile, imageName et updatedAt
    // private ?File $imageFile = null;
    // private ?string $imageName = null;
    // private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'quoteRequestLine', targetEntity: Image::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $images;

    public function __construct()
    {
        $this->images = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuoteRequest(): ?QuoteRequest
    {
        return $this->quoteRequest;
    }

    public function setQuoteRequest(?QuoteRequest $quoteRequest): static
    {
        $this->quoteRequest = $quoteRequest;

        return $this;
    }

    public function getBoite(): ?Boite
    {
        return $this->boite;
    }

    public function setBoite(?Boite $boite): static
    {
        $this->boite = $boite;

        return $this;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): static
    {
        $this->question = $question;

        return $this;
    }

    public function getPriceExcludingTax(): ?int
    {
        return $this->priceExcludingTax;
    }

    public function setPriceExcludingTax(?int $priceExcludingTax): static
    {
        $this->priceExcludingTax = $priceExcludingTax;

        return $this;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(?string $answer): static
    {
        $this->answer = $answer;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(?int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setQuoteRequestLine($this);
        }

        return $this;
    }

    public function removeImage(Image $image): static
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getQuoteRequestLine() === $this) {
                $image->setQuoteRequestLine(null);
            }
        }

        return $this;
    }
}
