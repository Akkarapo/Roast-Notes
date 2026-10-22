<?php

namespace App\Support\Reports;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * The period every report on the page shares: a preset (30d, 90d, 12m, ytd) or a custom from/to (both days included).
 */
final readonly class DateRange
{
    public const PRESETS = ['30d', '90d', '12m', 'ytd'];

    public const DEFAULT = '90d';

    public const MAX_YEARS = 3;

    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
        public ?string $preset = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $from = self::date($request->query('from'));
        $to = self::date($request->query('to'));
        if ($from && $to && $from->lte($to) && $from->gte($to->subYears(self::MAX_YEARS))) {
            return new self($from->startOfDay(), $to->endOfDay());
        }

        return self::preset((string) $request->query('range'));
    }

    public static function preset(string $preset): self
    {
        $preset = in_array($preset, self::PRESETS, true) ? $preset : self::DEFAULT;
        $today = CarbonImmutable::today();
        $start = match ($preset) {
            '30d' => $today->subDays(29),
            '90d' => $today->subDays(89),
            '12m' => $today->subMonthsNoOverflow(11)->startOfMonth(),
            'ytd' => $today->startOfYear(),
        };

        return new self($start, $today->endOfDay(), $preset);
    }

    private static function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }

    /** @return array{string, string} for whereBetween on DATETIME columns */
    public function bounds(): array
    {
        return [$this->start->format('Y-m-d H:i:s'), $this->end->format('Y-m-d H:i:s')];
    }

    public function days(): int
    {
        return (int) $this->start->startOfDay()->diffInDays($this->end->startOfDay()) + 1;
    }

    /** The same number of days, ending the day before this range starts. */
    public function previous(): self
    {
        $end = $this->start->subDay()->endOfDay();

        return new self($end->subDays($this->days() - 1)->startOfDay(), $end);
    }

    /** Daily bars for short ranges, monthly otherwise. */
    public function granularity(): string
    {
        return $this->days() <= 62 ? 'day' : 'month';
    }

    /** @return array<string, string> query string that reproduces this range */
    public function query(): array
    {
        return $this->preset
            ? ['range' => $this->preset]
            : ['from' => $this->start->toDateString(), 'to' => $this->end->toDateString()];
    }

    public function label(): string
    {
        return $this->start->translatedFormat('j M Y').' – '.$this->end->translatedFormat('j M Y');
    }
}
