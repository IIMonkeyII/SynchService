<?php
/**
 * Created by PhpStorm.
 * User: monkey
 * Date: 19.10.19
 * Time: 21:26
 */

namespace App\Service\Synchronization\src\AdapterInterface;


use App\Service\Synchronization\src\AdapterInterface\Entity\INode;
use App\Service\Synchronization\src\AdapterInterface\Entity\IRevision;

interface ITransport
{
    public function send(INode $node, IRevision $revision);
}