<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReadSuryaOutletMappingOriginals extends Command
{
    protected $signature = 'surya:outlet-mapping-originals {ids*}';

    protected $description = 'Read-only fetch of outlet frequency and PJP originals for the Telegram mapping workflow';

    public function handle(): int
    {
        $ids = array_values(array_unique(array_map('strval', $this->argument('ids'))));
        if (!$ids || count($ids) > 1000) {
            $this->error('Provide between 1 and 1000 outlet IDs.');
            return self::INVALID;
        }

        foreach ($ids as $id) {
            if (!preg_match('/^[1-9][0-9]{0,17}$/D', $id)) {
                $this->error('Invalid outlet ID.');
                return self::INVALID;
            }
        }

        $rows = DB::table('appsdumais')->whereIn('id_outlet', $ids)
            ->get(['id_outlet', 'freq', 'pjp'])->keyBy(fn ($row) => (string) $row->id_outlet);
        $missing = array_values(array_diff($ids, $rows->keys()->all()));
        if ($missing) {
            $this->error('Unknown outlet IDs: '.implode(',', $missing));
            return self::FAILURE;
        }

        $result = [];
        foreach ($ids as $id) {
            $row = $rows->get($id);
            $result[$id] = [
                'freq' => trim((string) ($row->freq ?? 'UNMAPPING')) ?: 'UNMAPPING',
                'pjp' => trim((string) ($row->pjp ?? 'UNMAPPING')) ?: 'UNMAPPING',
            ];
        }

        $this->line(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return self::SUCCESS;
    }
}
