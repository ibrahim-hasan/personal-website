<?php

namespace App\Services\ArticleAudio;

use Symfony\Component\Process\Process;

class Mp3Metadata
{
    public function durationSeconds(string $audio): ?int
    {
        if ($audio === '') {
            return null;
        }

        $input = tempnam(sys_get_temp_dir(), 'article-audio-');

        if ($input === false) {
            return null;
        }

        try {
            if (file_put_contents($input, $audio) === false) {
                return null;
            }

            $binary = (string) config('services.elevenlabs.ffprobe_binary', 'ffprobe');

            if ($binary === '') {
                return null;
            }

            $process = new Process([
                $binary,
                '-v',
                'error',
                '-show_entries',
                'format=duration',
                '-of',
                'default=noprint_wrappers=1:nokey=1',
                $input,
            ]);
            $process->setTimeout(15);
            $process->run();

            if (! $process->isSuccessful()) {
                return null;
            }

            $duration = (float) trim($process->getOutput());

            return $duration > 0 ? (int) round($duration) : null;
        } finally {
            @unlink($input);
        }
    }
}
