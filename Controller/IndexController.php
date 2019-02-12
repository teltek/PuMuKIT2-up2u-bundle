<?php

namespace Pumukit\Up2u\WebTVBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Pumukit\WebTVBundle\Controller\IndexController as ParentController;

class IndexController extends ParentController
{
    /**
     * @Route("/", name="pumukit_webtv_index_index")
     * @Template()
     */
    public function indexAction()
    {
        return $this->redirectToRoute('pumukit_webtv_search_multimediaobjects');
    }

    /**
     * @Template()
     */
    public function recentlyaddedAction()
    {
        $limit = $this->container->getParameter('limit_objs_recentlyadded');
        $numberCols = $this->container->getParameter('columns_objs_announces');
        $templateTitle = $this->container->getParameter('menu.announces_title');

        $this->get('pumukit_web_tv.breadcrumbs')->addList($templateTitle, 'pumukit_webtv_announces_latestuploads');

        $announcesService = $this->get('pumukitschema.announce');
        $lastMms = $announcesService->getLast($limit);

        return array('template_title' => $templateTitle,
                     'last' => $lastMms,
                     'number_cols' => $numberCols, );
    }
}
