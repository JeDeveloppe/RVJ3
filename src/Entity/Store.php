<?php

namespace App\Entity;

use App\Repository\StoreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: StoreRepository::class)]
#[Vich\Uploadable]
class Store
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 500)]
    private ?string $googleMapUrl = null;

    #[ORM\ManyToOne(inversedBy: 'stores')]
    #[ORM\JoinColumn(nullable: false)]
    private ?City $city = null;

    #[ORM\ManyToMany(targetEntity: OpeningHours::class, mappedBy: 'store', cascade: ['persist'])]
    private Collection $openingHours;

    #[ORM\Column(length: 255)]
    private ?string $street = null;

    #[ORM\OneToMany(mappedBy: 'store', targetEntity: StorePhoto::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $storePhotos;

    public function __construct()
    {
        $this->openingHours = new ArrayCollection();
        $this->storePhotos = new ArrayCollection();
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

    public function getGoogleMapUrl(): ?string
    {
        return $this->googleMapUrl;
    }

    public function setGoogleMapUrl(string $googleMapUrl): static
    {
        $this->googleMapUrl = $googleMapUrl;

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

    /**
     * @return Collection<int, OpeningHours>
     */
    public function getOpeningHours(): Collection
    {
        return $this->openingHours;
    }

    public function addOpeningHour(OpeningHours $openingHour): static
    {
        if (!$this->openingHours->contains($openingHour)) {
            $this->openingHours->add($openingHour);
            $openingHour->addStore($this);
        }

        return $this;
    }

    public function removeOpeningHour(OpeningHours $openingHour): static
    {
        if ($this->openingHours->removeElement($openingHour)) {
            $openingHour->removeStore($this);
        }

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

    /**
     * @return Collection<int, StorePhoto>
     */
    public function getStorePhotos(): Collection
    {
        return $this->storePhotos;
    }

    public function addStorePhoto(StorePhoto $storePhoto): static
    {
        if (!$this->storePhotos->contains($storePhoto)) {
            $this->storePhotos->add($storePhoto);
            $storePhoto->setStore($this);
        }

        return $this;
    }

    public function removeStorePhoto(StorePhoto $storePhoto): static
    {
        if ($this->storePhotos->removeElement($storePhoto)) {
            // set the owning side to null (unless already changed)
            if ($storePhoto->getStore() === $this) {
                $storePhoto->setStore(null);
            }
        }

        return $this;
    }
}
