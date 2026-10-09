{{--
    = Areas/System/Views/Shared/_PermissionTreeNode.cshtml. Một node trên cây phân quyền (đệ quy).
    Chỉ Page có lựa chọn quyền; Module / Group chỉ để gom nhóm.
    Tham số: $node (PermissionTreeNodeDto), $isUserOverrideMode, $isReadOnly, $depth.
--}}
@use('App\Support\Enums\AccessLevel')
@use('App\Support\Enums\MenuType')
@php
    $isPage = $node->menuType === MenuType::Page;
    $hasChildren = $node->children !== [];
    $inputName = $isUserOverrideMode ? "overrideAccessLevels[{$node->menuId}]" : "accessLevels[{$node->menuId}]";
    $inputIdPrefix = "access_{$node->menuId}";
    $selectedAccessLevel = $isUserOverrideMode ? $node->overrideAccessLevel : $node->roleAccessLevel;
@endphp

<li class="mes-treeview-item" data-menu-id="{{ $node->menuId }}" data-menu-type="{{ $node->menuType->name }}" style="--mes-tree-depth: {{ $depth }}">
    <div class="mes-tree-row {{ $node->isActive ? '' : 'mes-tree-inactive' }}">
        @if ($hasChildren)
            <button type="button" class="mes-tree-toggle" data-mes-tree-toggle aria-expanded="true" title="{{ localize('Expand / collapse') }}">
                <i class="fa-solid fa-chevron-down"></i>
            </button>
        @else
            <span class="mes-tree-toggle-placeholder"></span>
        @endif

        <span class="mes-tree-label" title="{{ $node->displayName }}">
            <i class="{{ $node->iconClass ?? ($isPage ? 'fa-regular fa-file' : 'fa-regular fa-folder') }} mes-tree-icon"></i>
            <span class="mes-tree-name">{{ $node->displayName }}</span>
        </span>
        <span class="mes-badge mes-tree-type {{ $node->menuType->badgeClass() }} d-none d-md-inline-block">{{ localize($node->menuType->labelKey()) }}</span>
        <span class="mes-tree-meta mes-tree-code d-none d-lg-inline">{{ $node->menuCode }}</span>

        @if ($isPage)
            <div class="mes-tree-actions">
                @if ($isUserOverrideMode)
                    <span class="mes-badge {{ $node->roleAccessLevel->badgeClass() }} d-none d-lg-inline" title="{{ localize('From roles') }}">
                        {{ localize('Role') }}: {{ localize($node->roleAccessLevel->labelKey()) }}
                    </span>
                    <input type="text" class="form-control form-control-sm d-none d-xl-block" style="width: 180px; min-height: 26px;"
                           name="overrideReasons[{{ $node->menuId }}]" value="{{ $node->overrideReason }}" maxlength="300"
                           placeholder="{{ localize('Reason') }}" @disabled($isReadOnly) />
                @endif

                <div class="mes-access-level" role="radiogroup">
                    @if ($isUserOverrideMode)
                        <input type="radio" id="{{ $inputIdPrefix }}_inherit" name="{{ $inputName }}" value="" @checked($node->overrideAccessLevel === null) @disabled($isReadOnly) />
                        <label for="{{ $inputIdPrefix }}_inherit" class="mes-access-inherit">{{ localize('By role') }}</label>
                    @endif

                    <input type="radio" id="{{ $inputIdPrefix }}_none" name="{{ $inputName }}" value="0" @checked($selectedAccessLevel === AccessLevel::NoAccess) @disabled($isReadOnly) />
                    <label for="{{ $inputIdPrefix }}_none" class="mes-access-none">{{ localize('No access') }}</label>

                    <input type="radio" id="{{ $inputIdPrefix }}_read" name="{{ $inputName }}" value="1" @checked($selectedAccessLevel === AccessLevel::ReadOnly) @disabled($isReadOnly) />
                    <label for="{{ $inputIdPrefix }}_read" class="mes-access-read">{{ localize('Read only') }}</label>

                    <input type="radio" id="{{ $inputIdPrefix }}_full" name="{{ $inputName }}" value="2" @checked($selectedAccessLevel === AccessLevel::FullAccess) @disabled($isReadOnly) />
                    <label for="{{ $inputIdPrefix }}_full" class="mes-access-full">{{ localize('Full access') }}</label>
                </div>
            </div>
        @elseif (! $isReadOnly && ! $isUserOverrideMode)
            <div class="mes-tree-actions">
                <span class="small text-body-secondary d-none d-md-inline">{{ localize('Set all') }}:</span>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-light" data-mes-set-access="0">{{ localize('No access') }}</button>
                    <button type="button" class="btn btn-light" data-mes-set-access="1">{{ localize('Read only') }}</button>
                    <button type="button" class="btn btn-light" data-mes-set-access="2">{{ localize('Full access') }}</button>
                </div>
            </div>
        @endif
    </div>

    @if ($hasChildren)
        <ul class="mes-treeview-children">
            @foreach ($node->children as $childNode)
                @include('system.shared.permission-tree-node', ['node' => $childNode, 'depth' => $depth + 1])
            @endforeach
        </ul>
    @endif
</li>
