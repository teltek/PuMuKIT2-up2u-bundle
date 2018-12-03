<?php

namespace Pumukit\Up2u\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;

class ChannelsController extends Controller
{
    /**
    * @Route("/category/{category}", defaults={"category" = null})
    */
    public function multimediaObjectsAction($category, Request $request)
    {
        return $this->redirect($this->generateUrl('pumukit_webtv_search_multimediaobjects_category', array('categoryId' => $category)));
    }
}
