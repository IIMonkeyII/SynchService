<?php
/**
 * Created by PhpStorm.
 * User: monkey
 * Date: 19.10.19
 * Time: 22:12
 */

namespace App\Service\Synchronization\src\Comparator;


use App\Service\Synchronization\src\AdapterInterface\Entity\IRevision;
use App\Service\Synchronization\src\Exception\ComparatorException;
use App\Service\Synchronization\src\Exception\IntersectionException;
use App\Service\Synchronization\src\Exception\MergeException;

class Comparator
{
    public function __construct()
    {
    }

    /**
     * @param IRevision $revision1
     * @param IRevision $revision2
     * @return RevisionDiff
     * @throws ComparatorException
     */
    public function buildDiff(IRevision $revision1 = null, IRevision $revision2 = null)
    {
        $diff = new RevisionDiff();

        $this->diff(
            $revision1 ? $revision1->getData() : [],
            $revision2 ? $revision2->getData() : [],
            $diff);

        return $diff;
    }

    /**
     * @param RevisionDiff $diff1
     * @param RevisionDiff $diff2
     * @return RevisionDiff
     * @throws IntersectionException
     */
    public function buildUnion(RevisionDiff $diff1, RevisionDiff $diff2)
    {
        $union = clone $diff1;

        $this->unionAdded($diff2, $union);
        $this->unionCreated($diff2, $union);
        $this->unionDeleted($diff2, $union);
        $this->unionUpdated($diff2, $union);

        // for check high level deletion
        $this->unionDeleted($union, clone $diff2);

        return $union;
    }

    /**
     * @param RevisionDiff $diff
     * @param IRevision $revision
     * @throws MergeException
     */
    public function merge(RevisionDiff $diff, IRevision $revision)
    {
        $data = $revision->getData();

        $changes = [];

        foreach ($diff->getAdded() as $field => $value) {
            $this->add($field, $value, $data);
            $this->add($field, $value, $changes);
        }

        foreach ($diff->getUpdated() as $field => $value) {
            $this->set($field, $value, $data);
            $this->set($field, $value, $changes);
        }

        foreach ($diff->getCreated() as $field => $value) {
            $this->set($field, $value, $data);
            $this->set($field, $value, $changes);
        }

        foreach ($diff->getDeleted() as $field => $value) {
            $this->delete($field, $data);
            $this->delete($field, $changes);
        }

        $revision->setData($data);
        $revision->setChanges($changes);
    }

    /**
     * @param string $field
     * @param mixed $value
     * @param array $data
     * @throws MergeException
     */
    private function set($field, $value, array  &$data)
    {
        $path = explode('.', $field);

        $temp = &$data;

        foreach($path as $key) {
            if(!is_array($temp)) {
                throw new MergeException();
            }

            if(!isset($temp[$key])) {
                $temp[$key] = [];
            }
            $temp = &$temp[$key];
        }
        $temp = $value;
        unset($temp);
    }

    /**
     * @param string $field
     * @param mixed $value
     * @param array $data
     * @throws MergeException
     */
    private function add($field, $value, array &$data)
    {
        $path = explode('.', $field);

        $temp = &$data;

        foreach($path as $key) {
            if(!is_array($temp)) {
                throw new MergeException();
            }

            if(!isset($temp[$key])) {
                $temp[$key] = [];
            }

            $temp = &$temp[$key];
        }

        if(!is_array($value) || !is_array($temp)) {
            throw new MergeException();
        }

        foreach ($value as $item) {
            $temp[] = $item;
        }

        unset($temp);
    }

    /**
     * @param string $field
     * @param array $data
     * @throws MergeException
     */
    private function delete($field, array &$data)
    {
        $path = explode('.', $field);

        $temp = &$data;

        foreach($path as $key) {
            if(!is_array($temp)) {
                throw new MergeException();
            }

            $prev = &$temp;
            if(!isset($temp[$key])) {
                $temp[$key] = [];
            }
            $temp = &$temp[$key];
        }

        if(isset($key) && isset($prev)) {
            unset($prev[$key]);
            unset($prev);
        }

        unset($temp);
    }

    /**
     * @param RevisionDiff $diff
     * @param RevisionDiff $union
     * @throws IntersectionException
     */
    private function unionCreated(RevisionDiff $diff, RevisionDiff $union)
    {
        foreach ($diff->getCreated() as $field => $newValue) {
//            if($union->hasCreated($field)) {
//                if($union->getCreatedValue($field) !== $newValue) {
//                    throw new IntersectionException();
//                }
//            } else {
                $union->addCreated($field, $newValue);
//            }
        }
    }

    /**
     * @param RevisionDiff $diff
     * @param RevisionDiff $union
     */
    private function unionAdded(RevisionDiff $diff, RevisionDiff $union)
    {
        foreach ($diff->getAdded() as $field => $newValue) {
            foreach ($newValue as $item) {
                $union->addAdded($field, $item);
            }
        }
    }

    /**
     * @param RevisionDiff $diff
     * @param RevisionDiff $union
     * @throws IntersectionException
     */
    private function unionDeleted(RevisionDiff $diff, RevisionDiff $union)
    {
        foreach ($diff->getDeleted() as $field => $oldValue) {
//            if($union->isChanged($field)) {
//                throw new IntersectionException("$field cant be deleted because it is changed");
//            }

            $union->addDeleted($field, $oldValue);
        }
    }

    /**
     * @param RevisionDiff $diff
     * @param RevisionDiff $union
     * @throws IntersectionException
     */
    private function unionUpdated(RevisionDiff $diff, RevisionDiff $union)
    {
        foreach ($diff->getUpdated() as $field => $newValue) {
//            if($union->hasUpdated($field)) {
//                if($union->getUpdatedValue($field) !== $newValue) {
//                    throw new IntersectionException();
//                }
//            } else {
                $union->addUpdated($field, $newValue);
//            }
        }
    }

    /**
     * @param array $data1
     * @param array $data2
     * @param RevisionDiff $diff
     * @param null $baseKey
     * @return RevisionDiff
     * @throws ComparatorException
     */
    private function diff(array $data1, array $data2, RevisionDiff $diff, $baseKey = null)
    {
        $keys = $this->keysUnion($data1, $data2, $isBothIndexed);


        foreach ($keys as $key)  {
            if(strpos($key, '.') !== false) {
                throw new ComparatorException();
            }

            $fullKey = $baseKey ? "$baseKey.$key" : $key;

            $isIn1 = key_exists($key, $data1);
            $isIn2 = key_exists($key, $data2);

            if($isIn1 && !$isIn2) {
                $diff->addDeleted($fullKey, $data1[$key]);
            }

            if(!$isIn1 && $isIn2) {
                if($isBothIndexed) {
                    $diff->addAdded($baseKey, $data2[$key]);
                } else {
                    $diff->addCreated($fullKey, $data2[$key]);
                }
            }

            if($isIn1 && $isIn2 && $data1[$key] !== $data2[$key]) {
                if(is_array($data1[$key]) && is_array($data2[$key])) {
                    $this->diff($data1[$key], $data2[$key], $diff, $fullKey);
                } else {
                    $diff->addUpdated($fullKey, $data2[$key]);
                }
            }
        }

        return $diff;
    }

    /**
     * @param array $array1
     * @param array $array2
     * @param null $isIndexed
     * @return array
     */
    private function keysUnion(array $array1, array  $array2, &$isIndexed = null)
    {
        $keys = [];

        $startKey = 0;

        $isIndexed = true;

        foreach ($array1 as $key => $item) {
            $keys[$key] = true;

            if($startKey++ !== $key) {
                $isIndexed = false;
            }
        }

        $startKey = 0;
        foreach ($array2 as $key => $item) {
            $keys[$key] = true;

            if($startKey++ !== $key) {
                $isIndexed = false;
            }
        }

        return array_keys($keys);
    }
}