<?php

declare(strict_types=1);

namespace Smile\ScopedEav\Ui\DataProvider\Entity\Form;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\PoolInterface;

/**
 * Scoped EAV generic dataprovider for edit form.
 */
class EntityDataProvider extends AbstractDataProvider
{
    /**
     * @var PoolInterface
     */
    private $pool;

    /**
     * Constructor.
     *
     * @param string $name Source name.
     * @param string $primaryFieldName Primary field name.
     * @param string $requestFieldName Request field name.
     * @param PoolInterface $pool Meta & data modifier pool.
     * @param array $meta Original meta.
     * @param array $data Original data.
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        PoolInterface $pool,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->pool       = $pool;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $this->data = $modifier->modifyData($this->data);
        }

        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $meta = $modifier->modifyMeta($meta);
        }

        return $meta;
    }
}
