<?php

namespace App\Entity;

use App\Repository\CityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CityRepository::class)]
class City
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $latitude = null;

    #[ORM\Column(length: 255)]
    private ?string $longitude = null;

    #[ORM\Column]
    private ?string $postalcode = null;

    #[ORM\ManyToOne(inversedBy: 'cities')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Department $department = null;

    #[ORM\ManyToOne(inversedBy: 'cities')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Country $country = null;

    #[ORM\Column(nullable:true)]
    private ?int $rvj2id = null;

    #[ORM\OneToMany(mappedBy: 'city', targetEntity: Partner::class)]
    private Collection $partners;

    #[ORM\OneToMany(mappedBy: 'city', targetEntity: Address::class)]
    private Collection $addresses;

    #[ORM\OneToMany(mappedBy: 'city', targetEntity: CollectionPoint::class)]
    private Collection $collectionPoints;

    #[ORM\OneToMany(mappedBy: 'privatecity', targetEntity: Ambassador::class)]
    private Collection $ambassadorsprivate;

    #[ORM\OneToMany(mappedBy: 'City', targetEntity: Ambassador::class)]
    private Collection $ambassadors;

    #[ORM\Column]
    private ?string $inseeCode = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\OneToMany(mappedBy: 'city', targetEntity: Store::class)]
    private Collection $stores;

    public function __construct()
    {
        $this->partners = new ArrayCollection();
        $this->addresses = new ArrayCollection();
        $this->collectionPoints = new ArrayCollection();
        $this->ambassadorsprivate = new ArrayCollection();
        $this->ambassadors = new ArrayCollection();
        $this->stores = new ArrayCollection();
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

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getPostalcode(): ?string
    {
        return $this->postalcode;
    }

    public function setPostalcode(string $postalcode): static
    {
        $this->postalcode = $postalcode;

        return $this;
    }

    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    public function setDepartment(?Department $department): static
    {
        $this->department = $department;

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

    public function getRvj2id(): ?int
    {
        return $this->rvj2id;
    }

    public function setRvj2id(?int $rvj2id): static
    {
        $this->rvj2id = $rvj2id;

        return $this;
    }

    /**
     * @return Collection<int, Partner>
     */
    public function getPartners(): Collection
    {
        return $this->partners;
    }

    public function addPartner(Partner $partner): static
    {
        if (!$this->partners->contains($partner)) {
            $this->partners->add($partner);
            $partner->setCity($this);
        }

        return $this;
    }

    public function removePartner(Partner $partner): static
    {
        if ($this->partners->removeElement($partner)) {
            // set the owning side to null (unless already changed)
            if ($partner->getCity() === $this) {
                $partner->setCity(null);
            }
        }

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
            $address->setCity($this);
        }

        return $this;
    }

    public function removeAddress(Address $address): static
    {
        if ($this->addresses->removeElement($address)) {
            // set the owning side to null (unless already changed)
            if ($address->getCity() === $this) {
                $address->setCity(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->postalcode.' '.$this->name;
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
            $collectionPoint->setCity($this);
        }

        return $this;
    }

    public function removeCollectionPoint(CollectionPoint $collectionPoint): static
    {
        if ($this->collectionPoints->removeElement($collectionPoint)) {
            // set the owning side to null (unless already changed)
            if ($collectionPoint->getCity() === $this) {
                $collectionPoint->setCity(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Ambassador>
     */
    public function getAmbassadorsprivate(): Collection
    {
        return $this->ambassadorsprivate;
    }

    public function addAmbassadorsprivate(Ambassador $ambassadorsprivate): static
    {
        if (!$this->ambassadorsprivate->contains($ambassadorsprivate)) {
            $this->ambassadorsprivate->add($ambassadorsprivate);
            $ambassadorsprivate->setPrivatecity($this);
        }

        return $this;
    }

    public function removeAmbassadorsprivate(Ambassador $ambassadorsprivate): static
    {
        if ($this->ambassadorsprivate->removeElement($ambassadorsprivate)) {
            // set the owning side to null (unless already changed)
            if ($ambassadorsprivate->getPrivatecity() === $this) {
                $ambassadorsprivate->setPrivatecity(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Ambassador>
     */
    public function getAmbassadors(): Collection
    {
        return $this->ambassadors;
    }

    public function addAmbassador(Ambassador $ambassador): static
    {
        if (!$this->ambassadors->contains($ambassador)) {
            $this->ambassadors->add($ambassador);
            $ambassador->setCity($this);
        }

        return $this;
    }

    public function removeAmbassador(Ambassador $ambassador): static
    {
        if ($this->ambassadors->removeElement($ambassador)) {
            // set the owning side to null (unless already changed)
            if ($ambassador->getCity() === $this) {
                $ambassador->setCity(null);
            }
        }

        return $this;
    }

    public function getInseeCode(): ?string
    {
        return $this->inseeCode;
    }

    public function setInseeCode(string $inseeCode): static
    {
        $this->inseeCode = $inseeCode;

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

    /**
     * @return Collection<int, Store>
     */
    public function getStores(): Collection
    {
        return $this->stores;
    }

    public function addStore(Store $store): static
    {
        if (!$this->stores->contains($store)) {
            $this->stores->add($store);
            $store->setCity($this);
        }

        return $this;
    }

    public function removeStore(Store $store): static
    {
        if ($this->stores->removeElement($store)) {
            // set the owning side to null (unless already changed)
            if ($store->getCity() === $this) {
                $store->setCity(null);
            }
        }

        return $this;
    }
}
