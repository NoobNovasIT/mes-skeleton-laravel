<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\System\AuditLogController;
use App\Http\Controllers\System\MenuController;
use App\Http\Controllers\System\RoleController;
use App\Http\Controllers\System\RolePermissionController;
use App\Http\Controllers\System\SystemSettingController;
use App\Http\Controllers\System\UserAccountController;
use App\Http\Controllers\Warehouse\MaterialReceiptController;
use App\Http\Controllers\WorkOrderController;
use App\Support\Constants\MenuCodes;
use Illuminate\Support\Facades\Route;

/*
 |--------------------------------------------------------------------------
 | Bản ASP.NET định tuyến theo quy ước /{Area}/{Controller}/{Action}/{id?}.
 | Laravel khai báo từng route ở đây; URL giữ NGUYÊN như bản ASP.NET để bảng Menu dùng chung được:
 |     Menu.Url = /System/Role  ->  Route::get('/System/Role', ...)
 |
 | Mỗi Page = 1 nhóm route gắn middleware('mes.permission:{MenuCode}') (= [RequireMenuPermission]).
 |--------------------------------------------------------------------------
 */

// Trang học đầu tiên (SQLite, không cần đăng nhập).
Route::resource('work-orders', WorkOrderController::class)->only(['index', 'store', 'destroy']);

// ------------------------------------------------------------------ Chưa đăng nhập ([AllowAnonymous])
Route::controller(AccountController::class)->prefix('Account')->group(function () {
    Route::get('Login', 'showLogin')->name('account.login');
    Route::post('Login', 'login')->middleware('throttle:10,1');          // = rate limit 10 request/phút/IP
});
Route::post('/Home/SetLanguage', [HomeController::class, 'setLanguage'])->name('home.set-language');

// ------------------------------------------------------------------ Đã đăng nhập
Route::middleware(['auth', 'mes.session'])->group(function () {

    // Trang tài khoản: KHÔNG gắn mes.must-change-password (để còn vào được trang đổi mật khẩu).
    Route::controller(AccountController::class)->prefix('Account')->group(function () {
        Route::post('Logout', 'logout')->name('account.logout');
        Route::get('ChangePassword', 'showChangePassword')->name('account.change-password');
        Route::post('ChangePassword', 'changePassword');
        Route::get('Profile', 'showProfile')->name('account.profile');
        Route::post('Profile', 'profile');
    });

    Route::middleware('mes.must-change-password')->group(function () {

        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('/Home/AccessDenied', [HomeController::class, 'accessDenied'])->name('home.access-denied');

        // ============================================================== Module SYSTEM (Area "System")
        Route::prefix('System')->name('system.')->group(function () {

            Route::controller(UserAccountController::class)->prefix('UserAccount')->name('user-account.')
                ->middleware('mes.permission:'.MenuCodes::SystemUserAccount)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('Detail/{id}', 'detail')->name('detail');
                    Route::post('Update/{id}', 'update')->name('update');
                    Route::post('Approve/{id}', 'approve')->name('approve');
                    Route::post('ChangeStatus/{id}', 'changeStatus')->name('change-status');
                    Route::post('ResetPassword/{id}', 'resetPassword')->name('reset-password');
                    Route::post('Delete/{id}', 'destroy')->name('delete');
                    Route::get('Permissions/{id}', 'permissions')->name('permissions');
                    Route::post('Permissions/{id}', 'savePermissions')->name('save-permissions');
                });

            Route::controller(RoleController::class)->prefix('Role')->name('role.')
                ->middleware('mes.permission:'.MenuCodes::SystemRole)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('Create', 'create')->name('create');
                    Route::post('Create', 'store')->name('store');
                    Route::get('Edit/{id}', 'edit')->name('edit');
                    Route::post('Edit/{id}', 'update')->name('update');
                    Route::post('Delete/{id}', 'destroy')->name('delete');
                });

            Route::controller(RolePermissionController::class)->prefix('RolePermission')->name('role-permission.')
                ->middleware('mes.permission:'.MenuCodes::SystemRolePermission)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('Save/{roleId}', 'save')->name('save');
                });

            Route::controller(MenuController::class)->prefix('Menu')->name('menu.')
                ->middleware('mes.permission:'.MenuCodes::SystemMenu)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('Create', 'create')->name('create');
                    Route::post('Create', 'store')->name('store');
                    Route::get('Edit/{id}', 'edit')->name('edit');
                    Route::post('Edit/{id}', 'update')->name('update');
                    Route::post('Delete/{id}', 'destroy')->name('delete');
                    Route::post('SavePositions', 'savePositions')->name('save-positions');
                });

            Route::controller(SystemSettingController::class)->prefix('SystemSetting')->name('system-setting.')
                ->middleware('mes.permission:'.MenuCodes::SystemSetting)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('Save', 'save')->name('save');
                });

            Route::get('AuditLog', [AuditLogController::class, 'index'])->name('audit-log.index')
                ->middleware('mes.permission:'.MenuCodes::SystemAuditLog);
        });

        // ============================================================== Module WAREHOUSE (Area "Warehouse")
        Route::prefix('Warehouse')->name('warehouse.')->group(function () {

            Route::controller(MaterialReceiptController::class)->prefix('MaterialReceipt')->name('material-receipt.')
                ->middleware('mes.permission:'.MenuCodes::WarehouseRawReceive)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('Create', 'create')->name('create');
                    Route::post('Create', 'store')->name('store');
                    Route::get('Edit/{id}', 'edit')->name('edit');
                    Route::post('Edit/{id}', 'update')->name('update');
                    Route::post('Delete/{id}', 'destroy')->name('delete');
                });
        });
    });
});
