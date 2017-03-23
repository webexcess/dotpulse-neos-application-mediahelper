<?php
namespace Dotpulse\MediaHelper\Eel\Helper;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\Media\Domain\Model\AssetCollection;
use TYPO3\Media\Domain\Model\Tag;
use TYPO3\Media\Domain\Repository\TagRepository;
use TYPO3\Media\Domain\Repository\AssetCollectionRepository;
use TYPO3\Media\Domain\Repository\AssetRepository;

/**
 * Class MediaHelper
 *
 * TYPO3:
 *   TypoScript:
 *     defaultContext:
 *       'Dotpulse.MediaHelper': 'Dotpulse\MediaHelper\Eel\Helper\MediaHelper'
 *
 * assets = ${Dotpulse.MediaHelper.getAssetsByAssetCollectionTitlesAndTagLabels(['Collection A', 'Collection B'], ['Tag 1', 'Tag 2'])}
 *
 * @package Dotpulse\MediaHelper\Eel\Helper
 */
class MediaHelper implements ProtectedContextAwareInterface {

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var TagRepository
     */
    protected $tagRepository;

    /**
     * @Flow\Inject
     * @var AssetCollectionRepository
     */
    protected $assetCollectionRepository;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    public function allowsCallOfMethod($methodName)
    {
        return true;
    }

    /**
     * Return something useful
     *
     * @return array
     */
    public function getAssetsByAssetCollectionTitlesAndTagLabels($assetCollectionTitles = array(), $tagLabels = array()) {
        $assetsResult = array();

        $filterByAssetCollections = array();
        if (count($assetCollectionTitles)<=0) {
            $assetCollections = $this->assetCollectionRepository->findAll();
            /** @var AssetCollection $assetCollection */
            foreach ($assetCollections as $assetCollection) {
                $filterByAssetCollections[$assetCollection->getTitle()] = $assetCollection;
            }
        }else {
            foreach ($assetCollectionTitles as $assetCollectionTitle) {
                /** @var AssetCollection $assetCollection */
                $assetCollection = $this->assetCollectionRepository->findOneByTitle($assetCollectionTitle);
                if ($assetCollection) {
                    $filterByAssetCollections[$assetCollectionTitle] = $assetCollection;
                }
            }
        }
        $filterByTags = array();
        foreach ($tagLabels as $tagLabel) {
            /** @var Tag $tag */
            $tag = $this->tagRepository->findOneByLabel($tagLabel);
            if ($tag) {
                $filterByTags[$tagLabel] = $tag;
            }
        }

        /** @var AssetCollection $assetCollection */
        foreach ($filterByAssetCollections as $assetCollection) {
            /** @var Asset $asset */
            foreach ($assetCollection->getAssets() as $asset) {
                /** @var Tag $tag */
                foreach ($asset->getTags() as $tag) {
                    if (array_key_exists($tag->getLabel(), $filterByTags) && !array_key_exists($asset->getIdentifier(), $assetsResult)) {
                        $assetsResult[$asset->getIdentifier()] = $asset;
                    }
                }
            }
        }

        return $assetsResult;
    }

}
