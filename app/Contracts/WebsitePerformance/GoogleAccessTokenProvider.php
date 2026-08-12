<?php

namespace App\Contracts\WebsitePerformance;

interface GoogleAccessTokenProvider
{
    public function accessToken(): string;
}
