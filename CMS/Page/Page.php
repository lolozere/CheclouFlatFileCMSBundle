<?php
/**
 * Created by PhpStorm.
 * User: lolozere
 * Date: 21/03/19
 * Time: 16:09
 */

namespace Checlou\FlatFileCMSBundle\CMS\Page;

use Checlou\FlatFileCMSBundle\CMS\Page\File\MarkdownFile;
use Checlou\FlatFileCMSBundle\CMS\Pages;

class Page
{

    /**
     * @var MarkdownFile
     */
    private $file;

    /**
     * @var Page
     */
    protected $parent;

    /**
     * @var Pages
     */
    protected $pagesContext;

    /**
     * The whole content of the file
     *
     * @var string
     */
    protected $content = null;
    /**
     * The summary if exists
     *
     * @var string
     */
    protected $summary = null;

    /**
     * The summary delimiter
     *
     * @var string
     */
    protected $summaryDelimiter = '===';

    const TYPE_PAGE = 'page';
    const TYPE_POST = 'post';

    protected function __construct(MarkdownFile $file) {
        $this->file = $file;
    }

    /**
     * @return MarkdownFile
     */
    public function getFile(): MarkdownFile {
        return $this->file;
    }

    /**
     * @param MarkdownFile $file
     * @param Pages $pagesContext
     * @param Page|null $parent
     * @param string|null $default_title
     * @return Page
     */
    public static function build(MarkdownFile $file, Pages $pagesContext, Page $parent = null, string $default_title = null): Page {
        $page = new self($file);
        $page->pagesContext = $pagesContext;
        $page->parent = $parent;

        // Header / Frontmatter
        $var = $page->file->header();

        if ($page->isDirectoryPage()) {
            $default_title = $default_title ?? Sanitizer::unsanitize(basename(dirname($file->filename())));
        } else {
            $default_title = $default_title ?? Sanitizer::unsanitize(basename($file->filename(), ".md"));
        }

        $default_type = null;
        // Set default type as post if blog section and type not defined
        if ($pagesContext->isPageInBlogSection($page) && empty($var['type']))
            $default_type = 'post';

        $var_required = [
            'meta' => ['description' => null, 'keywords' => null],
            'type' => $default_type,
            'site_slug' => null,
            'visible' => true,
            'title' => $default_title
        ];
        $var = array_replace_recursive($var_required, $var);
        $page->file->header($var);

        return $page;
    }

    /**
     * @param string $type
     *
     * @return Page
     */
    public function setType(string $type): Page {
        $var = $this->file->header();
        $var['type'] = $type;
        $this->file->header($var);
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string {
        return $this->file->header()['type'] ?? 'page';
    }

    /**
     * @return bool
     */
    public function isDirectoryPage():bool {
        return $this->pagesContext->isDirectoryPage($this->file);
    }

    /**
     * @return object
     */
    public function getHeaders(): object {
        return (object) $this->file->header();
    }

    /**
     * @return string
     */
    public function getSlug(): string {
        return $this->getHeaders()->slug;
    }

    public function setSlug($slug) {
        $var = $this->file->header();
        $var['slug'] = $slug;
        $this->file->header($var);
    }

    /**
     * @return string
     */
    public function getTitle(): string {
        if (empty($this->getHeaders()->title))
            return ucfirst(str_replace('-', ' ', $this->getSlug()));
        return $this->getHeaders()->title;
    }

    /**
     * @param string $title
     *
     * @return void
     */
    public function setTitle(string $title) {
        $var = $this->file->header();
        $var['title'] = $title;
        $this->file->header($var);
    }

    /**
     * @return string Markdown formated
     */
    public function getContent(): string {
        if (is_null($this->content))
            $this->content = trim($this->file->markdown());
        return $this->content;
    }

    /**
     * The summary is based on the text portion before the === delimiter
     *
     * @return string
     */
    public function getSummary(): string {
        if (is_null($this->summary)) {
            $this->summary = '';
            // Handle summary divider
            $divider_pos = mb_strpos($this->getContent(), $this->summaryDelimiter);
            if ($divider_pos !== false) {
                $this->summary = trim(
                    mb_substr($this->getContent(), 0, $divider_pos)
                );
            }
        }
        return $this->summary;
    }

    /**
     * @return string
     */
    public function getContentAfterSummary(): string {
        $divider_pos = mb_strpos($this->getContent(), $this->summaryDelimiter);
        if ($divider_pos !== false) {
            return trim(
                mb_substr($this->getContent(), $divider_pos + mb_strlen($this->summaryDelimiter))
            );
        } else {
            return $this->getContent();
        }
    }

    /**
     * Return the published date
     *
     * @return \DateTime
     * @throws \Exception
     */
    public function getPublishedAt(): \DateTime {
        /** @var string|null $published_at_date */
        $published_at_date = null;
        // Search de valid attribute for pusblish date : we accept "date" and "published_at"
        foreach(['date', 'published_at'] as $published_at_attribute) {
            if (!empty($this->getHeaders()->{$published_at_attribute})) {
                $published_at_date = $this->getHeaders()->{$published_at_attribute};
            }
        }
        if (!is_null($published_at_date)) {
            $format_accepted = ['Y-m-d', 'Y-m-d H:i', 'U'];
            foreach($format_accepted as $date_format) {
                $date = \DateTime::createFromFormat($date_format, $published_at_date, new \DateTimeZone(date_default_timezone_get()));
                if ($date !== false)
                    return $date;
            }
        }
        return $this->getCreatedAt();
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    public function getCreatedAt(): \DateTime {
        return \DateTime::createFromFormat("U", filectime($this->file->filename()))
            ->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
    }

    /**
     * Return the last modification of the file, based on the filemtime
     *
     * @return \DateTime
     * @throws \Exception
     */
    public function getModifiedAt(): \DateTime {
        if ($this->file->modified()) {
            return \DateTime::createFromFormat("U", $this->file->modified())
                ->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
        }
        return new \DateTime();
    }


    /**
     * Return a list of page parents from the beginning to the first ancestor of the page
     *
     * @return Page[]
     */
    public function getParents(): array {
        $ancestors = [];
        $current_page = $this;
        while(!is_null($current_page->parent)) {
            $ancestors[] = $current_page->parent;
            $current_page = $current_page->parent;
        }
        return array_reverse($ancestors);
    }

    /**
     * Set parent page of the page
     *
     * @param Page|null $parent
     * @return Page current page
     */
    public function setParent(?Page $parent = null): Page {
        $this->parent = $parent;
        return $this;
    }

    /**
     * Return the parent of the page
     *
     * @return Page|null
     */
    public function getParent(): ?Page {
        return $this->parent;
    }

    /**
     * Return true if the page directory is his first ancestor
     *
     * @param Page $page
     *
     * @return bool
     */
    public function isParent(Page $page_directory): bool {
        $parents = $this->getParents();
        return (sizeof($parents) > 0 && $parents[sizeof($parents) - 1]->getSlug() == $page_directory->getSlug());
    }

}