<?php

namespace Pumukit\Up2u\WebTVBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\WebTVBundle\Controller\SearchController as ParentController;

class SearchController extends ParentController
{
    /**
     * @Route("/searchmultimediaobjects/{tagCod}/{useTagAsGeneral}", defaults={"tagCod": null, "useTagAsGeneral": false}, name="pumukit_webtv_search_multimediaobjects")
     * @ParamConverter("blockedTag", class="PumukitSchemaBundle:Tag", options={"mapping": {"tagCod": "cod"}})
     * @Template("PumukitWebTVBundle:Search:index.html.twig")
     *
     * @param Request  $request
     * @param Tag|null $blockedTag
     * @param bool     $useTagAsGeneral
     *
     * @return array
     *
     * @throws \Exception
     */
    public function multimediaObjectsAction(Request $request, Tag $blockedTag = null, $useTagAsGeneral = false)
    {
        //Add translated title to breadcrumbs.
        $templateTitle = $this->container->getParameter('menu.search_title') ?: 'Multimedia objects search';
        $templateTitle = $this->get('translator')->trans($templateTitle);
        $this->get('pumukit_web_tv.breadcrumbs')->addList($blockedTag ? $blockedTag->getTitle() : $templateTitle, 'pumukit_webtv_search_multimediaobjects');

        // --- Get Tag Parent for Tag Fields ---
        $parentTag = $this->getParentTag();
        $parentTagOptional = $this->getOptionalParentTag();

        $aChildrenTagOptional = array();
        foreach ($parentTagOptional->getChildren() as $children) {
            $aChildrenTagOptional[$children->getTitle()] = $children;
        }
        ksort($aChildrenTagOptional);
        // --- END Get Tag Parent for Tag Fields ---

        // --- Get Variables ---
        $searchFound = $request->query->get('search');
        $tagsFound = $request->query->get('tags');
        $typeFound = $request->query->get('type');
        $durationFound = $request->query->get('duration');
        $startFound = $request->query->get('start');
        $endFound = $request->query->get('end');
        $yearFound = $request->query->get('year');
        $languageFound = $request->query->get('language');
        // --- END Get Variables --
        // --- Create QueryBuilder ---
        $queryBuilder = $this->createMultimediaObjectQueryBuilder();
        $queryBuilder = $this->searchQueryBuilder($queryBuilder, $searchFound);
        $queryBuilder = $this->geantTypeQueryBuilder($queryBuilder, $typeFound);
        $queryBuilder = $this->durationQueryBuilder($queryBuilder, $durationFound);
        $queryBuilder = $this->dateQueryBuilder($queryBuilder, $startFound, $endFound, $yearFound);
        $queryBuilder = $this->languageQueryBuilder($queryBuilder, $languageFound);
        $queryBuilder = $this->tagsQueryBuilder($queryBuilder, $tagsFound, $blockedTag, $useTagAsGeneral);
        $queryBuilder = $queryBuilder->sort('record_date', 'desc');
        // --- END Create QueryBuilder ---

        $request->attributes->set('searchCriteria', $queryBuilder->getQueryArray());

        // --- Execute QueryBuilder count --
        $countQuery = clone $queryBuilder;
        $totalObjects = $countQuery->count()->getQuery()->execute();
        // --- Execute QueryBuilder and get paged results ---
        $pagerfanta = $this->createPager($queryBuilder, $request->query->get('page', 1));

        // --- Query to get existing languages, years, types... ---
        $searchLanguages = $this->getMmobjsLanguages($queryBuilder, $languageFound);
        $searchYears = $this->getMmobjsYears($queryBuilder);
        $searchTypes = $this->getMmobjsGeantTypes($queryBuilder);
        $searchDuration = $this->getMmobjsDuration($queryBuilder);
        $searchTags = $this->getMmobjsTags($queryBuilder);

        // -- Init Number Cols for showing results ---
        $numberCols = $this->container->getParameter('columns_objs_search');

        // --- RETURN ---
        return array(
            'type' => 'multimediaObject',
            'template_title' => $templateTitle,
            'objects' => $pagerfanta,
            'parent_tag' => $parentTag,
            'parent_tag_optional' => $parentTagOptional,
            'children_tag_optional' => $aChildrenTagOptional,
            'tags_found' => $tagsFound,
            'number_cols' => $numberCols,
            'languages' => $searchLanguages,
            'blocked_tag' => $blockedTag,
            'types' => $searchTypes,
            'durations' => $searchDuration,
            'tags' => $searchTags,
            'search_years' => $searchYears,
            'total_objects' => $totalObjects,
        );
    }

    protected function geantTypeQueryBuilder($queryBuilder, $typeFound)
    {
        if ($typeFound != '') {
            $queryBuilder->field('properties.geant_type')->equals($typeFound);
        }

        return $queryBuilder;
    }

    protected function durationQueryBuilder($queryBuilder, $durationFound)
    {
        if ($durationFound != '') {
            if ($durationFound == '0') {
                $queryBuilder->field('duration')->equals(0);
            }
            if ($durationFound == '-5') {
                $queryBuilder->field('duration')->gt(0);
                $queryBuilder->field('duration')->lte(300);
            }
            if ($durationFound == '-10') {
                $queryBuilder->field('duration')->gt(300);
                $queryBuilder->field('duration')->lte(600);
            }
            if ($durationFound == '-30') {
                $queryBuilder->field('duration')->gt(600);
                $queryBuilder->field('duration')->lte(1800);
            }
            if ($durationFound == '-60') {
                $queryBuilder->field('duration')->gt(1800);
                $queryBuilder->field('duration')->lte(3600);
            }
            if ($durationFound == '+60') {
                $queryBuilder->field('duration')->gt(3600);
            }
        }

        return $queryBuilder;
    }

    protected function getMmobjsLanguages($queryBuilder = null, $languageFound = null)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $mmObjColl = $dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        $mmObjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $criteria = $dm->getFilterCollection()->getFilterCriteria($mmObjRepo->getClassMetadata());

        if ($queryBuilder) {
            $pipeline = array(
                array('$match' => $queryBuilder->getQueryArray()),
            );
        } else {
            $pipeline = array(
                array('$match' => array('status' => MultimediaObject::STATUS_PUBLISHED)),
            );
        }

        if ($criteria) {
            $pipeline[] = array('$match' => $criteria);
        }

        $pipeline[] = array('$unwind' => '$tracks');
        if ($languageFound) {
            $pipeline[] = array('$match' => array('tracks.language' => $languageFound));
        }

        $pipeline[] = array('$match' => array('tracks.tags' => 'display'));
        $pipeline[] = array('$group' => array('_id' => array('language' => '$tracks.language', 'mmoid' => '$_id')));

        $pipeline[] = array('$group' => array('_id' => '$_id.language', 'count' => array('$sum' => 1)));
        $pipeline[] = array('$sort' => array('_id' => 1));

        $languageResults = $mmObjColl->aggregate($pipeline);

        $languages = array();
        foreach ($languageResults as $language) {
            if (!isset($languages[$language['_id']])) {
                $languages[$language['_id']] = 0;
            }
            $languages[$language['_id']] += $language['count'];
        }

        return $languages;
    }

    protected function getMmobjsYears($queryBuilder = null)
    {
        return $this->getMmobjsFaceted(array('$year' => '$record_date'), $queryBuilder, $sort = -1);
    }

    protected function getMmobjsDuration($queryBuilder)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $mmObjColl = $dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        $mmObjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $criteria = $dm->getFilterCollection()->getFilterCriteria($mmObjRepo->getClassMetadata());

        if ($queryBuilder) {
            $pipeline = array(
                array('$match' => $queryBuilder->getQueryArray()),
            );
        } else {
            $pipeline = array(
                array('$match' => array('status' => MultimediaObject::STATUS_PUBLISHED)),
            );
        }

        if ($criteria) {
            $pipeline[] = array('$match' => $criteria);
        }

        $pipeline[] = array('$group' => array('_id' => '$duration', 'count' => array('$sum' => 1)));

        $facetedResults = $mmObjColl->aggregate($pipeline);
        $faceted = array(
            0 => 0,
            -5 => 0,
            -10 => 0,
            -30 => 0,
            -60 => 0,
            +60 => 0,
        );

        foreach ($facetedResults as $result) {
            if ($result['_id'] > 60 * 60) {
                $faceted[+60] += $result['count'];
            } elseif (($result['_id'] <= 60 * 60) and ($result['_id'] > 30 * 60)) {
                $faceted[-60] += $result['count'];
            } elseif (($result['_id'] <= 30 * 60) and ($result['_id'] > 10 * 60)) {
                $faceted[-30] += $result['count'];
            } elseif (($result['_id'] <= 10 * 60) and ($result['_id'] > 5 * 60)) {
                $faceted[-10] += $result['count'];
            } elseif (($result['_id'] <= 5 * 60) and ($result['_id'] > 0)) {
                $faceted[-5] += $result['count'];
            } else {
                $faceted[0] += $result['count'];
            }
        }

        return $faceted;
    }

    protected function getMmobjsTags($queryBuilder = null)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $mmObjColl = $dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        $mmObjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $criteria = $dm->getFilterCollection()->getFilterCriteria($mmObjRepo->getClassMetadata());

        if ($queryBuilder) {
            $pipeline = array(
                array('$match' => $queryBuilder->getQueryArray()),
            );
        } else {
            $pipeline = array(
                array('$match' => array('status' => MultimediaObject::STATUS_PUBLISHED)),
            );
        }

        if ($criteria) {
            $pipeline[] = array('$match' => $criteria);
        }

        $pipeline[] = array('$project' => array('_id' => '$tags.cod'));
        $pipeline[] = array('$unwind' => '$_id');
        $pipeline[] = array('$group' => array('_id' => '$_id', 'count' => array('$sum' => 1)));

        $facetedResults = $mmObjColl->aggregate($pipeline);
        $faceted = array();
        foreach ($facetedResults as $result) {
            $faceted[$result['_id']] = $result['count'];
        }

        return $faceted;
    }

    protected function getMmobjsGeantTypes($queryBuilder = null)
    {
        return $this->getMmobjsFaceted('$properties.geant_type', $queryBuilder);
    }

    protected function getMmobjsFaceted($idGroup, $queryBuilder = null, $sort = 1)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $mmObjColl = $dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        $mmObjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $criteria = $dm->getFilterCollection()->getFilterCriteria($mmObjRepo->getClassMetadata());

        if ($queryBuilder) {
            $pipeline = array(
                array('$match' => $queryBuilder->getQueryArray()),
            );
        } else {
            $pipeline = array(
                array('$match' => array('status' => MultimediaObject::STATUS_PUBLISHED)),
            );
        }

        if ($criteria) {
            $pipeline[] = array('$match' => $criteria);
        }

        $pipeline[] = array('$group' => array('_id' => $idGroup, 'count' => array('$sum' => 1)));
        $pipeline[] = array('$sort' => array('_id' => $sort));

        $facetedResults = $mmObjColl->aggregate($pipeline);
        $faceted = array();
        foreach ($facetedResults as $result) {
            $faceted[$result['_id']] = $result['count'];
        }

        return $faceted;
    }

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
