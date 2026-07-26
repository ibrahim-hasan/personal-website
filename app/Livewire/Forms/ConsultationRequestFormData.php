<?php

namespace App\Livewire\Forms;

use App\Actions\Consultation\ConsultationRequestRules;
use Livewire\Form;

class ConsultationRequestFormData extends Form
{
    public string $name = '';

    public string $email = '';

    public string $company = '';

    public string $role = '';

    public string $service = '';

    public string $challenge = '';

    public string $timing = '';

    public string $website = '';

    public function rules(): array
    {
        return app(ConsultationRequestRules::class)->rules();
    }

    public function validationAttributes(): array
    {
        return app(ConsultationRequestRules::class)->validationAttributes();
    }
}
