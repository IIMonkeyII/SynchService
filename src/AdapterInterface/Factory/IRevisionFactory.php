<?php
/**
 * Created by PhpStorm.
 * User: monkey
 * Date: 22.10.19
 * Time: 13:08
 */

namespace App\Service\Synchronization\src\AdapterInterface\Factory;


use App\Service\Synchronization\src\AdapterInterface\Entity\IRevision;

interface IRevisionFactory
{
    public function buildRevision($sourceData): IRevision;
}