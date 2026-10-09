<?php

namespace App\Support\Constants;

/**
 * = Mes.Common.Constants.MenuCodes. Sợi dây duy nhất nối bảng Menu với middleware "mes.permission".
 * Sai một ký tự = page luôn bị Access Denied.
 */
final class MenuCodes
{
    // SYSTEM
    public const System = 'SYSTEM';
    public const SystemSecurity = 'SYSTEM_SECURITY';
    public const SystemUserAccount = 'SYSTEM_USER_ACCOUNT';
    public const SystemRole = 'SYSTEM_ROLE';
    public const SystemRolePermission = 'SYSTEM_ROLE_PERMISSION';
    public const SystemConfiguration = 'SYSTEM_CONFIGURATION';
    public const SystemMenu = 'SYSTEM_MENU';
    public const SystemSetting = 'SYSTEM_SETTING';
    public const SystemMonitoring = 'SYSTEM_MONITORING';
    public const SystemAuditLog = 'SYSTEM_AUDIT_LOG';

    // WAREHOUSE
    public const Warehouse = 'WAREHOUSE';
    public const WarehouseRaw = 'WAREHOUSE_RAW';
    public const WarehouseRawReceive = 'WAREHOUSE_RAW_RECEIVE';
}
