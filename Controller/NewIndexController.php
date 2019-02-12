<?php

namespace Pumukit\Up2u\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/*
 * TODO: ONCE IT IS CONFIRM THIS NEW INDEX SHOULD REPLACE THE OLD ONE, REMOVE THIS CONTROLLER
 */
class NewIndexController extends IndexController
{
    /**
     * @Route("/newindex", name="pumukit_webtv_newindex_index")
     * @Template()
     */
    public function indexAction()
    {
        if ($this->getRequest()->query->has('search')) {
            return $this->redirect($this->generateUrl('pumukit_webtv_search_multimediaobjects', $this->getRequest()->query->all()));
        }

        $this->get('pumukit_web_tv.breadcrumbs')->reset();

        return array();
    }
}
