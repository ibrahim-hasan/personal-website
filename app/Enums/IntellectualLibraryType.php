<?php

namespace App\Enums;

/** Retained only so the immutable legacy table migration can build fresh databases. */
enum IntellectualLibraryType: string
{
    case Article = 'article';
    case Tool = 'tool';
    case Video = 'video';
    case Podcast = 'podcast';
}
