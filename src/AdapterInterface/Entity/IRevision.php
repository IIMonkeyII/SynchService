<?php
/**
 * Created by PhpStorm.
 * User: monkey
 * Date: 18.10.19
 * Time: 18:56
 */

namespace App\Service\Synchronization\src\AdapterInterface\Entity;


interface IRevision
{
    /**
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * @return int
     */
    public function getEntityId(): int;

    /**
     * @return IRevision | null
     */
    public function getSource(): ?IRevision;

    /**
     * @param IRevision | null $revision
     * @return IRevision
     */
    public function setSource(?IRevision $revision): self;

    /**
     * @return array
     */
    public function getData(): array;

    /**
     * @param array $data
     * @return self
     */
    public function setData(array $data): self;

    /**
     * @return array|null
     */
    public function getChanges(): ?array;

    /**
     * @param array $data
     * @return IRevision
     */
    public  function setChanges(array $data): self;

    /**
     * @param INode $node
     * @return IRevision
     */
    public function setNodeSource(INode $node): self;

    /**
     * @return INode|null
     */
    public function getNodeSource() : ?INode;
}