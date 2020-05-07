<?php
/**
 * Created by PhpStorm.
 * User: monkey
 * Date: 17.10.19
 * Time: 13:40
 */

namespace App\Service\Synchronization\src;

use App\Service\Synchronization\src\AdapterInterface\Entity\INode;
use App\Service\Synchronization\src\AdapterInterface\Entity\IRevision;
use App\Service\Synchronization\src\AdapterInterface\Factory\IRevisionFactory;
use App\Service\Synchronization\src\AdapterInterface\ITransport;
use App\Service\Synchronization\src\AdapterInterface\Repository\INodeRepository;
use App\Service\Synchronization\src\AdapterInterface\Repository\IRevisionRepository;
use App\Service\Synchronization\src\Comparator\Comparator;

class SynchronizationService
{
    /** @var ITransport */
    private $transport;

    /** @var INodeRepository */
    private $nodeRepository;

    /** @var IRevisionRepository */
    private $revisionRepository;

    /** @var IRevisionFactory */
    private $revisionFactory;

    /**
     * SynchronizationService constructor.
     * @param ITransport $transport
     * @param INodeRepository $nodeRepository
     * @param IRevisionRepository $revisionRepository
     * @param IRevisionFactory $revisionFactory
     */
    public function __construct(
        ITransport $transport,
        INodeRepository $nodeRepository,
        IRevisionRepository $revisionRepository,
        IRevisionFactory $revisionFactory
    ) {
        $this->transport = $transport;
        $this->nodeRepository = $nodeRepository;
        $this->revisionRepository = $revisionRepository;
        $this->revisionFactory = $revisionFactory;
    }

    /**
     * @param int $entityId
     * @param array $revisionData
     * @param null $sourceRevisionId
     * @param INode $sourceNode
     * @return IRevision
     * @throws Exception\ComparatorException
     * @throws Exception\IntersectionException
     * @throws Exception\MergeException
     */
    public function createRevision(int $entityId, array $revisionData, $sourceRevisionId = null, INode $sourceNode)
    {
        $updatedRevision = $this->revisionFactory->buildRevision($revisionData);
        $lastRevision = $this->revisionRepository->getLastRevision($entityId);

        if($sourceRevisionId) {
            $sourceRevision = $this->revisionRepository->find($sourceRevisionId);
            $tokenLastRevision = $this->revisionRepository->getTokenLastRevision($entityId, $sourceNode);

            if($tokenLastRevision && $tokenLastRevision->getId() > $sourceRevision->getId()) {
                $sourceRevision = $tokenLastRevision;
            }
        } else {
            $sourceRevision = $this->revisionFactory->buildRevision([]);
        }

        if(empty($lastRevision)) {
            $updatedRevision->setNodeSource($sourceNode);
            $this->revisionRepository->save($updatedRevision, $entityId);

            $this->send($updatedRevision, $entityId, $sourceNode);

            return $updatedRevision;
        }

        $updatedRevision->setSource($sourceRevision);

        $comparator = new Comparator();

        $diffUpdate = $comparator->buildDiff($sourceRevision, $updatedRevision);
        $diffLag = $comparator->buildDiff($sourceRevision, $lastRevision);

        if($diffUpdate->isEmpty() || $diffUpdate->isEqualTo($diffLag)) {
            return $lastRevision;
        }

        $comparator->buildUnion($diffUpdate, $diffLag);

        $newRevision = $this->revisionFactory->buildRevision($lastRevision->getData());
        $newRevision->setSource($sourceRevision);
        $comparator->merge($diffUpdate, $newRevision);
        $newRevision->setNodeSource($sourceNode);
        $this->revisionRepository->save($newRevision, $entityId);

        $this->send($newRevision, $entityId, $sourceNode);
        return $newRevision;
    }

    private function send(IRevision $newRevision, $entityId, INode $sourceNode)
    {
        $nodes = $this->nodeRepository->getEntityNodes($entityId);

        foreach ($nodes as $node) {
            if($node === $sourceNode
                && $this->revisionRepository->fromSourceUpdatedOnly($entityId, $newRevision->getSource(), $sourceNode)) {
                continue;
            }

            $this->transport->send($node, $newRevision);
        }
    }
}