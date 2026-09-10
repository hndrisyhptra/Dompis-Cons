<?php

namespace App\Services;

use App\Models\BoqItem;
use App\Models\Designator;
use Illuminate\Support\Collection;

class SurveyPreparationService
{
    /**
     * Satukan pasangan Material/Jasa yang mempunyai pair_code yang sama.
     * Item tanpa pair_code tetap berdiri sendiri.
     */
    public function groupBoqItems(Collection $items): Collection
    {
        return $items
            ->groupBy(fn (BoqItem $item) => $this->boqGroupKey($item))
            ->map(function (Collection $group, string $key): array {
                $sorted = $group->sortBy(fn (BoqItem $item) => $this->rank(
                    $this->designatorFor($item)?->type,
                    $item->designator
                ));

                /** @var BoqItem $representative */
                $representative = $sorted->first();
                $plannedItem = $sorted->first(fn (BoqItem $item) => $item->quantity_plan !== null)
                    ?? $representative;
                $actualItem = $sorted->first(fn (BoqItem $item) => $this->isMaterial(
                    $this->designatorFor($item)?->type,
                    $item->designator
                )) ?? $representative;
                $representativeDesignator = $this->designatorFor($representative);

                return [
                    'key' => $key,
                    'representative_id' => $representative->id_boq,
                    'item_ids' => $sorted->pluck('id_boq')->map(fn ($id) => (int) $id)->values()->all(),
                    'designator' => $representative->designator,
                    'item_name' => $representative->item_name,
                    'unit' => $representative->unit,
                    'pair_code' => $representativeDesignator?->pair_code,
                    'paired_designators' => $sorted->pluck('designator')->filter()->values()->all(),
                    'quantity_plan' => $plannedItem->quantity_plan,
                    'quantity_actual' => $actualItem->quantity_actual,
                    'is_additional' => $group->every(fn (BoqItem $item) => $item->quantity_plan === null),
                ];
            })
            ->sortBy('designator', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Pilihan tambahan juga dideduplikasi per pair_code. Material dipakai
     * sebagai wakil bila pasangan Material dan Jasa sama-sama tersedia.
     */
    public function groupDesignatorOptions(Collection $designators, Collection $existingGroups): Collection
    {
        $existingKeys = $existingGroups->pluck('key')->flip();

        return $designators
            ->groupBy(fn (Designator $designator) => $this->designatorGroupKey($designator))
            ->reject(fn (Collection $group, string $key) => $existingKeys->has($key))
            ->map(function (Collection $group, string $key): array {
                /** @var Designator $representative */
                $representative = $group
                    ->sortBy(fn (Designator $designator) => $this->rank($designator->type, $designator->designator))
                    ->first();

                return [
                    'key' => $key,
                    'designator_id' => $representative->id_designator,
                    'designator' => $representative->designator,
                    'item_name' => $representative->item_name,
                    'unit' => $representative->unit,
                    'pair_code' => $representative->pair_code,
                ];
            })
            ->sortBy('designator', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public function matchingDesignators(Designator $selected): Collection
    {
        if (! $this->usesPairCode($selected->type, $selected->designator, $selected->pair_code)) {
            return collect([$selected]);
        }

        $matches = Designator::query()
            ->when(
                $selected->customer_id,
                fn ($query) => $query->where('customer_id', $selected->customer_id),
                fn ($query) => $query->whereNull('customer_id')
            )
            ->where('pair_code', $selected->pair_code)
            ->get()
            ->filter(fn (Designator $designator) => $this->isMaterialOrService($designator->type, $designator->designator))
            ->whenEmpty(fn (Collection $collection) => $collection->push($selected))
            ->groupBy(fn (Designator $designator) => $this->isMaterial($designator->type, $designator->designator) ? 'material' : 'jasa')
            ->map(fn (Collection $group) => $group->firstWhere('id_designator', $selected->id_designator) ?? $group->first())
            ->values();

        return $matches;
    }

    public function boqGroupKey(BoqItem $item): string
    {
        $designator = $this->designatorFor($item);
        $pairCode = trim((string) ($designator?->pair_code ?? ''));

        if ($this->usesPairCode($designator?->type, $item->designator, $pairCode)) {
            return 'pair:'.mb_strtoupper($pairCode);
        }

        return 'boq:'.$item->id_boq;
    }

    private function designatorGroupKey(Designator $designator): string
    {
        $pairCode = trim((string) ($designator->pair_code ?? ''));

        if ($this->usesPairCode($designator->type, $designator->designator, $pairCode)) {
            return 'pair:'.mb_strtoupper($pairCode);
        }

        return 'designator:'.$designator->id_designator;
    }

    private function designatorFor(BoqItem $item): ?Designator
    {
        return $item->designatorData ?? $item->designatorDataByCode;
    }

    private function usesPairCode(?string $type, ?string $code, ?string $pairCode): bool
    {
        return trim((string) $pairCode) !== '' && $this->isMaterialOrService($type, $code);
    }

    private function isMaterialOrService(?string $type, ?string $code): bool
    {
        $normalizedType = mb_strtolower(trim((string) $type));
        $normalizedCode = mb_strtoupper(trim((string) $code));

        return in_array($normalizedType, ['material', 'jasa'], true)
            || str_starts_with($normalizedCode, 'M-')
            || str_starts_with($normalizedCode, 'J-');
    }

    private function isMaterial(?string $type, ?string $code): bool
    {
        return mb_strtolower(trim((string) $type)) === 'material'
            || str_starts_with(mb_strtoupper(trim((string) $code)), 'M-');
    }

    private function rank(?string $type, ?string $code): int
    {
        if ($this->isMaterial($type, $code)) {
            return 0;
        }

        return mb_strtolower(trim((string) $type)) === 'jasa'
            || str_starts_with(mb_strtoupper(trim((string) $code)), 'J-')
                ? 1
                : 2;
    }
}
