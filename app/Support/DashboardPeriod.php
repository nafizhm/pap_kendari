<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DashboardPeriod
{
    public function __construct(
        public string $mode,
        public ?CarbonImmutable $start,
        public ?CarbonImmutable $end,
        public string $label,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $data = $request->validate([
            'period' => ['sometimes', Rule::in(['all', 'this_month', 'last_month', 'month', 'custom'])],
            'month' => ['exclude_unless:period,month', 'required', 'date_format:Y-m'],
            'start_date' => ['exclude_unless:period,custom', 'required', 'date_format:Y-m-d'],
            'end_date' => ['exclude_unless:period,custom', 'required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);
        $mode = $data['period'] ?? 'all';
        $now = CarbonImmutable::now(config('app.timezone'))->locale('id');
        if ($mode === 'all') return new self($mode, null, null, 'Semua Waktu');
        if ($mode === 'custom') {
            $start = CarbonImmutable::createFromFormat('!Y-m-d', $data['start_date'])->locale('id');
            $end = CarbonImmutable::createFromFormat('!Y-m-d', $data['end_date'])->locale('id');
            return new self($mode, $start, $end, $start->translatedFormat('d M Y').' – '.$end->translatedFormat('d M Y'));
        }
        $start = match ($mode) {
            'last_month' => $now->startOfMonth()->subMonth(),
            'month' => CarbonImmutable::createFromFormat('!Y-m', $data['month'])->locale('id'),
            default => $now->startOfMonth(),
        };
        return new self($mode, $start, $start->endOfMonth()->startOfDay(), $start->translatedFormat('F Y'));
    }

    public function apply(Builder $query, string $column = 'tanggal_verif'): Builder
    {
        if ($this->start) {
            $query->where($column, '>=', $this->start->toDateString())
                ->where($column, '<', $this->end->addDay()->toDateString());
        }
        return $query;
    }

    public function parameters(): array
    {
        return match ($this->mode) {
            'month' => ['period' => 'month', 'month' => $this->start->format('Y-m')],
            'custom' => ['period' => 'custom', 'start_date' => $this->start->toDateString(), 'end_date' => $this->end->toDateString()],
            default => ['period' => $this->mode],
        };
    }
}
