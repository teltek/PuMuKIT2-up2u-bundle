<?php

namespace Pumukit\Up2u\WebTVBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Pumukit\WebTVBundle\Controller\AnnouncesController as ParentController;

class AnnouncesController extends ParentController
{
    /**
     * @Route("/latestuploads", name="pumukit_webtv_announces_latestuploads")
     * @Template()
     */
    public function latestUploadsAction(Request $request)
    {
        $response = parent::latestUploadsAction($request);

        $limit = 20;
        $numberCols = $this->container->getParameter('columns_objs_announces');
        $announcesService = $this->get('pumukitschema.announce');
        $lastMms = $announcesService->getLast($limit);

        $response['last'] = $lastMms;
        $response['number_cols'] = $numberCols;
        return $response;
    }
}
