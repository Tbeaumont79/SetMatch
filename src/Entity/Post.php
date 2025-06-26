<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[Vich\Uploadable]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(
        message: 'Le contenu du post ne peut pas être vide.'
    )]
    #[Assert\Length(
        min: 3,
        max: 5000,
        minMessage: 'Le contenu doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le contenu ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[^<>{}]*$/',
        message: 'Le contenu contient des caractères non autorisés.'
    )]
    private ?string $content = null;

    #[ORM\Column]
    #[Assert\NotNull(
        message: 'La date de création est obligatoire.'
    )]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(
        message: 'Un post doit avoir un auteur.',
        groups: ['service_validation']
    )]
    private ?User $author = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le nom du fichier image ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $image = null;

    #[Vich\UploadableField(mapping: 'post_images', fileNameProperty: 'image')]
    #[Assert\File(
        maxSize: '2M',
        mimeTypes: [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif'
        ],
        mimeTypesMessage: 'Veuillez télécharger une image valide (JPEG, PNG, WebP, GIF).',
        maxSizeMessage: 'L\'image ne doit pas dépasser {{ limit }}.'
    )]
    private ?File $imageFile = null;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile = null): static
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // Déclencher la mise à jour de updated_at quand un fichier est uploadé
            $this->updated_at = new \DateTimeImmutable();
        }

        return $this;
    }
}
