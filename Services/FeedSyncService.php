<?php
namespace Pumukit\Geant\WebTVBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Session\Session;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\TagService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\MultimediaObjectPicService;


/**
*  Service that iterates over the FeedSyncClientService responses, it processes them using the FeedProcesserService and then inserts/updates the object into the database.
*
*/
class FeedSyncService
{
    private $factoryService;
    private $tagService;
    private $personService;
    private $feedClientService;
    private $feedProcesserService;
    private $seriesRepo;
    private $mmobjRepo;
    private $tagRepo;
    private $personRepo;
    private $roleRepo;
    private $dm;

    private $providerRootTag;
    private $webTVTag;

    public function __construct(FactoryService $factoryService, TagService $tagService, PersonService $personService, MultimediaObjectPicService $mmsPicService, FeedSyncClientService $feedClientService,
    FeedProcesserService $feedProcesserService,  DocumentManager $dm)
    {
        //Schema Services
        $this->factoryService = $factoryService;
        $this->tagService = $tagService;
        $this->personService = $personService;
        $this->mmsPicService = $mmsPicService;
        //Geant Sync Services
        $this->feedClientService = $feedClientService;
        $this->feedProcesserService = $feedProcesserService;
        $this->dm = $dm;
        $this->init();
    }
    public function init()
    {
        $this->seriesRepo = $this->dm->getRepository('PumukitSchemaBundle:Series');
        $this->mmobjRepo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->tagRepo = $this->dm->getRepository('PumukitSchemaBundle:Tag');
        $this->personRepo = $this->dm->getRepository('PumukitSchemaBundle:Person');
        $this->roleRepo = $this->dm->getRepository('PumukitSchemaBundle:Role');
        $this->providerRootTag = $this->tagRepo->findOneByCod('PROVIDER');
        if(!isset($this->providerRootTag)) {
            $newTag = new Tag();
            $newTag->setParent($this->tagRepo->findOneByCod('ROOT'));
            $newTag->setCod('PROVIDER');
            $newTag->setMetatag(true);
            $newTag->setDisplay(false);
            $newTag->setTitle('Provider');
            $this->dm->persist($newTag);
            $this->dm->flush();
            $this->providerRootTag = $newTag;
        }
        $this->webTVTag = $this->tagRepo->findOneByCod('PUCHWEBTV');
        if(!isset($this->webTVTag)) {
            throw new \Exception('Tag: PUCHWEBTV does not exists. Did you initialize the repository? (pumukit:init:repo)');
        }
    }

    public function sync($limit = null)
    {
        $terenaGenerator = $this->feedClientService->getFeed( $limit );
        foreach( $terenaGenerator as $terena) {
            try {
                $parsedTerena = $this->feedProcesserService->process( $terena );
            } catch (\Exception $e) {
                //Log exception error.
                echo "\nPARSING GENERATOR EXCEPTION:\n$e\n";
                continue;
            }
            try {
                $this->syncMmobj($parsedTerena);
            }
            catch (\Exception $e) {
                echo "\nSYNC GENERATOR EXCEPTION:\n$e\n";
                continue;
            }
        }
    }

    public function syncMmobj( $parsedTerena )
    {
        $factory = $this->factoryService;
        $mmobj = $this->mmobjRepo->createQueryBuilder()
        ->field('properties.geant_id')
        ->equals($parsedTerena['identifier'])
        ->getQuery()
        ->getSingleResult();
        //We assume the 'provider' property of a feed won't change for the same Geant Feed Resource.
        //If it changes, the mmobj would keep it's original provider.
        if(!isset($mmobj)) {
            $series = $this->seriesRepo->createQueryBuilder()
            ->field('properties.geant_provider')
            ->equals($parsedTerena['provider'])
            ->getQuery()
            ->getSingleResult();
            if(!isset($series)) {
                $series = $factory->createSeries();
                $series->setProperty('geant_provider',$parsedTerena['provider']);
                $series->setTitle($parsedTerena['provider']);
            }
            $mmobj = $factory->createMultimediaObject($series);
            $mmobj->setProperty('geant_id', $parsedTerena['identifier']);

            //Add 'provider' tag
            $providerTag = $this->tagRepo->findOneByCod($parsedTerena['provider']);
            if(!isset($providerTag)) {
                $providerTag = new Tag();
                $providerTag->setParent($this->providerRootTag);
                $providerTag->setCod($parsedTerena['provider']);
                $providerTag->setTitle($parsedTerena['provider']);
                $providerTag->setDisplay(true);
                $providerTag->setMetatag(false);
                $this->dm->persist($providerTag);
                $this->dm->flush();
            }
            $this->tagService->addTagToMultimediaObject($mmobj, $providerTag->getId(), true);
        }
        //PUBLISH
        $mmobj->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $this->tagService->addTagToMultimediaObject($mmobj, $this->webTVTag->getId(), true);

        //METADATA
        $this->syncMetadata($mmobj, $parsedTerena);

        //TAGS
        $this->syncTags($mmobj, $parsedTerena);

        //PEOPLE
        $this->syncPeople($mmobj, $parsedTerena);

        //TRACK
        $this->syncTrack($mmobj, $parsedTerena);

        //THUMBNAIL
        $this->syncThumbnail($mmobj, $parsedTerena);

        //SAVE CHANGES
        $this->dm->persist($mmobj);
        $this->dm->flush();
    }

    public function syncMetadata(MultimediaObject $mmobj, $parsedTerena)
    {
        $mmobj->setTitle($parsedTerena['title']);
        $mmobj->setDescription($parsedTerena['description']);
        foreach($parsedTerena['keywords'] as $keyword) {
            $mmobj->setKeyword($keyword);
        }
        $mmobj->setLicense($parsedTerena['license']);
        $mmobj->setCopyright($parsedTerena['copyright']);
        $mmobj->setPublicDate($parsedTerena['public_date']);
        $mmobj->setRecordDate($parsedTerena['record_date']);
    }

    public function syncTags(MultimediaObject $mmobj, $parsedTerena)
    {
        foreach($parsedTerena['tags'] as $parsedTag) {
            $tag = $this->tagRepo->findOneByCod($parsedTag);//First we search by code on the database (it should be iTunesU, but could be other)

            if(!isset($tag))  //Second we search by title on the database (again, it should be iTunesU, but could be other)
            $tag = $this->tagRepo->findOneByTitle($parsedTag);

            if(!isset($tag))  //Now we start getting tricky. We search the cod, but adding 'U' (It should be UNESCO)
            $tag = $this->tagRepo->findOneByCod(sprintf('U%s',$parsedTag));

            if(!isset($tag)) { //If we can't find it here, all hope is lost. We log it and continue.
                echo "\n".sprintf('Warning: The tag with cod/title %s from the Feed ID:%s does not exist on PuMuKIT',$parsedTag,$parsedTerena['identifier']);
                continue;
            }

            //If the tag turned out to be from UNESCO, we try to add the iTunesU mapped tag
            if($tag->isDescendantOfByCod('UNESCO')) {
                $mappedItunesTags = $this->feedProcesserService->mapCodeToItunes(sprintf('U%s',substr($parsedTag,0,3)));
                foreach($mappedItunesTags as $itunesTag) {
                    $iTag = $this->tagRepo->findOneByCod($itunesTag);
                    if(!isset($iTag)) {
                        throw new \Exception(sprintf('Error! The parsed iTunes tag with code: %s  doesnt exists on PuMuKIT. Did you initialize the iTunes repo?'));
                    }
                    $this->tagService->addTagToMultimediaObject($mmobj, $iTag->getId(), false);
                }
            }
            $this->tagService->addTagToMultimediaObject($mmobj, $tag->getId(), false);
        }
    }

    public function syncPeople(MultimediaObject $mmobj, $parsedTerena)
    {
        foreach( $parsedTerena['people'] as $contributor) {
            $person = $this->personRepo->findOneByName($contributor['name']);
            if(!isset($person)) { //If the person doesn't exist, create a new one.
                $person = new Person();
                $person->setName($contributor['name']);
                $this->personService->savePerson($person);
            }

            $role = $this->roleRepo->findOneByCod($contributor['role']);
            if(!isset($role))  //Workaround for PuMuKIT. The 'Cod' field is not consistent, some are lowercase, some are ucfirst
            $role = $this->roleRepo->findOneByCod(ucfirst($contributor['role']));

            if(!isset($role)) { //If the role doesn't exist, use 'Participant'.
                $role = $this->roleRepo->findOneByCod('Participant'); // <-- This cod is ucfirst, but others are lowercase.
            }

            $this->personService->createRelationPerson($person, $role, $mmobj);
        }
    }

    public function syncTrack(MultimediaObject $mmobj, $parsedTerena)
    {
        $url = $parsedTerena['track_url'];
        $urlExtension = pathinfo((parse_url($parsedTerena['track_url'])['path']), PATHINFO_EXTENSION);
        //If the url is an url to an iframe
        if( $urlExtension == 'mp4' || $urlExtension == 'mp3' ) {
            $track = $mmobj->getTrackWithTag('geant_track');
            if(!isset($track)) {
                $track = new Track();
                $mmobj->addTrack($track);
            }

            $track->setLanguage($parsedTerena['language']);
            $track->setDuration($parsedTerena['track_duration']);
            $track->setVcodec($parsedTerena['track_format']);
            $track->setPath($url);
            $track->setUrl($url);
            $track->addTag('display');
            $track->addTag('geant_track');
            $this->dm->persist($track);
        }
        else {
            $mmobj->setProperty('opencast', true); //Workaround to prevent editing the Schema Filter for now.
            $mmobj->setProperty('iframeable', true);
            $mmobj->setProperty('iframe_url', $url);
        }
    }

    public function syncThumbnail(MultimediaObject $mmobj, $parsedTerena)
    {
        $url = $parsedTerena['thumbnail'];
        $pics = $mmobj->getPics();
        if (0 === count($pics)) {
            $mmobj = $this->mmsPicService->addPicUrl($mmobj, $url);
        } else {
            foreach($pics as $pic) break; //Woraround to get the first element.
            $pic->setUrl($url);
            $this->dm->persist($pic);
        }
    }
}