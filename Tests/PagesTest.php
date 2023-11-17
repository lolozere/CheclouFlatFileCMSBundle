<?php
/**
 * Created by PhpStorm.
 * User: lolozere
 * Date: 21/03/19
 * Time: 20:44
 */

namespace Checlou\FlatFileCMSBundle\Tests;

use Checlou\FlatFileCMSBundle\CMS\Exception;
use Checlou\FlatFileCMSBundle\CMS\Page\File\MarkdownFile;
use Checlou\FlatFileCMSBundle\CMS\Page\Page;
use Checlou\FlatFileCMSBundle\CMS\Pages;
use PHPUnit\Framework\TestCase;

class PagesTest extends TestCase
{

    public function testIsDirectoryPage() {
        $pages = $this->getMockBuilder(Pages::class)->disableOriginalConstructor()->setMethodsExcept(["isDirectoryPage"])->getMock();

        $this->assertTrue(
            $pages->isDirectoryPage(new \SplFileInfo("/path/to/index.md"))
        );
        $this->assertFalse(
            $pages->isDirectoryPage(new \SplFileInfo("/path/to/coucou.md"))
        );

        $file_md = MarkdownFile::instance(__DIR__ . '/DataFixtures/cms/build_test/index.md');
        $this->assertTrue(
            $pages->isDirectoryPage($file_md)
        );
        $file_md = MarkdownFile::instance(__DIR__ . '/DataFixtures/cms/build_test/test.md');
        $this->assertFalse(
            $pages->isDirectoryPage($file_md)
        );
    }

    public function testBuildingPages() {
        $pages = new Pages(__DIR__ . '/DataFixtures/files/build_test');

        // Md files in html slug
        $this->assertFalse($pages->has('bidule.html'));
        $this->assertTrue($pages->has('one-category/sub-category/results.html'));
        $this->assertTrue($pages->has('one-category/coucou.html'));
        $this->assertTrue($pages->has('page-test'));

        // Index.md transformed in directory page
        $this->assertFalse($pages->has('index.html'));
        $this->assertTrue($pages->has('/'));

        // Test d'existance d'une page représentant chaque dossier
        $this->assertTrue($pages->has('one-category/'));
        $this->assertTrue($pages->has('one-category/sub-category/'));

        // Test que le titre est bon
        $page_test = $pages->find(['slug' => "/"]);
        /** @var Page $page_test */
        $page_test = current($page_test);
        $this->assertEquals("cms.root.title", $page_test->getTitle());

        $page_test = $pages->find(['slug' => "one-category/sub-category/"]);
        /** @var Page $page_test */
        $page_test = current($page_test);
        $this->assertEquals("Sub Category", $page_test->getTitle());

        // Test avec dossier contenant déjà un index file et un titre défini
        $page_test = $pages->find(['slug' => "one-category/"]);
        /** @var Page $page_test */
        $page_test = current($page_test);
        $this->assertEquals("Bac général - Toutes les infos", $page_test->getTitle());

        // Test exception si deux index
        try {
            $pages = new Pages(
                __DIR__.'/DataFixtures/files/build_test_error'
            );
            $pages->buildPages();
            $this->fail(sprintf("Expecting exception when two files with index role are presents in a directory. Content path CMS %s", $pages->getContentPath()));
        } catch (Exception $e) {
            $this->assertStringEndsWith("contains more than one file index", $e->getMessage());
        }
    }

    public function testFindMethod() {
        // All pages
        $pages = new Pages(__DIR__ . '/DataFixtures/files/find_all_test');
        $pages_found = $pages->find();
        $this->assertCount(4, $pages_found);

        $pages_found = $pages->find(['parent' => 'one-category']);
        $this->assertCount(1, $pages_found);
        $this->assertEquals("one-category/coucou.html", $pages_found[0]->getSlug());

        // Filter by type
        $pages = new Pages(__DIR__ . '/DataFixtures/files/find_by_filters');
        $this->assertCount(1, $pages->find(['type' => 'post']));
        $this->assertCount(1, $pages->find(['type' => 'page']));

        // Test find directory page with trailing slash or not
        $page_test = $pages->find(['slug' => "one-category"]);
        /** @var Page $page_test */
        $page_test = current($page_test);
        $this->assertEquals("one-category/", $page_test->getSlug());

        $page_test = $pages->find(['slug' => "one-category/"]);
        /** @var Page $page_test */
        $page_test = current($page_test);
        $this->assertEquals("one-category/", $page_test->getSlug());

    }

    public function testAddBlogSection() {
        $pages = new Pages(__DIR__ . '/DataFixtures/files/blog_section');

        $this->assertFalse($pages->addBlogSection('/notfound'));
        $this->assertTrue($pages->addBlogSection('/blog'));

        $pages_found = $pages->find([]);
        foreach($pages_found as $page) {
            switch ($page->getSlug()) {
                case "/page.html":
                    $this->assertNull($page->getType(), "Default type expected : null");
                    break;
                case "/blog/page.html":
                    $this->assertEquals("page", $page->getType(), "Default type expected : page because set by the content editor");
                    break;
                case "/blog/post.html":
                    $this->assertEquals("post", $page->getType(), "Default type expected : post because page in the blog section");
                    break;
                default: break;
            }
        }
    }

    public function testFindMethodDefaultOrder() {
        $pages = new Pages(__DIR__ . '/DataFixtures/files/build_test/date_sort');

        $pages_found = $pages->find();
        $this->assertCount(3, $pages_found);
        $this->assertEquals('/',$pages_found[0]->getSlug());
        $this->assertEquals('page-test-date2',$pages_found[1]->getSlug());
    }

}