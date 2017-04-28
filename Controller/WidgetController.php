<?php

namespace Pumukit\Geant\WebTVBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Session\Session;
use Pumukit\WebTVBundle\Controller\WidgetController as BaseWidgetController;

class WidgetController extends BaseWidgetController
{
    /**
     * @Template()
     */
    public function menuAction()
    {
        $channels = $this->get('doctrine_mongodb')->getRepository('PumukitLiveBundle:Live')->findAll();
        $selected = $this->container->get('request_stack')->getMasterRequest()->get('_route');

        $menuStats = $this->container->getParameter('menu.show_stats');
        $homeTitle = $this->container->getParameter('menu.home_title');
        $announcesTitle = $this->container->getParameter('menu.announces_title');
        $searchTitle = $this->container->getParameter('menu.search_title');
        $mediatecaTitle = $this->container->getParameter('menu.mediateca_title');
        $categoriesTitle = $this->container->getParameter('menu.categories_title');

        return array('live_channels' => $channels, 'menu_selected' => $selected, 'menu_stats' => $menuStats,
        'home_title' => $homeTitle,
        'announces_title' => $announcesTitle,
        'search_title' => $searchTitle,
        'mediateca_title' => $mediatecaTitle,
        'categories_title' => $categoriesTitle,
        'menu_stats' => $menuStats, );
    }

    /**
     * @Template()
     */
    public function breadcrumbsAction()
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');

        return array('breadcrumbs' => $breadcrumbs->getBreadcrumbs());
    }

    /**
     * @Template()
     */
    public function statsAction()
    {
        $mmRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $seriesRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:series');

        $counts = array('series' => $seriesRepo->countPublic(),
                        'mms' => $mmRepo->count(),
                        'hours' => bcdiv($mmRepo->countDuration(), 3600, 2), );

        return array('counts' => $counts);
    }

    /**
     * @Template()
     */
    public function contactAction()
    {
        return array();
    }

    /**
     * @Template("PumukitWebTVBundle:Widget:upcomingliveevents.html.twig")
     */
    public function upcomingLiveEventsAction()
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $eventRepo = $dm->getRepository('PumukitLiveBundle:Event');
        $events = $eventRepo->findFutureAndNotFinished(5);

        return array('events' => $events);
    }

    /**
     * @Template()
     */
    public function languageselectAction()
    {
        $array_locales = $this->container->getParameter('pumukit2.locales');
        if (count($array_locales) <= 1) {
            return new Response('');
        }

        return array('languages' => $array_locales);
    }

    /**
     * @Template()
     */
    public function tracksLanguagesAction()
    {
        $mmoRepo = $this->get('doctrine_mongodb.odm.document_manager')->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        $status = array(MultimediaObject::STATUS_PUBLISHED);

        $command = array();
        $command[] = array('$match' => array(
            'status' => array('$in' => $status),
            'tracks.hide' => false,
            'tags.cod' => 'PUCHWEBTV',
            ));
        $command[] = array('$group' => array(
            '_id' => '$tracks.language',
            'count' => array('$sum' => 1),
        ));
        $command[] = array('$sort' => array('_id' => -1));

        $aggregation = $mmoRepo->aggregate($command);

        return array('tracks_languages' => $aggregation);
    }

    /**
     * @Route("/filter_language", name="pumukit_webtv_filter_language")
     */
    public function updateSessionFilterLanguages(Request $request)
    {
        $session = $this->get('session');
        $session->set('filter_language', $request->request->get('track_language'));
        $route = ($request->request->has('referer')) ? $request->request->has('referer') : 'pumukit_webtv_index_index';

        return $this->redirectToRoute($route);
    }
}
