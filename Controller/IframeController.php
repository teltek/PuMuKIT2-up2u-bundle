<?php

namespace Pumukit\Geant\WebTVBundle\Controller;

use Pumukit\WebTVBundle\Controller\PlayerController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class IframeController extends PlayerController
{
    /**
     * @Template("PumukitWebTVBundle:MultimediaObject:iframeplayer.html.twig")
     */
    public function indexAction(MultimediaObject $multimediaObject, Request $request)
    {
        $iframeUrl = $multimediaObject->getProperty('iframe_url');
        $this->updateBreadcrumbs($multimediaObject);

        return array(
            'multimediaObject' => $multimediaObject,
            'iframe_url' => $iframeUrl, 
        );
    }
}
