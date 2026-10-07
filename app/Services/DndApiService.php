<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DndApiService
{
    private string $baseUrl = 'https://www.dnd5eapi.co/api/2014';

    public function getMonsters(): array
    {
        $response = Http::get($this->baseUrl . '/monsters');

        return $response->json();
    }

    public function getMonster(string $index): ?array
    {
        $response = Http::get($this->baseUrl . '/monsters/' . $index);

        if ($response->notFound()) {
            return null;
        }

        return $response->json();
    }

    public function getSpells(): array
    {
        $response = Http::get($this->baseUrl . '/spells');

        return $response->json();
    }

    public function getSpell(string $index): ?array
    {
        $response = Http::get($this->baseUrl . '/spells/' . $index);

        if ($response->notFound()) {
            return null;
        }

        return $response->json();
    }

    public function getClasses(): array
{
    $response = Http::get($this->baseUrl . '/classes');

    return $response->json();
}

public function getClass(string $index): ?array
{
    $response = Http::get($this->baseUrl . '/classes/' . $index);

    if ($response->notFound()) {
        return null;
    }

    return $response->json();
}
}