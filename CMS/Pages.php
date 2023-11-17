<?php
/**
 * Created by PhpStorm.
 * User: lolozere
 * Date: 21/03/19
 * Time: 16:09
 */

namespace Checlou\FlatFileCMSBundle\CMS;

use Checlou\FlatFileCMSBundle\CMS\Page\File\MarkdownFile;
use Checlou\FlatFileCMSBundle\CMS\Page\Page;
use Checlou\FlatFileCMSBundle\CMS\Page\Sanitizer;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class Pages implements \Countable
{
    /**
     * @var string
     */
    private $contentPath;
    /**
     * @var string
     */
    private $temporaryContentPath;
    /**
     * @var Page[]
     */
    protected $pages = [];
    /**
     * @var array
     */
    protected $tree = [];
    /**
     * @var bool
     */
    protected $isPagesBuilt = false;
    /**
     * All page that are a blog section entry in the CMS
     *
     * @var Page[]
     */
    protected $blogSectionsPage = [];

    public function __construct($content_path) {
        // real path because we need a path without references to /./, /../ and extra /
        $this->contentPath = realpath($content_path);
        // md5 : to have a temps directory specific for this cms content
        $this->temporaryContentPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cms_pages' . DIRECTORY_SEPARATOR . md5($this->contentPath);
    }

    /**
     * Build pages list from the content path
     * @return void
     * @throws \Exception
     */
    public function buildPages() {
        if (!$this->isPagesBuilt) {
            if (file_exists($this->contentPath))
                $this->buildList($this->contentPath);
        }
        $this->isPagesBuilt = true;
    }

    protected function slugify(MarkdownFile $file) {
        $headers = $file->header();
        if (!isset($headers['slug'])) {
            $tree = explode("/", $this->getUrlRelativePath($file->filename()));
            if ($this->isDirectoryPage($file)) {
                // Remove the last part from the slug to keep a directory model : blog/ma-category instead of blog/ma-category/index.html
                array_pop($tree);
                return join('/', $tree) . '/';
            } else {
                return preg_replace('/\-md$/', '.html', join('/', $tree));
            }
        }
        return $headers['slug'];
    }

    /**
     * @param $content_path
     * @param Page|null $parent_page
     * @return void
     * @throws Exception
     */
    protected function buildList($content_path, Page $parent_page = null): void {
        $finder = new Finder();
        $finder->files()->depth(0)->in($content_path);

        // Index file to represent the content directory ?
        $page_index = null;
        foreach ($finder as $file) {
            if ($this->isDirectoryPage($file) && is_null($page_index)) {
                $page_index = $this->loadFileAsPage($file, $parent_page);
            } elseif ($this->isDirectoryPage($file) && !is_null($page_index)) {
                throw new Exception(sprintf("File %s is a directory page and page index already found with slug %s. Error : $content_path contains more than one file index", $file->getRealPath(), $page_index->getSlug()));
            }
        }
        // We create an index page for the content path if not exist
        if (is_null($page_index)) {
            $page_index = $this->createTemporaryPage(
                'index', $this->getUrlRelativePath($content_path), '', $parent_page
            );
        }

        // We create a page for each markdown file in the directory
        foreach ($finder as $file) {
            if (!$this->isDirectoryPage($file)) {
                $this->loadFileAsPage($file, $page_index);
            }
        }

        // On parcourt les sous-dossiers
        $finder = new Finder();
        $finder->directories()->depth(0)->in($content_path);
        foreach ($finder as $dir) {
            $this->buildList($dir->getRealPath(), $page_index);
        }
    }
    /**
     * Configure the CMS by adding a content path (inside the default content_path) as a blog section.
     * All pages under this content path are considered as a post and not a default content page type.
     *
     * @param $relative_content_path
     *
     * @return bool
     * @throws
     */
    public function addBlogSection($relative_content_path): bool {
        // Get the page directory that represents this blog section
        $blog_section_page = $this->find(['slug' => $relative_content_path]);
        if (count($blog_section_page) <= 0)
            return false;

        $this->blogSectionsPage[] = current($blog_section_page);

        /**
         * All contents under the section become post if type does not set in the header
         * We have to do it here because all pages are already built
         */
        if ($this->isPagesBuilt) {
            foreach ($this->pages as $page) {
                // If type already set, we can not override it because it's a choice of the content editor
                if (!empty($page->getHeaders()->type))
                    continue;

                if ($this->isPageInBlogSection($page) && !$this->isDirectoryPage($page->getFile()))
                    $page->setType('post');
            }
        }

        return true;
    }

    /**
     * @return Page[]
     */
    public function getBlogSections(): array {
        return $this->blogSectionsPage;
    }

    /**
     * Return true if the page is in a blog section
     *
     * @param Page $page
     *
     * @return bool
     */
    public function isPageInBlogSection(Page $page): bool {
        foreach($this->blogSectionsPage as $blog_section_page) {
            foreach ($page->getParents() as $parent_page)
                if ($parent_page->getSlug() == $blog_section_page->getSlug())
                    return true;
        }
        return false;
    }

    /**
     * Return true if the page is a direct child of a blog section
     *
     * @param Page $page
     *
     * @return bool
     */
    public function isFirstChildOfBlogSection(Page $page): bool {
        foreach($this->blogSectionsPage as $blog_section_page) {
            if (!is_null($page->getParent()) && $page->getParent() === $blog_section_page)
                return true;
        }
        return false;
    }

    /**
     * Return the url relative path of a content path from the root directory of the CMS content without trailing slash
     *
     * @param string $content_path
     *
     * @return string
     */
    protected function getUrlRelativePath(string $content_path): string {
        // We remove the root path of the content to keep only the path used for the url
        $contents_path = [$this->contentPath, $this->temporaryContentPath];
        foreach($contents_path as $root_path) {
            $content_path = str_replace($root_path, "", $content_path);
        }
        $content_path = trim($content_path, DIRECTORY_SEPARATOR);
        // We slugify
        $tree = explode(DIRECTORY_SEPARATOR, $content_path);
        foreach ($tree as $key => $tree_part) {
            $tree[$key] = Sanitizer::sanitize($tree_part);
        }
        return join('/', $tree);
    }

    /**
     * Create a temporary page on the fly if doest not exist
     *
     * @param string $title Title of the page content
     * @param string $filename filename for the file content
     * @param string $url_path Path of the directory to store the file
     * @param string $content Content of the page
     * @param Page $parent_page Parent of the page
     *
     * @return Page
     */
    public function createTemporaryPage(string $filename, string $url_path, string $content, Page $parent_page = null): Page {
        $directory_path = $this->temporaryContentPath
            . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $url_path); // To store in a subdirectory matching the desired URL
        $file_path = $directory_path . DIRECTORY_SEPARATOR . $filename . '.md';

        // Default title is the last name of the path except if it's the root directory
        $default_title = Sanitizer::unsanitize(basename($url_path));
        if ($filename == 'index' && is_null($parent_page)) // Home page
            $default_title = 'cms.root.title';
        $yaml = Yaml::dump(['title' => $default_title]);

        // We don't create already exist
        if (!file_exists($file_path)) {
            // Create directories
            @mkdir($directory_path, 0777, true);
            file_put_contents($file_path, '---'.PHP_EOL.$yaml.PHP_EOL.'---'.PHP_EOL.PHP_EOL.$content);
        }
        return $this->loadFileAsPage(new \SplFileInfo($file_path), $parent_page);
    }

    /**
     *
     * @param \SplFileInfo $file
     * @param Page|null $parent_page
     *
     * @return Page
     */
    protected function  loadFileAsPage(\SplFileInfo $file, Page $parent_page = null): Page {
        $file_md = MarkdownFile::instance($file->getRealPath());

        // if it's the root directory and not title set, we set the title to the root title
        $default_title = null;
        if (is_null($parent_page) && empty($file_md->header()["title"]))
            $default_title = 'cms.root.title';

        $page = Page::build($file_md, $this, $parent_page, $default_title);
        $page->setSlug($this->slugify($file_md));
        $this->pages[$page->getSlug()] = $page;

        return $page;
    }

    /**
     * @param Page $page
     * @return void
     * @throws \Exception
     */
    public function removePage(Page $page) {
        // Remove the page from childs
        $childs = $this->find(['parent' => $page]);
        foreach($childs as $child) {
            $child->setParent(null);
        }
        unset($this->pages[$page->getSlug()]);
    }

    /**
     * Get page even if not for the current site
     *
     * @param $slug
     *
     * @return Page
     * @throws Exception
     */
    public function get($slug): Page {
        if ($this->has($slug))
            return $this->pages[$slug];
        throw new Exception(sprintf("%s page not found", $slug));
    }

    /**
     * @param $slug
     *
     * @return bool
     * @throws
     */
    public function has($slug): bool {
        $this->buildPages();
        return array_key_exists($slug, $this->pages);
    }

    /**
     * Search visible pages for the current site
     *
     * Filters are :
     * - slug : return the page with this slug
     * - type : return pages of this king of type page
     * - parent : return pages having parent as an ancestor page excluding directory page.
     * - header : return pages having this specific headers
     * - excluded_pages : tableau de pages à exlure
     *
     * Exclude all contents in a _contents subdirectory or having a different site_slug targetting.
     *
     * @param array $filters
     * @param array $orders
     * @return Page[] Array zero-indexed
     * @throws
     */
    public function find(array $filters = [], array $orders = []): array {
        $this->buildPages();

        $pages = [];
        /*
         * Le principe est de faire un "continue" dès que cela ne correspond pas. Si aucun "continue" n'est fait, ce n'est que ça ne correspond pas aux filtres.
         */
        foreach($this->pages as $page) {

            // Les parties de contenu ne sont pas des pages valables
            if (stristr($page->getSlug(), '_contents/')) {
                continue;
            }

            // Par défaut n'accepte que les pages visibles
            if (!isset($filters['headers']['visible']) && !$page->getHeaders()->visible)
                continue;

            /**
             * Filtre par valeurs d'entête
             *
             * On peut donner plusieurs valeurs possibles à des entêtes
             */
            if (isset($filters['headers']) && is_array($filters['headers'])) {
                foreach($filters['headers'] as $key => $values) {
                    if (!is_array($values))
                        $values = [$values];
                    $headers_ok = false;
                    foreach($values as $value_authorized) {
                        $headers_ok = $value_authorized === $page->getHeaders()->{$key};
                    }
                    if (!$headers_ok)
                        continue;
                }
            }

            // Filtre par slug
            if (isset($filters['slug'])) {
                if (!$page->isDirectoryPage()) {
                    if ($page->getSlug() != $filters['slug']) // correspond pas --> ocntinue
                    {
                        continue;
                    }
                } elseif ((trim($filters['slug'], "/")."/") != $page->getSlug()) {
                    // Directory page has / at the end, so we test with always a "/" at the end.
                    continue;
                }
            }

            // Filtre par type de page
            if (isset($filters['type']) && (!property_exists($page->getHeaders(), 'type')
                    || $page->getHeaders()->type != $filters['type'])) {
                continue;
            }

            // Filtre par pages que l'on exclut
            if (isset($filters['excluded_pages']) && is_array($filters['excluded_pages'])) {
                $is_excluded = false;
                /** @var Page $page_excluded */
                foreach($filters['excluded_pages'] as $page_excluded) {
                    if ($page_excluded->getSlug() == $page->getSlug()) {
                        $is_excluded = true;
                        break;
                    }
                }
                if ($is_excluded)
                    continue;
            }

            // Filtre pour n'autoriser que les pages ayant une certaine page parent
            if (isset($filters['parent'])) {
                if ($page->isDirectoryPage())
                    continue;
                if (is_string($filters['parent'])) {
                    $parent = $this->find(['slug' => $filters['parent']]);
                    if (count($parent) <= 0)
                        throw new \Exception(sprintf("Bad CMS filter parent : parent %s not found", $filters['parent']));
                    $parent = current($parent);
                } elseif ($filters['parent'] instanceof Page)
                    $parent = $filters['parent'];
                else
                    throw new \Exception("Bad CMS filter parent : string or Page expected");
                $is_parent = false;
                foreach($page->getParents() as $ancestor) {
                    if ($ancestor->getSlug() == $parent->getSlug()) {
                        $is_parent = true;
                    }
                }
                if (!$is_parent)
                    continue;
            }

            // A passé tous les filtres, donc la page sera retournée.
            $pages[] = $page;
        }
        usort($pages, function(Page $p1, Page $p2) use ($orders) {
            return ($p1->getPublishedAt()->getTimestamp() >= $p2->getPublishedAt()->getTimestamp()) ? -1 : 1;
        });
        return $pages;
    }

    /**
     * @return string
     */
    public function getContentPath(): string {
        return $this->contentPath;
    }

    /**
     * Return true if the file represent the directory containing it
     *
     * @param \SplFileInfo|MarkdownFile $file
     *
     * @return bool
     */
    public function isDirectoryPage(object $file): bool {
        if ($file instanceof \SplFileInfo)
            return in_array($file->getFilename(), ['index.md', 'category.md']);
        elseif ($file instanceof MarkdownFile)
            return in_array($file->basename(), ['index', 'category']);
        return false;
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function count(): int
    {
        $this->buildPages();
        return count($this->pages);
    }
}