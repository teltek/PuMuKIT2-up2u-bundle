<?php

namespace Pumukit\Up2u\WebTVBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\WebTVBundle\Controller\SeriesController as ParentController;

class SeriesController extends ParentController
{
    /**
     * @Route("/series/{id}", name="pumukit_webtv_series_index")
     * @Template("PumukitWebTVBundle:Series:index.html.twig")
     */
    public function indexAction(Series $series, Request $request)
    {
        $geantProvider = $series->getProperty('geant_provider');
        $linkService = $this->get('pumukit_web_tv.link_service');

        return $this->redirect($linkService->generatePathToTag($geantProvider, false));
    }
}
