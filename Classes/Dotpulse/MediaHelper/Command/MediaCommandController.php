<?php
namespace Dotpulse\MediaHelper\Command;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\Media\Domain\Model\AssetCollection;
use TYPO3\Media\Domain\Model\Tag;
use TYPO3\Media\Domain\Repository\TagRepository;
use TYPO3\Media\Domain\Repository\AssetCollectionRepository;
use TYPO3\Media\Domain\Repository\AssetRepository;
use TYPO3\Flow\Log\Backend\AnsiConsoleBackend;

/**
 * @Flow\Scope("singleton")
 */
class MediaCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

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

    /**
     * @Flow\Inject
     * @var AnsiConsoleBackend
     */
    protected $consoleBackend;

    /**
     * Show available tags and asset collections
     *
     * @return void
     */
    public function overviewCommand()
    {
        $this->consoleBackend->open();
        $this->consoleBackend->append('Show available tags and asset collections:', LOG_NOTICE);

        $collections = $this->assetCollectionRepository->findAll();
        /** @var AssetCollection $collection */
        foreach ($collections as $collection) {
            $this->consoleBackend->append('- '.$collection->getTitle());
            /** @var Tag $tag */
            foreach ($collection->getTags() as $tag) {
                $assets = $this->assetRepository->findByTag($tag, $collection);
                $this->consoleBackend->append('  - '.$tag->getLabel().' ('.$assets->count().' Assets)');
            }
        }

        $this->consoleBackend->append('- Tags without collection');
        $tags = $this->tagRepository->findAll();
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $assets = $this->assetRepository->findByTag($tag, $collection);
            $this->consoleBackend->append('  - '.$tag->getLabel().' ('.$assets->count().' Assets)');
        }

    }

    /**
     * Add Tag to Assets
     *
     * Usage examples..
     *
     * Add the Tag "Produktbild" to all assets:
     * ./flow media:addtag "Produktbild"
     *
     * Add assets in Collection "Eizo_Website" with Tag "Produktbild" to the Collection "Downloads_DE" and the Tag "Produktbild":
     * ./flow media:addtag --from-tag "Produktbild" --from-collection "Eizo_Website" --to-collection "Downloads_DE" "Produktbild"
     *
     * @param string $toTag The tag that gets added
     * @param string $toCollection The Collection that gets added
     * @param string $fromTag Process Assets with this tag
     * @param string $fromCollection Process Assets with this collection
     * @param boolean $dryRun Test the execution - default: false
     * @return void
     */
    public function addTagCommand($toTag, $toCollection = '', $fromTag = '', $fromCollection = '', $dryRun = false)
    {
        $this->consoleBackend->open();
        if ($dryRun) {
            $this->consoleBackend->append('DRY RUN', LOG_WARNING);
        }
        $this->consoleBackend->append('Search Assets with tag "' . $fromTag . '"' . (!empty($fromCollection) ? ' and collection "' . $fromCollection . '"' : '') . ' and add them to the tag "' . $toTag . '"' . (!empty($toCollection) ? ' and collection "' . $toCollection . '"' : '') . '.', LOG_NOTICE);

        /** @var Tag $fromTagObject */
        $fromTagObject = !empty($fromTag) ? $this->tagRepository->findOneByLabel($fromTag) : null;
        /** @var AssetCollection $fromCollectionObject */
        $fromCollectionObject = !empty($fromCollection) ? $this->assetCollectionRepository->findOneByTitle($fromCollection) : null;

        if (!is_null($fromTagObject)) {
            $assets = $this->assetRepository->findByTag($fromTagObject, $fromCollectionObject);
        }else{
            $assets = $this->assetRepository->findAll();
        }

        /** @var Tag $toTagObject */
        $toTagObject = !empty($toTag) ? $this->tagRepository->findOneByLabel($toTag) : null;
        /** @var AssetCollection $toCollectionObject */
        $toCollectionObject = !empty($toCollection) ? $this->assetCollectionRepository->findOneByTitle($toCollection) : null;

        if (is_null($toTagObject)) {
            $this->consoleBackend->append('ERROR: toTag is not existing!', LOG_ERR);
            return ;
        }

        $this->consoleBackend->append($assets->count().' assets to process..');

        /** @var Asset $asset */
        foreach ($assets as $asset) {
            $this->consoleBackend->append($asset->getIdentifier());
            if (!$dryRun) {
                $asset->addTag($toTagObject);
            }
            $this->consoleBackend->append(' -> add tag '.$toTagObject->getLabel());
            if (!is_null($toCollectionObject)) {
                $this->consoleBackend->append(' -> add collection '.$toCollectionObject->getTitle());
                if (!$dryRun) {
                    $assetCollection = $asset->getAssetCollections();
                    $assetCollection->add($toCollectionObject);
                    $asset->setAssetCollections($assetCollection);
                }
            }
            if (!$dryRun) {
                $this->assetRepository->update($asset);
            }
        }
        if (!$dryRun) {
            $this->persistenceManager->persistAll();
        }
    }

}
