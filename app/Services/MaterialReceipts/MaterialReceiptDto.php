<?php

namespace App\Services\MaterialReceipts;

use App\Models\MaterialReceipt;
use Carbon\CarbonInterface;

final class MaterialReceiptDto
{
    public function __construct(
        public readonly int $materialReceiptId,
        public readonly string $receiptCode,
        public readonly string $materialCode,
        public readonly string $materialName,
        public readonly string $receivedQuantity,
        public readonly CarbonInterface $receivedAt,
        public readonly ?string $note,
    ) {
    }

    public static function fromModel(MaterialReceipt $materialReceipt): self
    {
        return new self(
            $materialReceipt->Id,
            $materialReceipt->ReceiptCode,
            $materialReceipt->MaterialCode,
            $materialReceipt->MaterialName,
            (string) $materialReceipt->ReceivedQuantity,
            $materialReceipt->ReceivedAt,
            $materialReceipt->Note,
        );
    }
}
