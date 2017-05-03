<?php

namespace Pumukit\Geant\WebTVBundle\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetaData;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;

class TracksLanguageFilter extends BsonFilter
{
    public function addFilterCriteria(ClassMetadata $targetDocument)
    {
        if ("Pumukit\SchemaBundle\Document\MultimediaObject" === $targetDocument->reflClass->name or "Pumukit\SchemaBundle\Document\Series" === $targetDocument->reflClass->name) {
            return $this->getMultimediaObjectCriteria();
        }
    }

    private function getMultimediaObjectCriteria()
    {
        $criteria = array();
        if (isset($this->parameters['tracks_language'])) {
            $criteria['$and'] = array(
                array('tracks.language' => array('$in' => $this->parameters['tracks_language'])),
            );
        }

        return $criteria;
    }
}
