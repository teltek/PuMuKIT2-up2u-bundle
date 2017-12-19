<?php

namespace Pumukit\Up2u\WebTVBundle\Controller;

use Pumukit\WebTVBundle\Controller\SearchController as ParentController;

class SearchController extends ParentController
{
    protected function createMultimediaObjectQueryBuilder()
    {
        $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $request = $this->get('request_stack')->getMasterRequest();

        if ('/pumoodle/searchmultimediaobjects' == $request->getPathInfo()) {
            $dm = $this->get('doctrine_mongodb.odm.document_manager');
            $dm->getFilterCollection()->disable('frontend');
            $queryBuilder = $repo->createQueryBuilder();
            $queryBuilder->field('status')->equals(0);
            $queryBuilder->field('properties.redirect')->equals(false);
        } else {
            $queryBuilder = $repo->createStandardQueryBuilder();
        }

        return $queryBuilder;
    }
}
