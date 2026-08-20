<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('html_decode', [$this, 'htmlDecode']),
        ];
    }

    //?TinyMCE encode les caracteres speciaux en entites HTML nommees (&eacute;, &rsquo;,
    //?&nbsp;...) dans le contenu qu'il genere. Une fois les balises retirees (striptags,
    //?pour un extrait en texte brut), ces entites restent telles quelles et Twig les
    //?echappe une seconde fois a l'affichage (&eacute; devient visible tel quel au lieu
    //?de "e"). Ce filtre les decode avant troncature/affichage.
    public function htmlDecode(?string $text): string
    {
        return html_entity_decode($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}
