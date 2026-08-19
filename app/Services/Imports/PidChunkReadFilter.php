<?php

namespace App\Services\Imports;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class PidChunkReadFilter implements IReadFilter
{
    private int $startRow = 2;
    private int $endRow = 2;

    public function setRows(int $startRow, int $chunkSize): void
    {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize - 1;
    }

    public function readCell(
        string $columnAddress,
        int $row,
        string $worksheetName = ''
    ): bool {
        return $row === 1
            || ($row >= $this->startRow && $row <= $this->endRow);
    }
}