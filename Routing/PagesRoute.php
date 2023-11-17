<?php

namespace Checlou\FlatFileCMSBundle\Routing;

use Checlou\FlatFileCMSBundle\CMS\Exception;
use Checlou\FlatFileCMSBundle\CMS\Page\Page;
use Checlou\FlatFileCMSBundle\CMS\Pages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Generate routes for pages
 *
 */
class PagesRoute
{
    protected $router;
    protected $pages;

    /**
     * @param RouterInterface $router
     * @param Pages $pages
     */
    public function __construct(RouterInterface $router, Pages $pages)
    {
        $this->router = $router;
        $this->pages = $pages;
    }

    /**
     * @param Page $page
     * @param bool $schemeRelative
     * @return string
     */
    public function generateUrl(Page $page, bool $schemeRelative = true): string
    {
        $route = 'checlou_flat_file_cms_page';
        if ($page->isDirectoryPage() && is_null($page->getParent()))
            $route = 'checlou_flat_file_cms_page_index';
        return $this->router->generate($route, ['slug' => $page->getSlug()],
            $schemeRelative ? UrlGeneratorInterface::ABSOLUTE_PATH : UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * @param string $slug
     * @param bool $schemeRelative
     * @return string
     * @throws Exception
     */
    public function generateUrlFromSlug(string $slug, bool $schemeRelative = true): string {
        $page = $this->pages->get($slug);
        return $this->generateUrl($page, $schemeRelative);
    }

    /**
     * @param Page $page
     * @param int $page_index
     * @return string
     */
    public function generatePaginationUrl(Page $page, int $page_index): string
    {
        if ($page->isDirectoryPage() && $page_index <= 1)
            return $this->generateUrl($page);
        if ($page->isDirectoryPage() && is_null($page->getParent()))
            return $this->router->generate('checlou_flat_file_cms_page_pagination_index', [
                'page_index' => $page_index
            ]);
        else
            return $this->router->generate('checlou_flat_file_cms_pagination', [
                'slug' => trim($page->getSlug(), "/"),
                'page_index' => $page_index
            ]);
    }

}