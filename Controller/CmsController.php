<?php
/**
 * Created by PhpStorm.
 * User: lolozere
 * Date: 21/03/19
 * Time: 16:04
 */

namespace Checlou\FlatFileCMSBundle\Controller;

use Checlou\FlatFileCMSBundle\CMS\Page\Page;
use Checlou\FlatFileCMSBundle\CMS\Pages;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CmsController extends AbstractController
{

    /**
     * @var int
     * Pagination : nombre d'actulaités par page
     */
    private $nbParPage = 10;

    /**
     * Retourne les derniers articles en lien avec la page
     *
     * - Sur une page du CMS (hors page “répertoire”), on liste les 6 derniers articles de la catégorie à laquelle la page appartient.
     *
     * @param Pages $pages
     * @param Page|null $page
     * @param int $max
     *
     * @return array|null Tableau ayant pour clés category et posts
     * @throws \Exception
     */
    public function findRelatedPages(Pages $pages, ?Page $page = null, int $max = 6): array
    {
        $filters = ['type' => 'post'];

        /*
         * Recherche des articles en lien avec le parent de la page
         */
        if (!is_null($page) && !is_null($page->getParent())) {
            $filters['parent'] = $page->getParent();
            $filters['excluded_pages'] = [$page];
        } elseif(!is_null($page) && $page->getType() == Page::TYPE_PAGE) {
            // Par défaut pas de related sur les pages
            return [];
        }
        $posts = $pages->find($filters);

        return array_slice($posts, 0, $max);
    }

    /**
     * Display content of an item
     *
     * @param Page $page_content
     * @param Pages $pages
     * @param int|null $page_index
     * @param array $parameters
     * @return Response
     * @throws \Exception
     */
    protected function renderPage(Page $page_content, Pages $pages, int $page_index = null, array $parameters = array()): Response {
        $page_items = [];
        $pagination = [];
        if ($page_content->isDirectoryPage()) {
            $page_items = $pages->find(['parent' => $page_content->getSlug()]);
            $pagination['index'] = (is_null($page_index))?1:$page_index;
            $pagination['total_pages'] = ceil(count($page_items)/$this->nbParPage);
            $offset = $this->nbParPage*($pagination['index']-1);
            $page_items = array_slice($page_items, $offset, $this->nbParPage);
        } else {
            $related_pages = $this->findRelatedPages($pages, $page_content);
        }

        return $this->render(
            $parameters['template'] ?? '@CheclouFlatFileCMS/page.html.twig',
            ($parameters['template_vars'] ?? []) + array(
                'page' => $page_content,
                'childs' => $page_items,
                'pagination' => $pagination,
                'related_pages' => $related_pages ?? []
            )
        );

    }

    /**
     * Display content of an item
     *
     * @param $slug
     * @param Pages $pages
     * @param int|null $page_index
     * @param array $parameters
     * @return Response
     * @throws \Exception
     */
    public function page($slug, Pages $pages, int $page_index = null, array $parameters = array()): Response {
        $page_content = $pages->find(['slug' => $slug]);
        if (count($page_content)<=0) {
            throw $this->createNotFoundException(sprintf("CMS page not found with slug : %s", $slug));
        } elseif(($page_content = current($page_content)) instanceof Page && !$page_content->getHeaders()->visible) {
            throw $this->createNotFoundException(sprintf("CMS page not visible with slug : %s", $slug));
        }

        return $this->renderPage($page_content, $pages, $page_index, $parameters);
    }

    /**
     * Display content of an item
     *
     * @param $slug
     * @param Pages $pages
     * @param int|null $page_index
     * @param array $parameters
     * @return Response
     * @throws \Exception
     */
    public function preview($slug, Pages $pages, int $page_index = null, array $parameters = array()): Response {
        $page_content = $pages->find(['slug' => $slug, 'headers' => ['visible' => true]]);
        if (count($page_content)<=0) {
            throw $this->createNotFoundException(sprintf("CMS page not found with slug : %s", $slug));
        }
        return $this->renderPage($page_content, $pages, $page_index, $parameters);
    }

}