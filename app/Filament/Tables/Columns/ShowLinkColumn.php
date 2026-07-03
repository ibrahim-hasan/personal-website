<?php

namespace App\Filament\Tables\Columns;

use Closure;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Gate;

class ShowLinkColumn extends TextColumn
{
    protected string|Closure|null $permission = null;

    protected string|Closure|null $linkUrl = null;

    public function permission(string|Closure|null $permission): static
    {
        $this->permission = $permission;

        return $this;
    }

    public function linkUrl(string|Closure|null $url): static
    {
        $this->linkUrl = $url;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->color('primary');

        $this->url(function ($record): ?string {
            if (! $this->canAccessLink($record)) {
                return null;
            }

            return $this->evaluate($this->linkUrl, ['record' => $record]);
        });
    }

    protected function canAccessLink(mixed $record): bool
    {
        $permission = $this->evaluate($this->permission, ['record' => $record]);

        if (blank($permission)) {
            return true;
        }

        return Gate::allows($permission);
    }
}
