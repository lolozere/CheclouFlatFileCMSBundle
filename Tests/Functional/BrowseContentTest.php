<?php

namespace Checlou\FlatFileCMSBundle\Tests\Functional;

class BrowseContentTest extends AbstractWebTestCase {

    /**
     * Test to browse the content of the CMS with success
     *
     * @return void
     */
    public function testBrowseContent() {
        $client = static::createClient(['test_case' => 'Website', 'root_config' => 'base_config.yml', 'debug' => true]);
        $crawler = $client->request('GET', '/');

        // Home page
        $this->assertRequestGotSuccessResponse($crawler, $client->getResponse());
        $this->assertSelectorTextContains('h1', 'Homepage');

        // About page
        $crawler = $client->request('GET', $crawler->filter('a:contains("About")')->attr('href'));
        $this->assertRequestGotSuccessResponse($crawler, $client->getResponse());
        $this->assertSelectorTextContains('h1', 'About the site');

        // Homepage of blog with 2 articles
        $crawler = $client->request('GET', "/blog");
        $this->assertRequestGotSuccessResponse($crawler, $client->getResponse());
        $this->assertSelectorTextContains('h1', 'Blog');
        $this->assertCount(2, $crawler->filter('.cms-post-item'));

        // First article
        $crawler = $client->request('GET', $crawler->filter('.cms-post-item a')->last()->attr('href'));
        $this->assertRequestGotSuccessResponse($crawler, $client->getResponse());
        $this->assertSelectorTextContains('h1', 'A basic post');

        // Back to blog using the breadcrumb
        $crawler = $client->request('GET', $crawler->filter('.breadcrumb .breadcrumb-item:nth-child(2) a')->first()->attr('href'));
        $this->assertRequestGotSuccessResponse($crawler, $client->getResponse());
        $this->assertSelectorTextContains('h1', 'Blog');

        // Go to category folder
        $crawler = $client->request('GET', $crawler->filter('.cms-post-item .cms-post-category-link')->first()->attr('href'));
        $this->assertRequestGotSuccessResponse($crawler, $client->getResponse());
        $this->assertSelectorTextContains('h1', 'Markdown');

        // Go to article in category folder
        $crawler = $client->request('GET', $crawler->filter('.cms-post-item a')->last()->attr('href'));
        $this->assertRequestGotSuccessResponse($crawler, $client->getResponse());
        $this->assertSelectorTextContains('p', 'Markdown is great');
    }
}