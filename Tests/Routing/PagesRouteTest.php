<?php

namespace Checlou\FlatFileCMSBundle\Tests\Routing;

use Checlou\FlatFileCMSBundle\CMS\Pages;
use Checlou\FlatFileCMSBundle\Routing\PagesRoute;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;

/**
 * Class PagesRouteTest
 */
class PagesRouteTest extends TestCase
{

    /**
     * Test la bonne génération des URL du CMS
     *
     * @return void
     * @throws \Checlou\FlatFileCMSBundle\CMS\Exception
     */
    public function testGenerateUrl() {
        $pages = new Pages(__DIR__ . '/../DataFixtures/files/default');

        $loader = new YamlFileLoader(
            new \Symfony\Component\Config\FileLocator(__DIR__ . '/../../Resources/config')
        );
        $request_context = new RequestContext();
        $router = new Router($loader, "routes.yaml", [], $request_context);
        $pages_route = new PagesRoute($router, $pages);

        // Path for index
        $this->assertEquals("/", $pages_route->generateUrl(
            $pages->get('/')
        ));

        // Path for a page
        $this->assertEquals("/page.html", $pages_route->generateUrlFromSlug(
            "page.html"
        ));

        // Path for an article in a sub-folder
        $this->assertEquals("/blog/page.html", $pages_route->generateUrlFromSlug(
            "blog/page.html"
        ));

        // Pagination for index
        $this->assertEquals("/page-2.html", $pages_route->generatePaginationUrl(
            $pages->get("/"), 2
        ));

        // Pagination for subfolder
        $this->assertEquals("/blog/page-2.html", $pages_route->generatePaginationUrl(
            $pages->get("blog/"), 2
        ));
    }

}