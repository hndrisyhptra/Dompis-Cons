<?php

namespace App\Services;

use App\Models\BoqItem;
use App\Models\Designator;
use App\Models\DesignatorPackagePrice;
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
                    // Volume hasil BOQ Survey -- KOLOM TERPISAH dari
                    // quantity_actual (lihat ANALISA_REFACTOR_PERSIAPAN.md
                    // bag. AB: quantity_actual murni utk progress Instalasi
                    // Step 3, tidak boleh lagi dipakai utk Survey).
                    'quantity_survey' => $actualItem->quantity_survey,
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

    /**
     * Stage 4e -- hitung total nilai nominal (Rupiah) BOQ Plan vs BOQ Survey
     * utk 1 LOP, dipakai WaspangController::finishSurvey() sbg dasar gate
     * "Approval Redesign" (deviasi >10%). Harga diambil dari
     * designator_package_prices per (designator_id, package LOP) -- BUKAN
     * dari boq_items (kolom unit_price/total_price di sana memang tidak
     * fillable/tidak pernah diisi, lihat catatan lama di dokumen audit).
     *
     * PENTING soal volume: 1 group (hasil groupBoqItems(), 1 pair_code =
     * 1 baris) tetap cuma dipakai SATU volume (tidak dijumlahkan Material+
     * Jasa) -- tapi nominalnya SENGAJA menjumlahkan harga Material DAN Jasa
     * dalam pasangan itu, karena keduanya komponen biaya yang beda (harga
     * satuan material != harga satuan jasa pasang), walau volumenya sama.
     *
     * @param  Collection<int, array<string, mixed>>  $groups  hasil groupBoqItems()
     * @param  array<string, mixed>  $submittedVolumes  keyed by representative_id (string)
     * @param  Collection<int, BoqItem>  $boqItems  koleksi BoqItem mentah (sumber designator_id per item)
     * @return array{plan_total: float, survey_total: float, missing: list<string>}
     *         `missing` berisi kode designator yg harganya tidak ditemukan di
     *         package ini; kalau LOP belum punya package sama sekali, isinya
     *         cuma 1 penanda '__no_package__'.
     */
    public function evaluateNominalDeviation(Collection $groups, array $submittedVolumes, Collection $boqItems, ?int $packageId): array
    {
        if ($packageId === null) {
            return ['plan_total' => 0.0, 'survey_total' => 0.0, 'missing' => ['__no_package__']];
        }

        $priceByDesignatorId = DesignatorPackagePrice::where('package_id', $packageId)
            ->pluck('price', 'designator_id');

        $itemsById = $boqItems->keyBy('id_boq');

        $planTotal = 0.0;
        $surveyTotal = 0.0;
        $missing = [];

        foreach ($groups as $group) {
            $surveyVolume = (float) ($submittedVolumes[(string) $group['representative_id']] ?? 0);
            $planVolume = (float) ($group['quantity_plan'] ?? 0);

            foreach ($group['item_ids'] as $itemId) {
                $item = $itemsById->get($itemId);
                $designatorId = $item?->designator_id;

                if ($designatorId === null) {
                    continue;
                }

                if (! $priceByDesignatorId->has($designatorId)) {
                    $missing[] = $item->designator ?? (string) $designatorId;

                    continue;
                }

                $price = (float) $priceByDesignatorId->get($designatorId);
                $planTotal += $planVolume * $price;
                $surveyTotal += $surveyVolume * $price;
            }
        }

        return [
            'plan_total' => $planTotal,
            'survey_total' => $surveyTotal,
            'missing' => array_values(array_unique($missing)),
        ];
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
