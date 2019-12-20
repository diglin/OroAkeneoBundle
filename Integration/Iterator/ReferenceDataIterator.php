<?php

namespace Oro\Bundle\AkeneoBundle\Integration\Iterator;

class ReferenceDataIterator extends AbstractIterator
{
    /**
     * {@inheritdoc}
     */
    public function doCurrent()
    {
        return $this->resourceCursor->current();
    }
}
