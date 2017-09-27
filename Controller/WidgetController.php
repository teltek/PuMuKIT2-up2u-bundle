<?php

namespace Pumukit\Geant\WebTVBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Pumukit\WebTVBundle\Controller\WidgetController as BaseWidgetController;

class WidgetController extends BaseWidgetController
{
    /**
     * @Template()
     */
    public function tracksLanguagesAction()
    {
        $mmoRepo = $this->get('doctrine_mongodb.odm.document_manager')->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        $status = array(MultimediaObject::STATUS_PUBLISHED);

        $command = array();
        $command[] = array('$match' => array(
            'status' => array('$in' => $status),
            'tracks.hide' => false,
            'tags.cod' => 'PUCHWEBTV',
            ));
        $command[] = array('$group' => array(
            '_id' => '$tracks.language',
            'count' => array('$sum' => 1),
        ));
        $command[] = array('$sort' => array('_id' => -1));

        $aggregation = $mmoRepo->aggregate($command);

        return array('tracks_languages' => $aggregation);
    }

    /**
     * @Route("/filter_language", name="pumukit_webtv_filter_language")
     */
    public function updateSessionFilterLanguages(Request $request)
    {
        $session = $this->get('session');
        $session->set('filter_language', $request->request->get('track_language'));
        $route = ($request->headers->has('referer')) ? $request->headers->get('referer') : 'pumukit_webtv_index_index';

        return $this->redirect($route);
    }
}
