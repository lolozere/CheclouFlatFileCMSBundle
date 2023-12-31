<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Checlou\FlatFileCMSBundle\CheclouFlatFileCMSBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;

return [
    new FrameworkBundle(),
    new TwigBundle(),
    new CheclouFlatFileCMSBundle(),
    new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle()
];