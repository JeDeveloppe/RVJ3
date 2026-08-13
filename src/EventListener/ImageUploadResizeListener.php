<?php

namespace App\EventListener;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Event\Events as VichEvents;

//?Certains benevoles envoient des photos directement depuis leur telephone (ex: 3000x4000px,
//?~46 Mo une fois decompressee en memoire) : LiipImagine plante alors avec un depassement de
//?memoire PHP en essayant de generer les vignettes du catalogue. On reduit donc toute image
//?trop grande juste apres l'upload (avant meme la premiere generation de vignette), une seule
//?fois, de facon transparente pour la personne qui envoie la photo.
#[AsEventListener(event: VichEvents::POST_UPLOAD)]
class ImageUploadResizeListener
{
    private const MAX_DIMENSION = 2000;
    private const JPEG_QUALITY = 85;

    public function __invoke(Event $event): void
    {
        $mapping = $event->getMapping();
        $uploadName = $mapping->getFileName($event->getObject());

        if ($uploadName === null) {
            return;
        }

        $path = rtrim($mapping->getUploadDestination(), '/').'/'.$uploadName;

        if (!is_file($path)) {
            return;
        }

        $dimensions = @getimagesize($path);
        if ($dimensions === false) {
            return;
        }

        [$width, $height] = $dimensions;
        if (max($width, $height) <= self::MAX_DIMENSION) {
            return;
        }

        //?Les photos surdimensionnees sont rares (upload manuel par un benevole) : on peut se
        //?permettre d'augmenter temporairement la limite memoire pour cette seule operation,
        //?plutot que de l'augmenter globalement pour toutes les requetes du site.
        $previousMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            $imagine = new Imagine();
            $image = $imagine->open($path);
            $image->thumbnail(new Box(self::MAX_DIMENSION, self::MAX_DIMENSION), ImageInterface::THUMBNAIL_INSET)
                ->save($path, ['jpeg_quality' => self::JPEG_QUALITY, 'png_compression_level' => 6]);
        } catch (\Throwable) {
            //?Si le redimensionnement echoue pour une raison quelconque, on garde l'image
            //?d'origine plutot que de faire echouer tout l'upload.
        } finally {
            ini_set('memory_limit', $previousMemoryLimit);
        }
    }
}
