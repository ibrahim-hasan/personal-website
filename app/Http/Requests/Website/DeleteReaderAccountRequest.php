<?php

namespace App\Http\Requests\Website;

use App\Models\User;
use Illuminate\Foundation\Http\Attributes\ErrorBag;
use Illuminate\Foundation\Http\FormRequest;

#[ErrorBag('accountDeletion')]
class DeleteReaderAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reader = $this->user();

        return $reader instanceof User
            && $reader->isReaderAccount()
            && ! $reader->canAccessPanel(filament()->getPanel('admin'));
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password:web'],
            'acknowledgement' => ['required', 'accepted'],
        ];
    }
}
