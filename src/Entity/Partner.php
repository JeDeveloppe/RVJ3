<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\PartnerRepository;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: PartnerRepository::class)]
#[Vich\Uploadable]
class Partner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $collect = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $sells = null;

    #[ORM\Column]
    private ?bool $isAcceptDonations = null;

    #[ORM\Column(length: 255)]
    private ?string $fullUrl = null;

    #[ORM\Column]
    private ?bool $isSellsSpareParts = null;

    #[ORM\Column]
    private ?bool $isWebShop = null;

    #[ORM\Column]
    private ?bool $isOnline = null;

    #[ORM\Column]
    private ?bool $isDisplayOnCatalogueWhenSearchIsNull = null;

    #[ORM\ManyToOne(inversedBy: 'partners')]
    #[ORM\JoinColumn(nullable: false)]
    private ?City $city = null;

    #[ORM\Column]
    private ?bool $isSellFullGames = null;

    // NOTE: This is not a mapped field of entity metadata, just a simple property.
    #[Vich\UploadableField(mapping: 'partners', fileNameProperty: 'image')]
    private ?File $imageFile = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?string $image = null;

    #[ORM\Column]
    private ?int $rvj2id = null;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCollect(): ?string
    {
        return $this->collect;
    }

    public function setCollect(?string $collect): static
    {
        $this->collect = $collect;

        return $this;
    }

    public function getSells(): ?string
    {
        return $this->sells;
    }

    public function setSells(string $sells): static
    {
        $this->sells = $sells;

        return $this;
    }

    public function isIsAcceptDonations(): ?bool
    {
        return $this->isAcceptDonations;
    }

    public function setIsAcceptDonations(bool $isAcceptDonations): static
    {
        $this->isAcceptDonations = $isAcceptDonations;

        return $this;
    }

    public function getFullUrl(): ?string
    {
        return $this->fullUrl;
    }

    public function setFullUrl(string $fullUrl): static
    {
        $this->fullUrl = $fullUrl;

        return $this;
    }

    public function isIsSellsSpareParts(): ?bool
    {
        return $this->isSellsSpareParts;
    }

    public function setIsSellsSpareParts(bool $isSellsSpareParts): static
    {
        $this->isSellsSpareParts = $isSellsSpareParts;

        return $this;
    }

    public function isIsWebShop(): ?bool
    {
        return $this->isWebShop;
    }

    public function setIsWebShop(bool $isWebShop): static
    {
        $this->isWebShop = $isWebShop;

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

    public function isIsDisplayOnCatalogueWhenSearchIsNull(): ?bool
    {
        return $this->isDisplayOnCatalogueWhenSearchIsNull;
    }

    public function setIsDisplayOnCatalogueWhenSearchIsNull(bool $isDisplayOnCatalogueWhenSearchIsNull): static
    {
        $this->isDisplayOnCatalogueWhenSearchIsNull = $isDisplayOnCatalogueWhenSearchIsNull;

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

    public function isIsSellFullGames(): ?bool
    {
        return $this->isSellFullGames;
    }

    public function setIsSellFullGames(bool $isSellFullGames): static
    {
        $this->isSellFullGames = $isSellFullGames;

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

    public function getRvj2id(): ?int
    {
        return $this->rvj2id;
    }

    public function setRvj2id(int $rvj2id): static
    {
        $this->rvj2id = $rvj2id;

        return $this;
    }
}
