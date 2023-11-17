<?php

namespace Checlou\FlatFileCMSBundle\Markdown;

class Parsedown extends \ParsedownExtra
{
    use ParsedownBootstrapThemeTrait;

    public $completable_blocks = [];
    public $continuable_blocks = [];

    /**
     * ParsedownExtra constructor.
     *
     * @param $page
     * @param $defaults
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->addBlockType('!', 'Notices', true);
        $this->InlineTypes['"'] = ["Quote"];
    }

    /**
     * Do not escape quote to keep shortcode working
     *
     * @param $Excerpt
     * @return void
     */
    protected function inlineQuote($Excerpt) { return; }

    /**
     * Public method to parse a content. We can use it letter to make something like striptags
     *
     * @param $text
     *
     * @return string|string[]|null
     */
    public function convert($text) {
        return $this->text($text);
    }

    /**
     * Be able to define a new Block type or override an existing one
     *
     * @param $type
     * @param $tag
     * @param bool $continuable
     * @param bool $completable
     * @param $index
     */
    public function addBlockType($type, $tag, bool $continuable = false, bool $completable = false, $index = null)
    {
        $block = &$this->unmarkedBlockTypes;
        if ($type) {
            if (!isset($this->BlockTypes[$type])) {
                $this->BlockTypes[$type] = [];
            }
            $block = &$this->BlockTypes[$type];
        }

        if (null === $index) {
            $block[] = $tag;
        } else {
            array_splice($block, $index, 0, [$tag]);
        }

        if ($continuable) {
            $this->continuable_blocks[] = $tag;
        }
        if ($completable) {
            $this->completable_blocks[] = $tag;
        }
    }


    /**
     * Overrides the default behavior to allow for plugin-provided blocks to be continuable
     *
     * @param $Type
     *
     * @return bool
     */
    protected function isBlockContinuable($Type): bool
    {
        return \in_array($Type, $this->continuable_blocks) || method_exists($this, 'block' . $Type . 'Continue');
    }

    /**
     *  Overrides the default behavior to allow for plugin-provided blocks to be completable
     *
     * @param $Type
     *
     * @return bool
     */
    protected function isBlockCompletable($Type): bool
    {
        return \in_array($Type, $this->completable_blocks) || method_exists($this, 'block' . $Type . 'Complete');
    }

}
