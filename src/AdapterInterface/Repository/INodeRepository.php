<?php
/**
 * Created by PhpStorm.
 * User: monkey
 * Date: 19.10.19
 * Time: 21:26
 */

namespace App\Service\Synchronization\src\AdapterInterface\Repository;


use App\Service\Synchronization\src\AdapterInterface\Entity\INode;

interface INodeRepository
{
    /**
     * @param integer $entityId
     * @return INode[]
     */
    public function getEntityNodes($entityId);
}