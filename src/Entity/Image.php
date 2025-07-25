<?php

namespace App\Entity;

use App\Repository\ImageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ImageRepository::class)]
#[Vich\Uploadable] // <-- TRÈS IMPORTANT : Cette annotation doit être présente !
class Image
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // NOTE: Ceci est un champ non mappé à la base de données, utilisé par VichUploader pour l'upload.
    #[Vich\UploadableField(mapping: 'quote_request_line_images', fileNameProperty: 'imageName')] // <-- Le mapping doit correspondre à votre config Vich
    #[Assert\File(
        maxSize: '5M', // Limite la taille du fichier à 5 mégaoctets (vous pouvez utiliser '2M', '1024k', etc.)
        mimeTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], // Limite les types MIME acceptés
        mimeTypesMessage: 'Veuillez télécharger une image valide (JPEG, PNG, GIF, WebP).',
        maxSizeMessage: 'Le fichier image est trop volumineux ({{ size }} {{ suffix }}). La taille maximale autorisée est de {{ limit }} {{ suffix }}.',
    )]
    private ?File $imageFile = null;

    #[ORM\Column(nullable: true)]
    private ?string $imageName = null; // <-- C'est ici que le nom du fichier sera stocké

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null; // <-- TRÈS IMPORTANT : Ce champ déclenche l'upload

    #[ORM\ManyToOne(inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false)]
    private ?QuoteRequestLine $quoteRequestLine = null;

    public function getId(): ?int
    {
        return $this->id;
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
            // C'est cette ligne qui est cruciale : elle force la mise à jour de l'entité
            // et déclenche les écouteurs d'événements de VichUploader.
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageName(?string $imageName): void
    {
        $this->imageName = $imageName;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getQuoteRequestLine(): ?QuoteRequestLine
    {
        return $this->quoteRequestLine;
    }

    public function setQuoteRequestLine(?QuoteRequestLine $quoteRequestLine): static
    {
        $this->quoteRequestLine = $quoteRequestLine;

        return $this;
    }
}