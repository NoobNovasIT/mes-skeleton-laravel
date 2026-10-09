<?php

namespace App\Services\Menus;

/** = Mes.Service/Menus/TreeBuilder.cs: danh sách phẳng (đã sắp xếp) -> cây theo ParentMenuId. */
final class TreeBuilder
{
    /**
     * Node phải có thuộc tính $menuId, $parentMenuId và mảng $children.
     *
     * @template TNode of object
     * @param list<TNode> $flatNodes
     * @return list<TNode>
     */
    public static function build(array $flatNodes): array
    {
        $nodeById = [];
        foreach ($flatNodes as $node) {
            $nodeById[$node->menuId] = $node;
        }

        $rootNodes = [];
        foreach ($flatNodes as $node) {
            if ($node->parentMenuId !== null && isset($nodeById[$node->parentMenuId])) {
                $nodeById[$node->parentMenuId]->children[] = $node;
            } else {
                $rootNodes[] = $node;
            }
        }

        return $rootNodes;
    }
}
