<?php

namespace App\Policies;

use App\Models\ContactInquiry;
use App\Models\User;

class ContactInquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any contact_inquiries');
    }

    public function view(User $user, ContactInquiry $contactInquiry): bool
    {
        return $user->hasPermissionTo('view contact_inquiries');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ContactInquiry $contactInquiry): bool
    {
        return $user->hasPermissionTo('update contact_inquiries');
    }

    public function delete(User $user, ContactInquiry $contactInquiry): bool
    {
        return false;
    }

    public function restore(User $user, ContactInquiry $contactInquiry): bool
    {
        return false;
    }

    public function forceDelete(User $user, ContactInquiry $contactInquiry): bool
    {
        return false;
    }
}
