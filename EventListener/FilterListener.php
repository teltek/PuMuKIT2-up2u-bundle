<?php

namespace Pumukit\Geant\WebTVBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\WebTVBundle\Controller\WebTVController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class FilterListener
{
    private $dm;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $req = $event->getRequest();
        $routeParams = $req->attributes->get('_route_params');
        $isFilterActivated = (!isset($routeParams['filter']) || $routeParams['filter']);

        /*
         * $controller passed can be either a class or a Closure.
         * This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         * From Symfony Docs: http://symfony.com/doc/current/cookbook/event_dispatcher/before_after_filters.html
         */
        $controller = $event->getController();
        if (!is_array($controller)) {
            return;
        }

        //@deprecated: PuMuKIT 2.2: This logic will be removed eventually. Please implement the interface WebTVBundleController to use the filter.
        $deprecatedCheck = (false !== strpos($req->attributes->get('_controller'), 'WebTVBundle'));

        if (($controller[0] instanceof WebTVController /*deprecated*/ || $deprecatedCheck)
            && $event->isMasterRequest()
                && $isFilterActivated) {
            if ($req->getSession()->has('filter_language')) {
                $configuration = $this->dm->getConfiguration();
                $configuration->addFilter('trackslanguagefilter', 'Pumukit\Geant\WebTVBundle\Filter\TracksLanguageFilter');
                $filtertrack = $this->dm->getFilterCollection()->enable('trackslanguagefilter');
                $filtertrack->setParameter('tracks_language', $req->getSession()->get('filter_language'));
            }
        }
    }
}
