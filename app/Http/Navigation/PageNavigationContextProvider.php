<?php

namespace App\Http\Navigation;

use App\Http\Permissions\PagePermission;
use App\Services\Permissions\NavigationDto;
use App\Services\Permissions\NavigationNodeDto;
use App\Services\Permissions\PermissionService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

/**
 * = PageNavigationContextProvider.cs. Đăng ký "scoped" (1 instance / request) trong AppServiceProvider
 * để sidebar, tab Module và breadcrumb dùng chung một kết quả.
 *
 * Xác định Page đang mở: MenuCode của middleware mes.permission -> nếu không có thì so khớp URL (tiền tố dài nhất).
 */
final class PageNavigationContextProvider
{
    private ?PageNavigationContext $pageNavigationContext = null;

    public function __construct(
        private readonly PermissionService $permissionService,
    ) {
    }

    public function get(): PageNavigationContext
    {
        if ($this->pageNavigationContext !== null) {
            return $this->pageNavigationContext;
        }

        $userAccountId = Auth::id();
        $navigation = $userAccountId === null
            ? NavigationDto::empty()
            : $this->permissionService->getNavigation((int) $userAccountId, App::getLocale());

        $activePath = $this->buildPathToRoot($this->findActiveNode($navigation), $navigation);
        $activeModule = $activePath[0] ?? ($navigation->modules[0] ?? null);

        return $this->pageNavigationContext = new PageNavigationContext($navigation, $activeModule, $activePath);
    }

    private function findActiveNode(NavigationDto $navigation): ?NavigationNodeDto
    {
        $menuCode = PagePermission::current()->menuCode;
        if ($menuCode !== '') {
            foreach ($navigation->nodeByMenuId as $node) {
                if (strcasecmp($node->menuCode, $menuCode) === 0) {
                    return $node;
                }
            }
        }

        $requestPath = rtrim('/'.ltrim(request()->path(), '/'), '/');
        $bestNode = null;
        foreach ($navigation->nodeByMenuId as $node) {
            if (! empty($node->url) && $this->isPathMatch($requestPath, $node->url)
                && ($bestNode === null || strlen($node->url) > strlen($bestNode->url))) {
                $bestNode = $node;
            }
        }

        return $bestNode;
    }

    private function isPathMatch(string $requestPath, string $menuUrl): bool
    {
        $normalizedMenuUrl = rtrim(explode('?', $menuUrl)[0], '/');

        return strcasecmp($requestPath, $normalizedMenuUrl) === 0
            || str_starts_with(strtolower($requestPath), strtolower($normalizedMenuUrl).'/');
    }

    /** @return list<NavigationNodeDto> */
    private function buildPathToRoot(?NavigationNodeDto $activeNode, NavigationDto $navigation): array
    {
        $pathFromPageToRoot = [];
        $currentNode = $activeNode;
        while ($currentNode !== null) {
            $pathFromPageToRoot[] = $currentNode;
            $currentNode = $currentNode->parentMenuId === null ? null : ($navigation->nodeByMenuId[$currentNode->parentMenuId] ?? null);
        }

        return array_reverse($pathFromPageToRoot);
    }
}
