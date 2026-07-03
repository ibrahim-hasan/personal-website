<?php

namespace App\Livewire\Forms;

use Livewire\Form;

class GuideDownloaderFormData extends Form
{
    public int $guide_id = 0;

    public string $email = '';

    public function rules(): array
    {
        return [
            'guide_id' => ['required', 'integer', 'exists:guides,id'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }
}
