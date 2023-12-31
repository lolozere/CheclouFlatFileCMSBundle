<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Checlou\FlatFileCMSBundle\Tests\Functional;

use Checlou\FlatFileCMSBundle\Tests\Functional\app\AppKernel;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class AbstractWebTestCase extends BaseWebTestCase
{
    public static function assertRedirect($response, $location)
    {
        self::assertTrue($response->isRedirect(), "Response is not a redirect, got:\n".(($p = strpos($response, '-->')) ? substr($response, 0, $p + 3) : $response));
        self::assertEquals('http://localhost'.$location, $response->headers->get('Location'));
    }

    public static function setUpBeforeClass(): void
    {
        static::deleteTmpDir();
    }

    public static function tearDownAfterClass(): void
    {
        static::deleteTmpDir();
    }

    protected static function deleteTmpDir()
    {
        if (!file_exists($dir = sys_get_temp_dir().'/'.static::getVarDir())) {
            return;
        }

        $fs = new Filesystem();
        $fs->remove($dir);
    }

    protected static function getKernelClass(): string
    {
        require_once __DIR__.'/app/AppKernel.php';

        return AppKernel::class;
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        $class = self::getKernelClass();

        if (!isset($options['test_case'])) {
            throw new \InvalidArgumentException('The option "test_case" must be set.');
        }

        return new $class(
            static::getVarDir(),
            $options['test_case'],
            $options['root_config'] ?? 'config.yml',
            $options['environment'] ?? strtolower(static::getVarDir().$options['test_case']),
            $options['debug'] ?? false
        );
    }

    protected static function getVarDir()
    {
        return 'SB'.substr(strrchr(static::class, '\\'), 1);
    }

    public function assertRequestGotSuccessResponse(Crawler $crawler, Response $response): void
    {
        $message = "Request failed with status code {$response->getStatusCode()}";
        if (count($crawler->filterXPath('//title')) > 0)
            $message .= ":\n".$crawler->filterXPath('//title')->text();
        else
            $message .= ":\n".$response->getContent();
        $this->assertTrue($response->isSuccessful(), $message);
    }

}