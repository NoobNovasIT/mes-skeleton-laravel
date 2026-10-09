# MES Skeleton - Laravel

Bộ khung (skeleton) hệ thống **MES (Manufacturing Execution System)** viết bằng **PHP 8.4 / Laravel 13 / SQL Server**,
port từ bản gốc **ASP.NET Core 10 (N-Tier)** do tôi tự xây dựng. Hai bản dùng **chung một database** và **chung URL**,
nên có thể chạy song song và đối chiếu từng tầng.

> *A reusable MES skeleton (auth, role × menu permissions, dynamic menu tree, audit log, i18n) ported from my own
> ASP.NET Core 10 N-Tier solution to Laravel 13, sharing the same SQL Server schema.*

| | |
|---|---|
| Ngôn ngữ / Framework | PHP 8.4, Laravel 13, Blade |
| Database | SQL Server (`pdo_sqlsrv`), schema quản lý bằng SQL script, Stored Procedure |
| Giao diện | Bootstrap 5, jQuery, DataTables, SweetAlert2, Font Awesome, SortableJS |
| Quy mô | 85 file PHP, 34 Blade view, 24 SQL script (~7.000 dòng) |

---

## Tính năng

- **Đăng nhập** bằng UserName hoặc Email. Mật khẩu BCrypt tương thích với bản C#. Khóa tạm khi sai nhiều lần, chống dò tài khoản theo thời gian phản hồi, giới hạn 10 request/phút.
- **Kiểm tra phiên ở mọi request** bằng `SecurityStamp`: Admin khóa tài khoản hoặc reset mật khẩu thì user bị đăng xuất ngay ở request kế tiếp.
- **Phân quyền Role × Menu 3 mức** (NoAccess / ReadOnly / FullAccess), có override riêng cho từng user.
  Server tự chặn theo HTTP method: GET cần ReadOnly, POST cần FullAccess. Ẩn nút trên giao diện chỉ là trải nghiệm, server luôn kiểm tra lại.
- **Menu động dạng cây** (Module → Group → Page) dựng phía server theo quyền. Trang quản trị TreeView kéo-thả, đổi thứ tự, kiểm tra vòng lặp cha-con.
- **Audit Log tự động** cho mọi thao tác thêm / sửa / xóa (giá trị cũ / mới dạng JSON, che cột nhạy cảm), cùng các sự kiện Login, PermissionChange...
- **Xóa mềm** (`IsDeleted`) áp dụng tự động cho mọi truy vấn.
- **Thao tác trong dialog không chuyển trang:** chỉ cần thêm thuộc tính `data-mes-*` vào HTML, không phải viết JavaScript riêng cho từng trang.
- **Đa ngôn ngữ EN / VI.** Khóa dịch là câu tiếng Anh, tên menu dịch trong database.
- **Cấu hình thương hiệu** (tên, màu, logo, video nền trang đăng nhập) chỉnh trên giao diện, không sửa code.

### Màn hình

| Module | Page | URL |
|---|---|---|
| System | User Accounts: duyệt, gán role, khóa, reset mật khẩu, permission override | `/System/UserAccount` |
| | Roles | `/System/Role` |
| | Role Permissions (cây quyền) | `/System/RolePermission` |
| | Menus (TreeView kéo-thả) | `/System/Menu` |
| | System Settings | `/System/SystemSetting` |
| | Audit Log (lọc, phân trang bằng Stored Procedure) | `/System/AuditLog` |
| Warehouse | Receive Material: CRUD mẫu theo đúng checklist thêm page | `/Warehouse/MaterialReceipt` |

---

## Kiến trúc

```
app/
├─ Support/            = Mes.Common      Constants (MenuCodes...), Enums, OperationResult, PagedResult
├─ Models/             = Mes.Data        Eloquent model + Concerns: Auditable, HasSoftDeleteFlag, HasCompositeKey
├─ Services/           = Mes.Service     Nghiệp vụ: Accounts, Permissions, Menus, Roles, UserAccounts, ...
│   └─ Database/StoredProcedureExecutor   = Dapper
├─ Http/
│   ├─ Controllers/    = Mes.Web Areas   System/*, Warehouse/*, MesController (base)
│   ├─ Middleware/                       RequireMenuPermission, ValidateUserSession, MustChangePassword, SetLanguage
│   ├─ ViewModels/                       Form + rule validate
│   └─ Navigation/                       Module / Page đang mở, breadcrumb
└─ Console/Commands/                     mes:run-sql, mes:create-admin
resources/views/       Blade: layouts (app / dialog / auth), partials, system/*, warehouse/*
routes/web.php         Mỗi Page = 1 nhóm route gắn middleware('mes.permission:{MenuCode}')
database/MASTERDATA_SQL/   Script SQL là gốc của schema (bảng, SP, seed) - không dùng migrations
lang/vi.json           Bản dịch tiếng Việt
public/js, public/css  JS / CSS dùng chung (dialog, AJAX form, treeview)
```

Một request `GET /System/Role` đi qua các bước:

```
web (session, CSRF, ngôn ngữ) → auth → mes.session (SecurityStamp) → mes.must-change-password
 → mes.permission:SYSTEM_ROLE (SP tính quyền hiệu lực, có cache) → RoleController → RoleService → Eloquent → SQL Server
 → Blade view trong layout (menu dựng theo quyền)
```

Bảng đối chiếu chi tiết từng thành phần ASP.NET ↔ Laravel: **[MES-LARAVEL.md](MES-LARAVEL.md)**.

### Một số quyết định kỹ thuật

| Vấn đề | Cách giải quyết |
|---|---|
| Schema dùng chung với bản .NET, cột bit `IsDeleted` thay vì `deleted_at` | Trait `HasSoftDeleteFlag`: global scope + `delete()` thành UPDATE |
| EF Core Interceptor ghi AuditLog | Trait `Auditable` dùng Eloquent Model Events, ghi trong cùng transaction |
| Eloquent không hỗ trợ khóa chính ghép | Trait `HasCompositeKey` (RoleMenuPermission, UserAccountRole...) |
| PHP không giữ bộ nhớ giữa các request | Cache quyền / menu theo user bằng "version key", xóa cache tức thì khi đổi quyền |
| Hash BCrypt dùng chung với BCrypt.Net | Kiểm tra `$2a$` bằng `password_verify`, tạo hash mới với tiền tố `$2a$` |
| Dialog cần trả lại HTML form khi lỗi (không phải 422 JSON) | ViewModel tự validate, controller trả View kèm lỗi |
| Unserialize object từ cache (Laravel 13 chặn mặc định) | Khai báo đúng danh sách DTO được phép trong `config/cache.php` |

---

## Cài đặt

Cần có PHP 8.3+ kèm extension `pdo_sqlsrv` ([Microsoft Drivers for PHP for SQL Server](https://github.com/microsoft/msphpsql)), ODBC Driver 18 for SQL Server, Composer và SQL Server 2019+.

```bash
git clone <repo-url> php-mes-demo && cd php-mes-demo
composer install
cp .env.example .env            # điền DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD
php artisan key:generate

php artisan mes:run-sql database/MASTERDATA_SQL          # tạo bảng, Stored Procedure, dữ liệu mặc định
php artisan mes:create-admin admin admin@example.com     # admin đầu tiên (bắt đổi mật khẩu lần đầu)

php artisan serve                                        # http://127.0.0.1:8000
```

> Không chạy `php artisan migrate` trên database MES: schema được quản lý bằng script SQL.
> Trang học `/work-orders` (CRUD đầu tiên) chạy trên SQLite: `php artisan migrate --database=sqlite`.

---

## Thêm một Page mới

1. Tạo menu trên **System > Menus** (URL `/{Module}/{Controller}`), thêm hằng vào `MenuCodes.php`.
2. Viết script bảng trong `database/MASTERDATA_SQL`, chạy `php artisan mes:run-sql <file>`.
3. Model (`use Auditable, HasSoftDeleteFlag`) → Service → ViewModel → Controller.
4. Nhóm route có `->middleware('mes.permission:'.MenuCodes::X)`.
5. View `index.blade.php` + `save.blade.php`, câu mới thêm vào `lang/vi.json`.

Không cần đăng ký DI. Trang mẫu để copy: `Warehouse/MaterialReceipt`.

## Lộ trình

- [ ] Đăng ký / xác thực email / quên mật khẩu (Laravel Mail)
- [ ] Thông báo realtime (Laravel Reverb)
- [ ] REST API cho thiết bị (Laravel Sanctum)
- [ ] Module giám sát nhiệt độ realtime: ESP32 → MQTT (Mosquitto) → dashboard
- [ ] Hỗ trợ Oracle (`yajra/laravel-oci8`)
