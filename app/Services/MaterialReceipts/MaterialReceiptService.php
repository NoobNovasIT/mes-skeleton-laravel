<?php

namespace App\Services\MaterialReceipts;

use App\Models\MaterialReceipt;
use App\Support\Constants\ErrorMessageKeys;
use App\Support\Results\OperationResult;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * = Mes.Service/MaterialReceipts/MaterialReceiptService.cs, nhưng lưu bảng thật dbo.MaterialReceipt.
 * Thứ tự: chuẩn hóa -> kiểm tra trùng mã -> ghi -> commit. Validate định dạng đã làm ở MaterialReceiptSaveForm (tầng Web).
 */
final class MaterialReceiptService
{
    /** @return list<MaterialReceiptDto> */
    public function list(): array
    {
        return MaterialReceipt::query()
            ->orderByDesc('ReceivedAt')
            ->get()
            ->map(fn (MaterialReceipt $materialReceipt) => MaterialReceiptDto::fromModel($materialReceipt))
            ->all();
    }

    public function get(int $materialReceiptId): ?MaterialReceiptDto
    {
        $materialReceipt = MaterialReceipt::query()->find($materialReceiptId);

        return $materialReceipt === null ? null : MaterialReceiptDto::fromModel($materialReceipt);
    }

    /** @param array{ReceiptCode: string, MaterialCode: string, MaterialName: string, ReceivedQuantity: string, ReceivedAt: CarbonImmutable, Note: ?string} $request */
    public function create(array $request): OperationResult
    {
        $normalizedRequest = $this->normalize($request);
        if ($this->isReceiptCodeTaken($normalizedRequest['ReceiptCode'], null)) {
            return OperationResult::failure(ErrorMessageKeys::ReceiptCodeAlreadyTaken);
        }

        $materialReceipt = DB::transaction(fn () => MaterialReceipt::query()->create($normalizedRequest));

        return OperationResult::success($materialReceipt->Id);
    }

    public function update(int $materialReceiptId, array $request): OperationResult
    {
        $materialReceipt = MaterialReceipt::query()->find($materialReceiptId);
        if ($materialReceipt === null) {
            return OperationResult::failure(ErrorMessageKeys::RecordNotFound);
        }

        $normalizedRequest = $this->normalize($request);
        if ($this->isReceiptCodeTaken($normalizedRequest['ReceiptCode'], $materialReceiptId)) {
            return OperationResult::failure(ErrorMessageKeys::ReceiptCodeAlreadyTaken);
        }

        $materialReceipt->fill($normalizedRequest);
        DB::transaction(fn () => $materialReceipt->save());

        return OperationResult::success();
    }

    public function delete(int $materialReceiptId): OperationResult
    {
        $materialReceipt = MaterialReceipt::query()->find($materialReceiptId);
        if ($materialReceipt === null) {
            return OperationResult::failure(ErrorMessageKeys::RecordNotFound);
        }

        DB::transaction(fn () => $materialReceipt->delete());   // xóa mềm: IsDeleted = 1

        return OperationResult::success();
    }

    private function normalize(array $request): array
    {
        $note = trim((string) ($request['Note'] ?? ''));

        return [
            'ReceiptCode' => strtoupper(trim($request['ReceiptCode'])),
            'MaterialCode' => strtoupper(trim($request['MaterialCode'])),
            'MaterialName' => trim($request['MaterialName']),
            'ReceivedQuantity' => $request['ReceivedQuantity'],
            'ReceivedAt' => $request['ReceivedAt'],
            'Note' => $note === '' ? null : $note,
        ];
    }

    private function isReceiptCodeTaken(string $receiptCode, ?int $excludedMaterialReceiptId): bool
    {
        return MaterialReceipt::query()
            ->where('ReceiptCode', $receiptCode)
            ->when($excludedMaterialReceiptId !== null, fn ($query) => $query->where('Id', '!=', $excludedMaterialReceiptId))
            ->exists();
    }
}
