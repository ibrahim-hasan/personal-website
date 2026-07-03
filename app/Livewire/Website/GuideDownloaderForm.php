<?php

namespace App\Livewire\Website;

use App\Livewire\Forms\GuideDownloaderFormData;
use App\Mail\GuideDownloadMail;
use App\Models\Guide;
use App\Models\GuideDownloader;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class GuideDownloaderForm extends Component
{
    public GuideDownloaderFormData $form;

    public int $guideId;

    public bool $submitted = false;

    public string $errorMessage = '';

    public function mount(int $guideId): void
    {
        $this->guideId = $guideId;
        $this->form->guide_id = $guideId;
    }

    public function submit(): void
    {
        $this->errorMessage = '';
        $this->form->guide_id = $this->guideId;
        $this->form->validate();

        Guide::query()->posted()->findOrFail($this->guideId);

        $downloader = GuideDownloader::query()
            ->where('email', $this->form->email)
            ->where('guide_id', $this->guideId)
            ->first();

        if ($downloader) {
            if (Cache::has($this->resendCooldownKey($downloader->email))) {
                $this->errorMessage = __('guide.resend_wait_message');

                return;
            }

            $token = $downloader->generateDownloadToken();
            Mail::to($downloader)->send(new GuideDownloadMail($downloader, $token));
            $downloader->update(['is_mail_sent' => true]);
            Cache::put($this->resendCooldownKey($downloader->email), true, now()->addMinutes(2));

            $this->form->reset();
            $this->form->guide_id = $this->guideId;
            $this->submitted = true;

            return;
        }

        $downloader = GuideDownloader::create([
            'guide_id' => $this->guideId,
            'email' => $this->form->email,
            'is_mail_sent' => true,
        ]);

        $token = $downloader->generateDownloadToken();
        Mail::to($downloader)->send(new GuideDownloadMail($downloader, $token));
        Cache::put($this->resendCooldownKey($downloader->email), true, now()->addMinutes(2));

        $this->form->reset();
        $this->form->guide_id = $this->guideId;
        $this->submitted = true;
    }

    public function render(): View
    {
        return view('livewire.website.guide-downloader-form');
    }

    private function resendCooldownKey(string $email): string
    {
        return 'guide-download:resend-cooldown:'.$this->guideId.':'.mb_strtolower(trim($email));
    }
}
