<?php

namespace App\Services\Imports;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class BoqChunkReadFilter implements IReadFilter
{
    public function __construct(
        private int $startRow,
        private int $endRow,
    ) {
    }

    public function readCell(
        string $columnAddress,
        int $row,
        string $worksheetName = ''
    ): bool {
        if ($row === 1) {
            return true;
        }

        return $row >= $this->startRow
            && $row <= $this->endRow;
    }
}