<?php

namespace App\Services\ArticleAudio;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;

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

        return $this->rewriteDurationMetadata($audio);
    }

    private function rewriteDurationMetadata(string $audio): string
    {
        $binary = (string) config('services.elevenlabs.ffmpeg_binary', 'ffmpeg');

        if ($binary === '') {
            return $audio;
        }

        $input = tempnam(sys_get_temp_dir(), 'article-audio-input-');
        $output = tempnam(sys_get_temp_dir(), 'article-audio-output-');

        if ($input === false || $output === false) {
            if ($input !== false) {
                @unlink($input);
            }

            if ($output !== false) {
                @unlink($output);
            }

            return $audio;
        }

        try {
            if (file_put_contents($input, $audio) === false) {
                return $audio;
            }

            $process = new Process([
                $binary,
                '-hide_banner',
                '-loglevel',
                'error',
                '-y',
                '-i',
                $input,
                '-map',
                '0:a:0',
                '-c:a',
                'copy',
                '-write_xing',
                '1',
                '-f',
                'mp3',
                $output,
            ]);
            $process->setTimeout(120);
            $process->run();

            if (! $process->isSuccessful() || ! is_file($output) || filesize($output) === 0) {
                return $audio;
            }

            $normalized = file_get_contents($output);

            return is_string($normalized) && $normalized !== '' ? $normalized : $audio;
        } catch (\Throwable $exception) {
            Log::warning('Unable to normalize concatenated MP3 metadata.', [
                'exception' => $exception,
            ]);

            return $audio;
        } finally {
            @unlink($input);
            @unlink($output);
        }
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
