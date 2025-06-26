<?php

namespace App\Exception;

class PostException extends \Exception
{
    public static function invalidContent(string $reason): self
    {
        return new self("Contenu du post invalide: {$reason}");
    }

    public static function authorRequired(): self
    {
        return new self("Un auteur est requis pour créer un post");
    }

    public static function postNotFound(int $id): self
    {
        return new self("Post #{$id} introuvable");
    }

    public static function unauthorizedAction(): self
    {
        return new self("Action non autorisée sur ce post");
    }

    public static function imageTooLarge(string $maxSize): self
    {
        return new self("Image trop volumineuse. Taille maximale: {$maxSize}");
    }

    public static function unsupportedImageFormat(array $allowedFormats): self
    {
        $formats = implode(', ', $allowedFormats);
        return new self("Format d'image non supporté. Formats autorisés: {$formats}");
    }
}
