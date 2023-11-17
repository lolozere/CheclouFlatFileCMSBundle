<?php
/**
 * Created by PhpStorm.
 * User: lolozere
 * Date: 22/03/19
 * Time: 09:15
 */

namespace Checlou\FlatFileCMSBundle\Twig;

use Checlou\FlatFileCMSBundle\CMS\Page\Page;
use Checlou\FlatFileCMSBundle\Markdown\Parsedown;
use Checlou\FlatFileCMSBundle\Routing\PagesRoute;
use HtmlTruncator\InvalidHtmlException;
use HtmlTruncator\Truncator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PageTwigExtension extends AbstractExtension {

    /**
     * @var PagesRoute
     */
    protected $pagesRoute;

    /**
     * @var Parsedown
     */
    protected $parsedown;

    protected $parsed = [];

    protected $breadcrumbPosition = 0;

    public function __construct(PagesRoute $pagesRoute, Parsedown $markdown) {
        $this->pagesRoute = $pagesRoute;
        $this->parsedown = $markdown;
    }

    public function getFunctions(): array
    {
        return array(
            'checlou_flat_file_cms_page_pagination_path' =>  new TwigFunction('checlou_flat_file_cms_page_pagination_path', array($this, 'pagePaginationPath')),
            'checlou_flat_file_cms_page_path' =>  new TwigFunction('checlou_flat_file_cms_page_path', array($this, 'pagePath')),
            'checlou_flat_file_cms_page_summary' =>  new TwigFunction('checlou_flat_file_cms_page_summary', array($this, 'pageSummary'), array('is_safe' => array('html'))),
            'checlou_flat_file_cms_page_content' =>  new TwigFunction('checlou_flat_file_cms_page_content', array($this, 'pageContent'), array('is_safe' => array('html'))),
            'checlou_flat_file_cms_page_feature_image_url' => new TwigFunction('checlou_flat_file_cms_page_feature_image_url', array($this, 'pageFeatureImageUrl'), array('is_safe' => array('html'))),
            'checlou_flat_file_cms_page_has_feature_image' => new TwigFunction('checlou_flat_file_cms_page_has_feature_image', array($this, 'pageHasFeatureImage'), array('is_safe' => array('html'))),
            'checlou_flat_file_cms_page_breadcrumb_start' => new TwigFunction('checlou_flat_file_cms_page_breadcrumb_start', array($this, 'pageBreadcrumbSet'), array('is_safe' => array('html'))),
            'checlou_flat_file_cms_page_breadcrumb_add_position' => new TwigFunction('checlou_flat_file_cms_page_breadcrumb_add_position', array($this, 'pageBreadcrumbSet'), array('is_safe' => array('html')))
        );
    }

    /**
     * @param int|null $add
     * @return void
     */
    public function pageBreadcrumbSet(?int $add = null) {
        if (is_null($add))
            $this->breadcrumbPosition = 0;
        else
            $this->breadcrumbPosition = $this->breadcrumbPosition + $add;
        return $this->breadcrumbPosition;
    }

    /**
     * @param Page $page
     * @param bool $schemeRelative
     *
     * @return string
     */
    public function pagePaginationPath(Page $page, int $page_index): string {
        return $this->pagesRoute->generatePaginationUrl($page, $page_index);
    }

    /**
     * @param Page $page
     * @param bool $schemeRelative
     *
     * @return string
     */
    public function pagePath(Page $page, bool $schemeRelative = true): string {
        return $this->pagesRoute->generateUrl($page, $schemeRelative);
    }

    /**
     *
     * @param Page $page
     * @param int $size
     * @param bool $textOnly
     * @return string
     * @throws InvalidHtmlException
     */
    public function pageSummary(Page $page, int $size = 50, bool $textOnly = false): string {
        $summary = $page->getSummary();

        // Set up variables to process summary from page or from custom summary
        if (empty($summary)) {
            $parsed = $this->parsePage($page);
            $content = $parsed['content'];
        } else {
            $content = $this->parsedown->convert($summary);
        }

        // If no size return the entire content found
        if (empty($size)) {
            return $content;
        }

        // Only return string but not html, wrap whatever html tag you want when using
        if ($textOnly) {
            // Use mb_strwidth to deal with the 2 character widths characters
            $content_size = mb_strwidth($content, 'utf-8');
            if ($content_size <= $size) {
                return $content;
            }
            return mb_strimwidth($content, 0, $size, '...', 'utf-8');
        }

        $content = Truncator::truncate(strip_tags($content, 'p,strong,b,i,span'), $size);

        return html_entity_decode($content);
    }

    /**
     * @param Page $page
     * @param array $params
     * @return string
     */
    public function pageContent(Page $page, array $params = []): string {
        $parsed = $this->parsePage($page, $params);
        return $parsed['content'];
    }

    /**
     * @param Page $page
     *
     * @return bool
     */
    public function pageHasFeatureImage(Page $page): bool {
        $parsed = $this->parsePage($page);
        return !empty($parsed['featured_src_image']);
    }

    /**
     * @param Page $page
     * @return string|null
     */
    public function pageFeatureImageUrl(Page $page): ?string {
        $parsed = $this->parsePage($page);
        if (!empty($parsed['featured_src_image'])) {
            return $parsed['featured_src_image'];
        }
        return null;
    }

    /**
     * @param Page $page
     * @param array $params
     *
     * @return mixed
     */
    protected function parsePage(Page $page, array $params = []) {
        $page_id = md5($page->getSlug());
        if (isset($this->parsed[$page_id]))
            return $this->parsed[$page_id];

        // Build html version of content
        $summary = $page->getSummary();
        if (!empty($summary)) {
            $content_html = $this->parsedown->convert($page->getContentAfterSummary());
        } else {
            $content_html = $this->parsedown->convert($page->getContent());
        }

        // Search featured image
        $image = [];
        preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $content_html, $image);
        $this->parsed[$page_id] = ['content' => $content_html, 'featured_src_image' => ((isset($image['src']))?$image['src']:null)];

        return $this->parsed[$page_id];
    }

}