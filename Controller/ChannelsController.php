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
        return $this->forward('PumukitWebTVBundle:Search:multimediaObjects', array('request' => $request, 'blockedTag' => null, 'useTagAsGeneral' => false, 'categoryId' => null));
    }
}
