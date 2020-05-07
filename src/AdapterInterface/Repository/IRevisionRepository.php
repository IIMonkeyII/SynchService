<?php
/**
 * Created by PhpStorm.
 * User: monkey
 * Date: 19.10.19
 * Time: 21:26
 */

namespace App\Service\Synchronization\src\AdapterInterface\Repository;

use App\Service\Synchronization\src\AdapterInterface\Entity\INode;
use App\Service\Synchronization\src\AdapterInterface\Entity\IRevision;

interface IRevisionRepository
{
    public function getLastRevision($entityId): ?IRevision;

    public function getTokenLastRevision($entityId, INode $node): ?IRevision;

    public function save(IRevision $revision, $entityId);

    public function find($id): ?IRevision;

    public function fromSourceUpdatedOnly($entityId, IRevision $sourceRevision, INode $sourceNod);
}