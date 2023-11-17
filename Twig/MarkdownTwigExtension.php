<?php

namespace Checlou\FlatFileCMSBundle\Twig;

use Checlou\FlatFileCMSBundle\Markdown\Parsedown;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownTwigExtension extends AbstractExtension
{
    /**
     * @var Parsedown
     */
    protected $parsedown;

    public function __construct(Parsedown $markdown) {
        $this->parsedown = $markdown;
    }

    public function getFilters(): array
    {
        return array(
            'checlou_flat_file_cms_md2html' => new TwigFilter('checlou_flat_file_cms_md2html', array($this, 'markdown2Html'))
        );
    }

    public function markdown2Html($md) {
        return $this->parsedown->text($md);
    }


}
