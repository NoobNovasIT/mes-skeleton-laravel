<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\MesController;
use App\Http\Dialogs\ClientAfterSuccess;
use App\Http\ViewModels\System\RoleSaveViewModel;
use App\Services\Roles\RoleService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * = Areas/System/Controllers/RoleController.cs (System > Roles).
 * Quyền gắn ở routes/web.php: ->middleware('mes.permission:SYSTEM_ROLE')
 *   GET  index / create / edit  -> cần ReadOnly
 *   POST store / update / delete -> tự động cần FullAccess
 */
final class RoleController extends MesController
{
    private const SaveViewName = 'system.role.save';

    public function __construct(
        private readonly RoleService $roleService,
    ) {
    }

    public function index(): mixed
    {
        return view('system.role.index', ['roles' => $this->roleService->list()]);
    }

    public function create(): mixed
    {
        return view(self::SaveViewName, ['roleSaveViewModel' => new RoleSaveViewModel(), 'title' => localize('Create role')]);
    }

    public function store(Request $request): mixed
    {
        $roleSaveViewModel = RoleSaveViewModel::fromInput($request->all());
        $saveView = view(self::SaveViewName, ['roleSaveViewModel' => $roleSaveViewModel, 'title' => localize('Create role')]);

        $validator = RoleSaveViewModel::validator($request->all());
        if ($validator->fails()) {
            return $this->viewWithValidationErrors($saveView, $validator);      // trả HTML -> dialog hiện lỗi
        }

        $createResult = $this->roleService->create($roleSaveViewModel->toRequest());
        if ($createResult->isFailure()) {
            return $this->viewWithOperationError($saveView, $createResult);
        }

        return $this->operationResponse($createResult, 'Saved successfully.', route('system.role.index'));
    }

    public function edit(int $id): mixed
    {
        $role = $this->roleService->get($id);
        abort_if($role === null, 404);

        return view(self::SaveViewName, ['roleSaveViewModel' => RoleSaveViewModel::fromModel($role), 'title' => localize('Edit role')]);
    }

    public function update(Request $request, int $id): mixed
    {
        $roleSaveViewModel = RoleSaveViewModel::fromInput($request->all(), $id);
        $roleSaveViewModel->IsSystemRole = (bool) $this->roleService->get($id)?->IsSystemRole;
        $saveView = view(self::SaveViewName, ['roleSaveViewModel' => $roleSaveViewModel, 'title' => localize('Edit role')]);

        $validator = RoleSaveViewModel::validator($request->all());
        if ($validator->fails()) {
            return $this->viewWithValidationErrors($saveView, $validator);
        }

        $updateResult = $this->roleService->update($id, $roleSaveViewModel->toRequest());
        if ($updateResult->isFailure()) {
            return $this->viewWithOperationError($saveView, $updateResult);
        }

        return $this->operationResponse($updateResult, 'Saved successfully.', route('system.role.index'));
    }

    public function destroy(int $id): Response
    {
        $deleteResult = $this->roleService->delete($id);

        return $this->operationResponse($deleteResult, 'Deleted successfully.', route('system.role.index'), ClientAfterSuccess::None);
    }
}
