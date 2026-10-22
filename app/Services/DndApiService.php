<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DndApiService
{
    private string $baseUrl = 'https://www.dnd5eapi.co/api/2014';

    /**
     * Obtiene la lista de monstruos.
     */
    public function getMonsters(): array
    {
        return Http::timeout(15)
            ->get($this->baseUrl . '/monsters')
            ->throw()
            ->json();
    }

    /**
     * Obtiene un monstruo por su identificador.
     */
    public function getMonster(string $index): ?array
    {
        $response = Http::timeout(15)
            ->get($this->baseUrl . '/monsters/' . $index);

        if ($response->notFound()) {
            return null;
        }

        $response->throw();

        return $response->json();
    }

    /**
     * Obtiene la lista de hechizos.
     */
    public function getSpells(): array
    {
        return Http::timeout(15)
            ->get($this->baseUrl . '/spells')
            ->throw()
            ->json();
    }

    /**
     * Obtiene un hechizo por su identificador.
     */
    public function getSpell(string $index): ?array
    {
        $response = Http::timeout(15)
            ->get($this->baseUrl . '/spells/' . $index);

        if ($response->notFound()) {
            return null;
        }

        $response->throw();

        return $response->json();
    }

    /**
     * Obtiene la lista de clases.
     */
    public function getClasses(): array
    {
        return Http::timeout(15)
            ->get($this->baseUrl . '/classes')
            ->throw()
            ->json();
    }

    /**
     * Obtiene una clase por su identificador.
     */
    public function getClass(string $index): ?array
    {
        $response = Http::timeout(15)
            ->get($this->baseUrl . '/classes/' . $index);

        if ($response->notFound()) {
            return null;
        }

        $response->throw();

        return $response->json();
    }

    /**
     * Obtiene la lista de equipo.
     */
    public function getEquipment(): array
    {
        return Http::timeout(15)
            ->get($this->baseUrl . '/equipment')
            ->throw()
            ->json();
    }

    /**
     * Obtiene un objeto de equipo por su identificador.
     */
    public function getEquipmentItem(string $index): ?array
    {
        $response = Http::timeout(15)
            ->get($this->baseUrl . '/equipment/' . $index);

        if ($response->notFound()) {
            return null;
        }

        $response->throw();

        return $response->json();
    }

    /**
     * Obtiene la lista de razas.
     */
    public function getRaces(): array
    {
        return Http::timeout(15)
            ->get($this->baseUrl . '/races')
            ->throw()
            ->json();
    }

    /**
     * Obtiene una raza por su identificador.
     */
    public function getRace(string $index): ?array
    {
        $response = Http::timeout(15)
            ->get($this->baseUrl . '/races/' . $index);

        if ($response->notFound()) {
            return null;
        }

        $response->throw();

        return $response->json();
    }

    /**
     * Obtiene la lista de subrazas.
     */
    public function getSubraces(): array
    {
        return Http::timeout(15)
            ->get($this->baseUrl . '/subraces')
            ->throw()
            ->json();
    }

    /**
     * Obtiene una subraza por su identificador.
     */
    public function getSubrace(string $index): ?array
    {
        $response = Http::timeout(15)
            ->get($this->baseUrl . '/subraces/' . $index);

        if ($response->notFound()) {
            return null;
        }

        $response->throw();

        return $response->json();
    }

    /**
     * Obtiene la lista de trasfondos.
     */
    public function getBackgrounds(): array
    {
        return Http::timeout(15)
            ->get($this->baseUrl . '/backgrounds')
            ->throw()
            ->json();
    }

    /**
     * Obtiene un trasfondo por su identificador.
     */
    public function getBackground(string $index): ?array
    {
        $response = Http::timeout(15)
            ->get($this->baseUrl . '/backgrounds/' . $index);

        if ($response->notFound()) {
            return null;
        }

        $response->throw();

        return $response->json();
    }
}
