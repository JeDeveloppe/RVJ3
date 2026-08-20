<?php

namespace App\Entity;

use App\Repository\SiteSettingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteSettingRepository::class)]
class SiteSetting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?bool $BlockEmailSending = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $Marquee = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $fairday = null;

    #[ORM\Column]
    private ?int $distanceMaxForOccasionBuy = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastPanierCleanupAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastJobPostCleanupAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBlockEmailSending(): ?bool
    {
        return $this->BlockEmailSending;
    }

    public function setBlockEmailSending(?bool $BlockEmailSending): static
    {
        $this->BlockEmailSending = $BlockEmailSending;

        return $this;
    }

    public function getMarquee(): ?string
    {
        return $this->Marquee;
    }

    public function setMarquee(?string $Marquee): static
    {
        $this->Marquee = $Marquee;

        return $this;
    }

    public function getFairday(): ?string
    {
        return $this->fairday;
    }

    public function setFairday(?string $fairday): static
    {
        $this->fairday = $fairday;

        return $this;
    }

    public function getDistanceMaxForOccasionBuy(): ?int
    {
        return $this->distanceMaxForOccasionBuy;
    }

    public function setDistanceMaxForOccasionBuy(int $distanceMaxForOccasionBuy): static
    {
        $this->distanceMaxForOccasionBuy = $distanceMaxForOccasionBuy;

        return $this;
    }

    public function getLastPanierCleanupAt(): ?\DateTimeImmutable
    {
        return $this->lastPanierCleanupAt;
    }

    public function setLastPanierCleanupAt(?\DateTimeImmutable $lastPanierCleanupAt): static
    {
        $this->lastPanierCleanupAt = $lastPanierCleanupAt;

        return $this;
    }

    public function getLastJobPostCleanupAt(): ?\DateTimeImmutable
    {
        return $this->lastJobPostCleanupAt;
    }

    public function setLastJobPostCleanupAt(?\DateTimeImmutable $lastJobPostCleanupAt): static
    {
        $this->lastJobPostCleanupAt = $lastJobPostCleanupAt;

        return $this;
    }
}
