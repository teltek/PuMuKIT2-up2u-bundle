<?php

namespace Pumukit\Up2u\WebTVBundle\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetaData;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;

class TracksLanguageFilter extends BsonFilter
{
    public function addFilterCriteria(ClassMetadata $targetDocument)
    {
        if ("Pumukit\SchemaBundle\Document\MultimediaObject" === $targetDocument->reflClass->name) {
            return $this->getMultimediaObjectCriteria();
        }
    }

    private function getMultimediaObjectCriteria()
    {
        if (isset($this->parameters['tracks_language'])) {
            return array('status' => 0, 'tracks.language' => array('$in' => $this->parameters['tracks_language']));
        }

        return array('status' => 0);
    }
}
