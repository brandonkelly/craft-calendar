<?php

namespace Solspace\Calendar\Events;

use craft\base\Model;
use craft\events\CancelableEvent;

class SaveModelEvent extends CancelableEvent
{
    private ?Model $model = null;

    private ?bool $isNew = null;

    /**
     * BeforeSaveModelEvent constructor.
     */
    public function __construct(Model $model, bool $isNew)
    {
        $this->model = $model;
        $this->isNew = $isNew;

        parent::__construct();
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }
}
