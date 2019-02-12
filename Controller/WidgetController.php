<?php

namespace Pumukit\Up2u\WebTVBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Pumukit\WebTVBundle\Controller\WidgetController as BaseWidgetController;

class WidgetController extends BaseWidgetController
{
    /**
     * No execute Live and Event queries.
     */
    protected function getMenuParameters()
    {
        $channels = array();
        $events = array();
        $selected = $this->container->get('request_stack')->getMasterRequest()->get('_route');
        $request = $this->container->get('request_stack')->getMasterRequest();

        $menuStats = $this->container->getParameter('menu.show_stats');
        $homeTitle = $this->container->getParameter('menu.home_title');
        $announcesTitle = $this->container->getParameter('menu.announces_title');
        $searchTitle = $this->container->getParameter('menu.search_title');
        $mediatecaTitle = $this->container->getParameter('menu.mediateca_title');
        $categoriesTitle = $this->container->getParameter('menu.categories_title');
        $repositories = array();
        // --- Get Tag Parent for Tag Fields ---
        $parentTag = $this->getParentTag();
        $parentTagOptional = $this->getOptionalParentTag();

        $repositories = array();
        if ($parentTagOptional) {
            foreach ($parentTagOptional->getChildren() as $children) {
                $repositories[$children->getTitle()] = $children;
            }
            ksort($repositories);
        }
        // --- END Get Tag Parent for Tag Fields ---


        return array(
            'live_events' => $events,
            'live_channels' => $channels,
            'menu_selected' => $selected,
            'menu_stats' => $menuStats,
            'home_title' => $homeTitle,
            'announces_title' => $announcesTitle,
            'search_title' => $searchTitle,
            'mediateca_title' => $mediatecaTitle,
            'categories_title' => $categoriesTitle,
            'repositories' => $repositories,
            'request' => $request,
        );
    }

    /**
     * @Template()
     */
    public function tracksLanguagesAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $mmObjColl = $dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        $mmObjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $criteria = $dm->getFilterCollection()->getFilterCriteria($mmObjRepo->getClassMetadata());

        $command = array();

        $requestStack = $this->container->get('request_stack');
        $masterRequest = $requestStack->getMasterRequest();

        unset($criteria['tracks.language']);

        $criteria = $masterRequest->attributes->get('searchCriteria', $criteria);
        if ($criteria) {
            $command[] = array(
                '$match' => $criteria,
            );
        }
        $command[] = array(
            '$group' => array(
                '_id' => '$tracks.language',
                'count' => array('$sum' => 1),
            ),
        );
        $command[] = array(
            '$sort' => array('_id' => -1),
        );

        $aggregation = $mmObjColl->aggregate($command, array('cursor' => array()));

        return array('tracks_languages' => $aggregation);
    }

    /**
     * @Route("/filter_language", name="pumukit_webtv_filter_language")
     */
    public function updateSessionFilterLanguages(Request $request)
    {
        $session = $this->get('session');
        $session->set('filter_language', $request->request->get('track_language'));
        $route = ($request->headers->has('referer')) ? $request->headers->get('referer') : 'pumukit_webtv_index_index';

        return $this->redirect($route);
    }

    protected function getParentTag()
    {
        $tagRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag');
        $searchByTagCod = $this->container->getParameter('search.parent_tag.cod');

        $parentTag = $tagRepo->findOneByCod($searchByTagCod);
        if (!isset($parentTag)) {
            throw new \Exception(sprintf('The parent Tag with COD:  \' %s  \' does not exist. Check if your tags are initialized and that you added the correct \'cod\' to parameters.yml (search.parent_tag.cod)', $searchByTagCod));
        }

        return $parentTag;
    }

    protected function getOptionalParentTag()
    {
        $tagRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag');

        $searchByTagCod = $this->container->getParameter('search.parent_tag_2.cod');
        $parentTagOptional = null;
        if ($searchByTagCod) {
            $parentTagOptional = $tagRepo->findOneByCod($searchByTagCod);
        }

        return $parentTagOptional;
    }
}
