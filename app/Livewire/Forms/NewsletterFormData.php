<?php

namespace App\Livewire\Forms;

use Livewire\Form;

class NewsletterFormData extends Form
{
    public string $email = '';

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'unique:newsletters,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => __('newsletter.already_subscribed'),
        ];
    }
}
