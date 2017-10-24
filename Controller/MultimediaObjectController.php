<?php

namespace Pumukit\Geant\WebTVBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\WebTVBundle\Controller\MultimediaObjectController as ParentController;

class MultimediaObjectController extends ParentController
{
    public function preExecute(MultimediaObject $multimediaObject, Request $request, $secret = false)
    {
        if ($multimediaObject->getProperty('iframeable') === true) {
            $this->dispatchViewEvent($multimediaObject);

            return $this->forward('PumukitGeantWebTVBundle:Iframe:index', array('request' => $request, 'multimediaObject' => $multimediaObject));
        } elseif ($multimediaObject->getProperty('redirect') === true) {
            $this->dispatchViewEvent($multimediaObject);
            $redirectUrl = $multimediaObject->getProperty('redirect_url');
            if (!$redirectUrl) {
                throw $this->createNotFoundException();
            }
            if (strpos($redirectUrl, '://') === false) {
                $redirectUrl = 'http://'.$redirectUrl;
            }

            return $this->redirect($redirectUrl);
        }
    }

    /**
     * @Route("/iframe/{id}", name="pumukit_webtv_multimediaobject_iframe" )
     * @Template()
     */
    public function iframeAction(MultimediaObject $multimediaObject, Request $request)
    {
        if ($multimediaObject->getProperty('iframeable') === true) {
            $iframeUrl = $multimediaObject->getProperty('iframe_url');

            return $this->redirect($iframeUrl);
        }

        return parent::iframeAction($multimediaObject, $request);
    }

    public function dispatchViewEvent(MultimediaObject $multimediaObject, Track $track = null)
    {
        $event = new ViewedEvent($multimediaObject, $track);
        $this->get('event_dispatcher')->dispatch(BasePlayerEvents::MULTIMEDIAOBJECT_VIEW, $event);
    }
}
