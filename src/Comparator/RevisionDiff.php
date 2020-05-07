<?php
/**
 * Created by PhpStorm.
 * User: monkey
 * Date: 19.10.19
 * Time: 21:41
 */

namespace App\Service\Synchronization\src\Comparator;


class RevisionDiff
{
    /** @var array */
    private $created = [];

    /** @var array  */
    private $added = [];

    /** @var array */
    private $updated = [];

    /** @var array */
    private $deleted = [];

    /** @var array  */
    private $changed = [];

    public function addCreated($field, $newValue): self
    {
        $this->created[$field] = $newValue;
        $this->addChanged($field);

        return $this;
    }

    public function addAdded($field, $newValue): self
    {
        if(!key_exists($field, $this->added)) {
            $this->added[$field] = [];
        }

        $this->added[$field][] = $newValue;
        $this->addChanged($field);

        return $this;
    }

    public function addUpdated($field, $newValue): self
    {
        $this->updated[$field] = $newValue;
        $this->addChanged($field);
        return $this;
    }

    public function addDeleted($field, $deletedValue): self
    {
        $this->deleted[$field] = $deletedValue;

        return $this;
    }

    public function getCreated(): array
    {
        return $this->created;
    }

    public function hasCreated($key): bool
    {
        return key_exists($key, $this->created);
    }

    public function getCreatedValue($key)
    {
        return $this->created[$key];
    }

    public function getAdded(): array
    {
        return $this->added;
    }

    public function getUpdated(): array
    {
        return $this->updated;
    }

    public function hasUpdated($key): bool
    {
        return key_exists($key, $this->updated);
    }

    public function getUpdatedValue($key)
    {
        return $this->updated[$key];
    }

    public function getDeleted(): array
    {
        return $this->deleted;
    }

    private function addChanged($field)
    {
        $lastPath = '';

        for( $i=0;  $i< mb_strlen($field); $i++) {
            if($field[$i] == '.') {
                $this->changed[$lastPath] = true;
            }

            $lastPath .= $field[$i];
        }

        $this->changed[$lastPath] = true;
    }

    public function isChanged($key): bool
    {
        return key_exists($key, $this->changed);
    }

    public function isEmpty(): bool
    {
        return empty($this->getDeleted())
            && empty($this->getCreated())
            && empty($this->getUpdated())
            && empty($this->getAdded());
    }

    public function isEqualTo(self $diff)
    {
        if($this->getDeleted() !== $diff->getDeleted()
            || $this->getCreated() !== $diff->getCreated()
            || $this->getUpdated() !== $diff->getUpdated()
            || $this->getAdded() !== $diff->getAdded()
        ) {
            return false;
        }

        return true;
    }
}