<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $timeOfTransaction = null;

    #[ORM\ManyToOne(inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: true)]
    private ?MeansOfPayement $meansOfPayment = null;

    #[ORM\Column(length: 255)]
    private ?string $tokenPayment = null;

    #[ORM\OneToOne(inversedBy: 'payment', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Document $document = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $details = null;

    //?Identifiants HelloAsso remplaces par une nouvelle tentative de paiement sur le meme document
    //?(chaque visite de /paiement/{token} cree un nouveau checkout chez HelloAsso). On les garde :
    //?le client a pu regler l'ancien pendant qu'un nouveau etait cree.
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $previousTokenPayments = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTimeOfTransaction(): ?\DateTimeImmutable
    {
        return $this->timeOfTransaction;
    }

    public function setTimeOfTransaction(?\DateTimeImmutable $timeOfTransaction): static
    {
        $this->timeOfTransaction = $timeOfTransaction;

        return $this;
    }

    public function getMeansOfPayment(): ?MeansOfPayement
    {
        return $this->meansOfPayment;
    }

    public function setMeansOfPayment(?MeansOfPayement $meansOfPayment): static
    {
        $this->meansOfPayment = $meansOfPayment;

        return $this;
    }

    public function getTokenPayment(): ?string
    {
        return $this->tokenPayment;
    }

    public function setTokenPayment(string $tokenPayment): static
    {
        $this->tokenPayment = $tokenPayment;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getPreviousTokenPayments(): array
    {
        return $this->previousTokenPayments ?? [];
    }

    public function addPreviousTokenPayment(string $tokenPayment): static
    {
        $previous = $this->getPreviousTokenPayments();

        if(!in_array($tokenPayment, $previous, true)){
            $previous[] = $tokenPayment;
            $this->previousTokenPayments = $previous;
        }

        return $this;
    }

    /**
     * Identifiant courant en premier, puis les anciens (du plus recent au plus ancien).
     *
     * @return string[]
     */
    public function getAllTokenPayments(): array
    {
        $tokens = array_reverse($this->getPreviousTokenPayments());
        if($this->tokenPayment){
            array_unshift($tokens, $this->tokenPayment);
        }

        return array_values(array_unique($tokens));
    }

    public function __toString()
    {
        if(!is_null($this->meansOfPayment)){

            return $this->meansOfPayment;
            
        }else{

            return 'EN ATTENTE';
        }
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(Document $document): static
    {
        $this->document = $document;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;

        return $this;
    }

}
