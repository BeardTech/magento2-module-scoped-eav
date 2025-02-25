<?php

declare(strict_types=1);

namespace Smile\ScopedEav\Controller\Adminhtml\Set;

use Smile\ScopedEav\Controller\Adminhtml\AbstractSet;

/**
 * Scoped EAV entity attribute set admin add controller.
 */
class Add extends AbstractSet
{
    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $this->setTypeId();

        return $this->createActionPage(__('New Attribute Set'));
    }
}
