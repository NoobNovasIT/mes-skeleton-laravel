{{--
    = PageHeaderViewComponent: breadcrumb + tiêu đề. Tiêu đề = $title (nếu controller truyền) hoặc tên Page trong menu.
    Page chi tiết (Edit, Detail) mở thẳng URL: thêm tên Page cha vào breadcrumb.
--}}
@php
    $activePage = $pageNavigationContext->activePage();
    $resolvedPageTitle = $pageTitle ?? $activePage?->displayName ?? '';
    $breadcrumbItems = array_values(array_filter(
        array_map(fn ($node) => $node->displayName, $pageNavigationContext->activePath),
        fn ($displayName) => $displayName !== $resolvedPageTitle));
    if ($activePage !== null && $resolvedPageTitle !== $activePage->displayName && ! in_array($activePage->displayName, $breadcrumbItems, true)) {
        $breadcrumbItems[] = $activePage->displayName;
    }
@endphp

@if ($resolvedPageTitle !== '')
    <div class="mes-page-head">
        @foreach ($breadcrumbItems as $breadcrumbItem)
            <span class="mes-breadcrumb-item">{{ $breadcrumbItem }}</span>
            <span class="mes-breadcrumb-separator">/</span>
        @endforeach
        <h1 class="mes-page-title">{{ $resolvedPageTitle }}</h1>
    </div>
@endif
