<?php

namespace Pumukit\Geant\WebTVBundle\Controller;

use Pumukit\WebTVBundle\Controller\PlayerController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class IframeController extends PlayerController
{
    /**
     * @Template("PumukitWebTVBundle:MultimediaObject:iframeplayer.html.twig")
     */
    public function indexAction( MultimediaObject $multimediaObject, Request $request ){
        $iframeUrl = $multimediaObject->getProperty('iframe_url');
        $this->updateBreadcrumbs($multimediaObject);
        return array('multimediaObject' => $multimediaObject,
                     'iframe_url' => $iframeUrl);
    }

    /**
     * @Route("/iframe/{id}", name="pumukit_webtv_multimediaobject_iframe" )
     * @Template()
     */
    public function iframeAction(MultimediaObject $multimediaObject, Request $request)
    {
        if ($multimediaObject->getProperty('iframeable') === true ) {
            $iframeUrl = $multimediaObject->getProperty('iframe_url');
            return $this->redirect($iframeUrl);
        }
        return parent::iframeAction($multimediaObject, $request);
    }
}
