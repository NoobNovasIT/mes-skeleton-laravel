{{--
    = Views/Shared/_Pager.cshtml. Phân trang server-side, bấm trang chỉ tải lại vùng $regionName (data-mes-region-link).
    Tham số: $pagedResult (PagedResult), $buildPageUrl (fn(int $pageNumber): string), $regionName.
--}}
@php
    $visiblePageRadius = 2;
    $totalPageCount = $pagedResult->totalPageCount();
    $firstVisiblePage = max(1, $pagedResult->pageNumber - $visiblePageRadius);
    $lastVisiblePage = min($totalPageCount, $pagedResult->pageNumber + $visiblePageRadius);
@endphp

<div class="d-flex flex-wrap align-items-center gap-2 w-100">
    <span class="text-body-secondary small">{{ localize('Total: {0} record(s)', $pagedResult->totalCount) }}</span>
    @if ($totalPageCount > 1)
        <nav class="ms-auto">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item {{ $pagedResult->pageNumber <= 1 ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ $buildPageUrl($pagedResult->pageNumber - 1) }}" data-mes-region-link="{{ $regionName }}">&laquo;</a>
                </li>
                @for ($pageNumber = $firstVisiblePage; $pageNumber <= $lastVisiblePage; $pageNumber++)
                    <li class="page-item {{ $pageNumber === $pagedResult->pageNumber ? 'active' : '' }}">
                        <a class="page-link" href="{{ $buildPageUrl($pageNumber) }}" data-mes-region-link="{{ $regionName }}">{{ $pageNumber }}</a>
                    </li>
                @endfor
                <li class="page-item {{ $pagedResult->pageNumber >= $totalPageCount ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ $buildPageUrl($pagedResult->pageNumber + 1) }}" data-mes-region-link="{{ $regionName }}">&raquo;</a>
                </li>
            </ul>
        </nav>
    @endif
</div>
