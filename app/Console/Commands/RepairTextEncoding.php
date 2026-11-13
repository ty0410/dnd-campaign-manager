<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repara textos guardados con la codificación rota (por ejemplo «Sesi├│n» en
 * lugar de «Sesión»), que aparece al insertar datos desde una consola de Windows
 * con la página de códigos CP437. Solo toca textos que, al deshacer el error,
 * dan UTF-8 válido.
 *
 *   php artisan data:repair-encoding --dry-run   (solo muestra los cambios)
 *   php artisan data:repair-encoding             (los aplica)
 */
class RepairTextEncoding extends Command
{
    protected $signature = 'data:repair-encoding {--dry-run : Muestra los cambios sin guardarlos}';

    protected $description = 'Repara textos con codificación rota («Sesi├│n» → «Sesión»).';

    private const COLUMNS = [
        'campaigns' => ['name', 'description'],
        'game_sessions' => ['title', 'summary', 'notes'],
        'locations' => ['name', 'type', 'description'],
        'npcs' => ['name', 'race', 'role', 'location', 'description'],
        'encounters' => ['name', 'description'],
        'characters' => ['name', 'description', 'personality_trait', 'ideal', 'bond', 'flaw', 'notes'],
    ];

    /** Mitad alta de CP437 (bytes 0x80 a 0xFF). */
    private const CP437_HIGH = 'ÇüéâäàåçêëèïîìÄÅÉæÆôöòûùÿÖÜ¢£¥₧ƒáíóúñÑªº¿⌐¬½¼¡«»░▒▓│┤╡╢╖╕╣║╗╝╜╛┐└┴┬├─┼╞╟╚╔╩╦╠═╬╧╨╤╥╙╘╒╓╫╪┘┌█▄▌▐▀αßΓπΣσµτΦΘΩδ∞φε∩≡±≥≤⌠⌡÷≈°∙·√ⁿ²■ ';

    private array $map = [];

    public function handle(): int
    {
        $chars = preg_split('//u', self::CP437_HIGH, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($chars as $offset => $char) {
            $this->map[$char] = 0x80 + $offset;
        }

        $dry = (bool) $this->option('dry-run');
        $total = 0;

        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (DB::table($table)->get() as $row) {
                $changes = [];

                foreach ($columns as $column) {
                    if (! property_exists($row, $column) || ! is_string($row->{$column})) {
                        continue;
                    }

                    $fixed = $this->repair($row->{$column});

                    if ($fixed !== null && $fixed !== $row->{$column}) {
                        $changes[$column] = $fixed;
                        $this->line("{$table}#{$row->id}.{$column}: «{$row->{$column}}» → «{$fixed}»");
                    }
                }

                if ($changes) {
                    $total++;

                    if (! $dry) {
                        DB::table($table)->where('id', $row->id)->update($changes);
                    }
                }
            }
        }

        $this->info($dry ? "{$total} registro(s) se repararían." : "{$total} registro(s) reparados.");

        return self::SUCCESS;
    }

    /** Devuelve el texto reparado o null si no se puede (o no hace falta). */
    private function repair(string $text): ?string
    {
        $bytes = '';

        foreach (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
            if (strlen($char) === 1) {
                $bytes .= $char;
            } elseif (isset($this->map[$char])) {
                $bytes .= chr($this->map[$char]);
            } else {
                return null;
            }
        }

        if ($bytes === $text || ! mb_check_encoding($bytes, 'UTF-8')) {
            return null;
        }

        return $bytes;
    }
}
