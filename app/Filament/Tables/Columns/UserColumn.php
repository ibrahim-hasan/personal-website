<?php

namespace App\Filament\Tables\Columns;

use Closure;
use Filament\Tables\Columns\Column;

class UserColumn extends Column
{
    protected string $view = 'filament.tables.columns.user-column';

    protected string|Closure|null $nameValue = null;

    protected string|Closure|null $titleValue = null;

    protected string|Closure|null $imageValue = null;

    public function userName(string|Closure|null $name): static
    {
        $this->nameValue = $name;

        return $this;
    }

    public function userTitle(string|Closure|null $title): static
    {
        $this->titleValue = $title;

        return $this;
    }

    public function userImage(string|Closure|null $image): static
    {
        $this->imageValue = $image;

        return $this;
    }

    public function getUserName(mixed $record): ?string
    {
        return $this->evaluate($this->nameValue, ['record' => $record]);
    }

    public function getUserTitle(mixed $record): ?string
    {
        return $this->evaluate($this->titleValue, ['record' => $record]);
    }

    public function getUserImage(mixed $record): ?string
    {
        return $this->evaluate($this->imageValue, ['record' => $record]);
    }
}
