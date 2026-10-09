<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\MesController;
use App\Http\Dialogs\ClientAfterSuccess;
use App\Services\SystemSettings\SystemSettingService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** = Areas/System/Controllers/SystemSettingController.cs (System > System Settings). */
final class SystemSettingController extends MesController
{
    public function __construct(
        private readonly SystemSettingService $systemSettingService,
    ) {
    }

    public function index(): mixed
    {
        return view('system.system-setting.index', ['systemSettings' => $this->systemSettingService->list()]);
    }

    public function save(Request $request): Response
    {
        $updateResult = $this->systemSettingService->update((array) $request->input('settingValues', []));

        // Tên, màu, logo hiển thị trên Layout -> tải lại cả trang để thấy thay đổi.
        return $this->operationResponse($updateResult, 'Saved successfully.', route('system.system-setting.index'), ClientAfterSuccess::ReloadPage);
    }
}
