<?php

namespace App\Services\ArticleAudio;

use RuntimeException;

class Mp3Concatenator
{
    /** @param list<string> $segments */
    public function concatenate(array $segments): string
    {
        if ($segments === []) {
            throw new RuntimeException('No MP3 segments were returned by the speech provider.');
        }

        $audio = '';

        foreach ($segments as $index => $segment) {
            if ($segment === '') {
                throw new RuntimeException('An empty MP3 segment was returned by the speech provider.');
            }

            $segment = $this->stripId3v1($segment);

            if ($index > 0) {
                $segment = $this->stripId3v2($segment);
            }

            $audio .= $segment;
        }

        return $audio;
    }

    private function stripId3v1(string $audio): string
    {
        if (strlen($audio) >= 128 && substr($audio, -128, 3) === 'TAG') {
            return substr($audio, 0, -128);
        }

        return $audio;
    }

    private function stripId3v2(string $audio): string
    {
        if (strlen($audio) < 10 || substr($audio, 0, 3) !== 'ID3') {
            return $audio;
        }

        $flags = ord($audio[5]);
        $size = (ord($audio[6]) << 21)
            | (ord($audio[7]) << 14)
            | (ord($audio[8]) << 7)
            | ord($audio[9]);
        $tagLength = 10 + $size + (($flags & 0x10) === 0x10 ? 10 : 0);

        return substr($audio, min($tagLength, strlen($audio)));
    }
}
