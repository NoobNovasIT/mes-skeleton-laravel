# MES skeleton - bản Laravel (đối chiếu với `D:\Apps-Projects\MES`)

Port từ solution ASP.NET `Mes.slnx`, **dùng chung database MASTERDATA (SQL Server)**,
URL giữ nguyên (`/System/Role`, `/Warehouse/MaterialReceipt`...) nên bảng `Menu` dùng chung được cho cả hai bản.

## 1. Chạy

```powershell
cd D:\php-mes-demo
php artisan serve          # http://127.0.0.1:8000 - đăng nhập bằng tài khoản trong bảng UserAccount
```

- PHP dùng bản `C:\php84` (có driver `pdo_sqlsrv`). Bản herd-lite cũ **không** kết nối được SQL Server.
- Kết nối DB nằm trong `.env` (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) - không commit file này.
- Schema do **script SQL** quản lý, không dùng Laravel migrations:
  `php artisan mes:run-sql database/MASTERDATA_SQL/013_Create_Table_MaterialReceipt.sql`
- ⚠ **Không chạy `php artisan migrate`** (sẽ tạo bảng `users`, `cache`, `jobs`... của Laravel vào MASTERDATA).
  Trang học `/work-orders` dùng SQLite: `php artisan migrate --database=sqlite`.

## 2. N-Tier: ASP.NET → Laravel

```
Mes.Web ──► Mes.Service ──► Mes.Repository ──► Mes.Data ──► Mes.Common
   │             │                 │                │            │
app/Http   app/Services      (Eloquent query)   app/Models   app/Support
```

| ASP.NET | Laravel | Ghi chú |
|---|---|---|
| `Mes.Common/Constants`, `Enums`, `Results` | `app/Support/Constants`, `Enums`, `Results` | enum C# → PHP backed enum |
| `Mes.Data/Entities` + `Configurations` | `app/Models/*.php` (Eloquent) | tên bảng / cột / cast khai báo trong model |
| `MesDbContext` Global Query Filter (IsDeleted) | `Models/Concerns/HasSoftDeleteFlag` | `delete()` → `UPDATE IsDeleted = 1` |
| `AuditSaveChangesInterceptor` | `Models/Concerns/Auditable` (Model Events) | tự điền Created/Updated, ghi AuditLog |
| Khóa ghép `HasKey(x => new {...})` | `Models/Concerns/HasCompositeKey` | Eloquent không hỗ trợ sẵn |
| `Mes.Repository` + `IUnitOfWork` | Eloquent query trong Service + `DB::transaction()` | Laravel thường không tách Repository |
| `StoredProcedureExecutor` (Dapper) | `app/Services/Database/StoredProcedureExecutor` | `EXEC dbo.usp_... @A = ?` |
| `Mes.Service/{Feature}/*Service.cs` | `app/Services/{Feature}/*Service.php` | logic giữ nguyên thứ tự |
| FluentValidation (Service) | kiểm tra trong Service (regex, độ dài) | |
| `IUserScopedCache` (MemoryCache) | `Services/Caching/UserScopedCache` (file cache) | PHP không giữ RAM giữa các request |
| `services.AddScoped<IX, X>()` | **không cần** - Laravel tự khởi tạo qua constructor | chỉ singleton / scoped đặc biệt khai trong `AppServiceProvider` |
| `Areas/{Module}/Controllers` | `app/Http/Controllers/{Module}` | |
| `MesControllerBase.OperationResponse` | `app/Http/Controllers/MesController::operationResponse` | JSON cho dialog, flash + redirect cho trang thường |
| ViewModel + DataAnnotations | `app/Http/ViewModels/*` (`rules()`, `displayNames()`) | |
| `[RequireMenuPermission(MenuCodes.X)]` | `->middleware('mes.permission:'.MenuCodes::X)` trong `routes/web.php` | GET cần ReadOnly, POST cần FullAccess |
| `[AllowReadOnlySubmit]` | `->middleware('mes.permission:X,ReadOnly')` | |
| `CookieSessionValidator` | `app/Http/Middleware/ValidateUserSession` | đối chiếu SecurityStamp mỗi request |
| `MustChangePasswordFilter` | `app/Http/Middleware/MustChangePassword` | |
| `PageNavigationContextProvider` + ViewComponent | `app/Http/Navigation/*` + `partials/sidebar-menu`, `top-module-menu`, `page-header` | |
| TagHelper `mes-require-access="FullAccess"` | `@requireAccess('FullAccess') ... @endrequireAccess` | chỉ ẩn giao diện, server vẫn chặn |
| `Context.GetPagePermission().CanWrite` | `PagePermission::current()->canWrite()` | |
| `Request.IsDialogRequest()` | `request()->isDialogRequest()` | macro trong `AppServiceProvider` |
| `_ViewStart.cshtml` chọn layout | `@extends(mes_layout())` | `layouts.app` / `layouts.dialog` |
| `ViewData["Title"]`, `ViewData["DialogSize"]` | biến `$title`, `@section('dialogSize', 'md')` | |
| `@section Scripts` | `@push('scripts')` | dialog bỏ qua |
| `SharedResource.vi.resx` | `lang/vi.json` | khóa = câu tiếng Anh, dùng `localize('...', $arg0)` |
| `TempData` toast | `session()->flash()` | `partials/notification-scripts` |
| Antiforgery `RequestVerificationToken` | `@csrf` + header `X-CSRF-TOKEN` | đã sửa trong `public/js/mes-core.js`, `mes-ui.js` |
| `wwwroot/` | `public/` | CSS / JS / lib copy nguyên |
| `appsettings.json` | `.env` + `config/mes.php` | |
| `MASTERDATA_SQL/RunAllScripts.ps1` | `php artisan mes:run-sql {file}` | `app/Console/Commands/RunSqlScript.php` |

## 3. Một request đi qua những đâu

```
GET /System/Role
 → public/index.php → bootstrap/app.php
 → middleware nhóm web: cookie, session, CSRF, SetLanguage (đọc cookie mes_language)
 → auth (chưa đăng nhập → /Account/Login)
 → mes.session (SecurityStamp, trạng thái tài khoản)
 → mes.must-change-password
 → mes.permission:SYSTEM_ROLE → PermissionService::getAccessLevel (SP usp_Security_Menu_GetNavigationByUserAccountId, có cache)
 → RoleController@index → RoleService::list() → Eloquent → SQL Server
 → resources/views/system/role/index.blade.php trong layouts/app (menu dựng theo quyền)
```

## 4. Thêm một Page mới (ví dụ Production > Line)

1. Menu trên **System > Menus** (URL `/Production/ProductionLine`) hoặc script `9xx_Seed_Menu_*.sql`.
2. `app/Support/Constants/MenuCodes.php` - thêm hằng.
3. Script bảng `database/MASTERDATA_SQL/0xx_Create_Table_ProductionLine.sql` → `php artisan mes:run-sql ...`.
4. `app/Models/ProductionLine.php` - `use Auditable, HasSoftDeleteFlag`, `$table`, `casts()`.
5. `app/Services/ProductionLines/ProductionLineService.php` (+ Dto).
6. `app/Http/ViewModels/Production/ProductionLineSaveViewModel.php`.
7. `app/Http/Controllers/Production/ProductionLineController.php` - copy `Warehouse/MaterialReceiptController`.
8. `routes/web.php` - nhóm route có `->middleware('mes.permission:'.MenuCodes::ProductionLine)`.
9. `resources/views/production/production-line/index.blade.php` + `save.blade.php`.
10. Câu tiếng Việt mới → `lang/vi.json`.

Không phải đăng ký DI, không có Area / `_ViewImports`.

## 5. Chưa port

| Tính năng | Lý do |
|---|---|
| Đăng ký, xác thực email, quên mật khẩu | cần cấu hình gửi mail (Laravel Mail) |
| Thông báo realtime (chuông, SignalR) | Laravel dùng Reverb / Pusher, cần cài thêm |
| Mes.API (JWT) | Laravel dùng Sanctum, làm sau |
| "Remember me" | bảng UserAccount không có cột `remember_token` |
| Client-side validation (jQuery unobtrusive) | validate ở server, lỗi hiện trong dialog |
