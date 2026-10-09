<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\MesController;
use App\Http\Dialogs\ClientAfterSuccess;
use App\Http\ViewModels\Warehouse\MaterialReceiptSaveViewModel;
use App\Services\MaterialReceipts\MaterialReceiptService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * = Areas/Warehouse/Controllers/MaterialReceiptController.cs (Warehouse > Raw Warehouse > Receive Material).
 * Danh sách + thêm / sửa / xóa trong dialog. Quyền: ->middleware('mes.permission:WAREHOUSE_RAW_RECEIVE').
 */
final class MaterialReceiptController extends MesController
{
    private const SaveViewName = 'warehouse.material-receipt.save';

    public function __construct(
        private readonly MaterialReceiptService $materialReceiptService,
    ) {
    }

    public function index(): mixed                                   // GET -> middleware tự yêu cầu ReadOnly
    {
        return view('warehouse.material-receipt.index', ['materialReceipts' => $this->materialReceiptService->list()]);
    }

    public function create(): mixed
    {
        return view(self::SaveViewName, [
            'materialReceiptSaveViewModel' => MaterialReceiptSaveViewModel::forCreate(),
            'title' => localize('Create receipt'),                      // tiêu đề của dialog
        ]);
    }

    public function store(Request $request): mixed                   // POST -> middleware tự yêu cầu FullAccess
    {
        $materialReceiptSaveViewModel = MaterialReceiptSaveViewModel::fromInput($request->all());
        $saveView = view(self::SaveViewName, ['materialReceiptSaveViewModel' => $materialReceiptSaveViewModel, 'title' => localize('Create receipt')]);

        $validator = MaterialReceiptSaveViewModel::validator($request->all());
        if ($validator->fails()) {
            return $this->viewWithValidationErrors($saveView, $validator);      // trả HTML -> dialog hiện lỗi tại chỗ
        }

        $createResult = $this->materialReceiptService->create($materialReceiptSaveViewModel->toRequest());
        if ($createResult->isFailure()) {
            return $this->viewWithOperationError($saveView, $createResult);
        }

        return $this->operationResponse($createResult, 'Saved successfully.', route('warehouse.material-receipt.index'));
    }

    public function edit(int $id): mixed
    {
        $materialReceipt = $this->materialReceiptService->get($id);
        abort_if($materialReceipt === null, 404);

        return view(self::SaveViewName, [
            'materialReceiptSaveViewModel' => MaterialReceiptSaveViewModel::fromDto($materialReceipt),
            'title' => localize('Edit receipt'),
        ]);
    }

    public function update(Request $request, int $id): mixed
    {
        $materialReceiptSaveViewModel = MaterialReceiptSaveViewModel::fromInput($request->all(), $id);
        $saveView = view(self::SaveViewName, ['materialReceiptSaveViewModel' => $materialReceiptSaveViewModel, 'title' => localize('Edit receipt')]);

        $validator = MaterialReceiptSaveViewModel::validator($request->all());
        if ($validator->fails()) {
            return $this->viewWithValidationErrors($saveView, $validator);
        }

        $updateResult = $this->materialReceiptService->update($id, $materialReceiptSaveViewModel->toRequest());
        if ($updateResult->isFailure()) {
            return $this->viewWithOperationError($saveView, $updateResult);
        }

        return $this->operationResponse($updateResult, 'Saved successfully.', route('warehouse.material-receipt.index'));
    }

    public function destroy(int $id): Response
    {
        $deleteResult = $this->materialReceiptService->delete($id);

        // None: form xóa nằm trên trang (không phải trong dialog) -> chỉ toast + tải lại vùng danh sách.
        return $this->operationResponse($deleteResult, 'Deleted successfully.', route('warehouse.material-receipt.index'), ClientAfterSuccess::None);
    }
}
