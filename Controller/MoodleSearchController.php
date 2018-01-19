<?php

namespace Pumukit\Up2u\WebTVBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\Up2u\WebTVBundle\Controller\SearchController;

/**
 * @Route("/pumoodle")
 */
class MoodleSearchController extends SearchController
{
    /**
     * @Route("/searchmultimediaobjects/{tagCod}/{useTagAsGeneral}", defaults={"tagCod": null, "useTagAsGeneral": false})
     * @ParamConverter("blockedTag", class="PumukitSchemaBundle:Tag", options={"mapping": {"tagCod": "cod"}})
     * @Template("PumukitUp2uWebTVBundle:MoodleSearch:index.html.twig")
     */
    public function searchAction(Request $request, Tag $blockedTag = null, $useTagAsGeneral = false)
    {
        return parent::multimediaObjectsAction($request, $blockedTag, $useTagAsGeneral);
    }
}
