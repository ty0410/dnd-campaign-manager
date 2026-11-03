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

    /** Lista los conjuros disponibles para una clase en la API 2014. */
    public function getClassSpells(string $classIndex): array
    {
        return Http::timeout(20)->get($this->baseUrl . '/classes/' . $classIndex . '/spells')->throw()->json();
    }

    /** Consulta en paralelo los detalles de una lista de conjuros para evitar una petición secuencial por elemento. */
    public function getSpellDetailsForIndexes(array $indexes): array
    {
        $indexes = array_values(array_unique(array_filter($indexes)));
        if (!$indexes) return [];
        $missing = [];
        $cached = [];
        foreach ($indexes as $index) {
            $value = \Illuminate\Support\Facades\Cache::get('dnd_spell_' . $index);
            if ($value) $cached[$index] = $value;
            else $missing[] = $index;
        }
        foreach (array_chunk($missing, 25) as $batch) {
            $responses = \Illuminate\Support\Facades\Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($batch) {
                return array_map(fn ($index) => $pool->as($index)->timeout(15)->get($this->baseUrl . '/spells/' . $index), $batch);
            });
            foreach ($batch as $index) {
                $response = $responses[$index] ?? null;
                if ($response && $response->successful()) {
                    $cached[$index] = $response->json();
                    \Illuminate\Support\Facades\Cache::put('dnd_spell_' . $index, $cached[$index], now()->addDay());
                }
            }
        }
        return array_values($cached);
    }

    /** Lista de conjuros propia de una subclase lanzadora de conjuros. */
    public function getSubclassSpells(string $subclassIndex): array
    {
        return Http::timeout(20)->get($this->baseUrl . '/subclasses/' . $subclassIndex . '/spells')->throw()->json();
    }

    /** Detalle de una subclase. */
    public function getSubclass(string $index): ?array
    {
        $response = Http::timeout(15)->get($this->baseUrl . '/subclasses/' . $index);
        if ($response->notFound()) return null;
        $response->throw();
        return $response->json();
    }

    /** Progresión de una subclase por nivel. */
    public function getSubclassLevel(string $index, int $level): ?array
    {
        $response = Http::timeout(15)->get($this->baseUrl . '/subclasses/' . $index . '/levels/' . $level);
        if ($response->notFound()) return null;
        $response->throw();
        return $response->json();
    }

    /** Obtiene la descripción de un rasgo de clase. */
    public function getFeature(string $index): ?array
    {
        $response = Http::timeout(15)->get($this->baseUrl . '/features/' . $index);
        if ($response->notFound()) return null;
        $response->throw();
        return $response->json();
    }

    /** Lista las dotes disponibles en el catálogo 2014. */
    public function getFeats(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('dnd_2014_feats', now()->addDay(), function () {
            return Http::timeout(20)->get($this->baseUrl . '/feats')->throw()->json();
        });
    }

    /** Obtiene el detalle de una dote. */
    public function getFeat(string $index): ?array
    {
        $response = Http::timeout(15)->get($this->baseUrl . '/feats/' . $index);
        if ($response->notFound()) return null;
        $response->throw();
        return $response->json();
    }

    /** Obtiene las características desbloqueadas por una clase en un nivel concreto. */
    public function getClassLevel(string $classIndex, int $level): ?array
    {
        $response = Http::timeout(15)->get($this->baseUrl . '/classes/' . $classIndex . '/levels/' . $level);
        if ($response->notFound()) return null;
        $response->throw();
        return $response->json();
    }

    /** Obtiene el detalle de una competencia del catálogo. */
    public function getProficiency(string $index): ?array
    {
        $response = Http::timeout(15)->get($this->baseUrl . '/proficiencies/' . $index);
        if ($response->notFound()) return null;
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
