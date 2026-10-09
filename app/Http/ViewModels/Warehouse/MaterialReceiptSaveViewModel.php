<?php

namespace App\Http\ViewModels\Warehouse;

use App\Http\ViewModels\FormViewModel;
use App\Services\MaterialReceipts\MaterialReceiptDto;
use Carbon\CarbonImmutable;

/**
 * = Areas/Warehouse/Models/MaterialReceiptViewModels.cs.
 * Thuộc tính đặt PascalCase giống C# để dễ đối chiếu; tên input trên form cũng là ReceiptCode, MaterialCode...
 */
final class MaterialReceiptSaveViewModel extends FormViewModel
{
    public int $MaterialReceiptId = 0;

    public string $ReceiptCode = '';

    public string $MaterialCode = '';

    public string $MaterialName = '';

    public string $ReceivedQuantity = '';

    public string $ReceivedAt = '';

    public ?string $Note = null;

    public function isCreateMode(): bool
    {
        return $this->MaterialReceiptId === 0;
    }

    public static function forCreate(): self
    {
        $materialReceiptSaveViewModel = new self();
        $materialReceiptSaveViewModel->ReceivedAt = now()->format('Y-m-d\TH:i');

        return $materialReceiptSaveViewModel;
    }

    public static function fromInput(array $input, int $materialReceiptId = 0): self
    {
        $materialReceiptSaveViewModel = new self();
        $materialReceiptSaveViewModel->MaterialReceiptId = $materialReceiptId;
        $materialReceiptSaveViewModel->ReceiptCode = (string) ($input['ReceiptCode'] ?? '');
        $materialReceiptSaveViewModel->MaterialCode = (string) ($input['MaterialCode'] ?? '');
        $materialReceiptSaveViewModel->MaterialName = (string) ($input['MaterialName'] ?? '');
        $materialReceiptSaveViewModel->ReceivedQuantity = (string) ($input['ReceivedQuantity'] ?? '');
        $materialReceiptSaveViewModel->ReceivedAt = (string) ($input['ReceivedAt'] ?? '');
        $materialReceiptSaveViewModel->Note = $input['Note'] ?? null;

        return $materialReceiptSaveViewModel;
    }

    public static function fromDto(MaterialReceiptDto $materialReceipt): self
    {
        $materialReceiptSaveViewModel = new self();
        $materialReceiptSaveViewModel->MaterialReceiptId = $materialReceipt->materialReceiptId;
        $materialReceiptSaveViewModel->ReceiptCode = $materialReceipt->receiptCode;
        $materialReceiptSaveViewModel->MaterialCode = $materialReceipt->materialCode;
        $materialReceiptSaveViewModel->MaterialName = $materialReceipt->materialName;
        $materialReceiptSaveViewModel->ReceivedQuantity = str_contains($materialReceipt->receivedQuantity, '.')
            ? rtrim(rtrim($materialReceipt->receivedQuantity, '0'), '.')
            : $materialReceipt->receivedQuantity;
        $materialReceiptSaveViewModel->ReceivedAt = $materialReceipt->receivedAt->format('Y-m-d\TH:i');
        $materialReceiptSaveViewModel->Note = $materialReceipt->note;

        return $materialReceiptSaveViewModel;
    }

    /** = SaveMaterialReceiptRequest gửi xuống Service. */
    public function toRequest(): array
    {
        return [
            'ReceiptCode' => $this->ReceiptCode,
            'MaterialCode' => $this->MaterialCode,
            'MaterialName' => $this->MaterialName,
            'ReceivedQuantity' => $this->ReceivedQuantity,
            'ReceivedAt' => CarbonImmutable::parse($this->ReceivedAt),
            'Note' => $this->Note,
        ];
    }

    protected static function rules(): array
    {
        return [
            'ReceiptCode' => ['required', 'string', 'max:30'],
            'MaterialCode' => ['required', 'string', 'max:50'],
            'MaterialName' => ['required', 'string', 'max:200'],
            'ReceivedQuantity' => ['required', 'numeric', 'between:0.001,1000000'],
            'ReceivedAt' => ['required', 'date'],
            'Note' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected static function displayNames(): array
    {
        return [
            'ReceiptCode' => 'Receipt code',
            'MaterialCode' => 'Material code',
            'MaterialName' => 'Material name',
            'ReceivedQuantity' => 'Received quantity',
            'ReceivedAt' => 'Received at',
            'Note' => 'Note',
        ];
    }
}
