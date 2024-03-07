<?php

namespace Solspace\Calendar\Elements\Actions;

use craft\base\Element;
use craft\elements\actions\SetStatus;
use craft\elements\db\ElementQueryInterface;
use Solspace\Calendar\Calendar;
use Solspace\Calendar\Elements\Event;
use Solspace\Calendar\Library\CalendarPermissionHelper;

class SetStatusAction extends SetStatus
{
    /**
     * Performs the action on any elements that match the given criteria.
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $failCount = 0;

        $totalElements = $query->count();

        $isLocalized = Event::isLocalized() && \Craft::$app->getIsMultiSite();

        /** @var Event $element */
        foreach ($query->all() as $element) {
            if (CalendarPermissionHelper::canEditEvent($element)) {
                switch ($this->status) {
                    case self::ENABLED:
                        // Skip if there's nothing to change
                        if ($element->enabled && $element->getEnabledForSite()) {
                            continue 2;
                        }

                        $element->enabled = true;
                        $element->setEnabledForSite(true);
                        $element->setScenario(Element::SCENARIO_LIVE);

                        break;

                    case self::DISABLED:
                        // Is this a multi-site element?
                        if ($isLocalized && 1 !== \count($element->getSupportedSites())) {
                            // Skip if there's nothing to change
                            if (!$element->getEnabledForSite()) {
                                continue 2;
                            }

                            $element->setEnabledForSite(false);
                        } else {
                            // Skip if there's nothing to change
                            if (!$element->enabled) {
                                continue 2;
                            }

                            $element->enabled = false;
                        }

                        break;
                }

                if (false === Calendar::getInstance()->events->saveEvent($element)) {
                    // Validation error
                    ++$failCount;
                }

                // If we wanted to inform the user, some elements were not updated due to permissions, we could uncomment the following lines
                // } else {
                //	$failCount++;
            }
        }

        // Did all of them fail?
        if ($failCount === $totalElements) {
            if (1 === $totalElements) {
                $this->setMessage(\Craft::t('app', 'Could not update status due to a validation error.'));
            } else {
                $this->setMessage(\Craft::t('app', 'Could not update statuses due to validation errors.'));
            }

            return false;
        }

        if (0 !== $failCount) {
            $this->setMessage(\Craft::t('app', 'Status updated, with some failures due to validation errors.'));
        } else {
            if (1 === $totalElements) {
                $this->setMessage(\Craft::t('app', 'Status updated.'));
            } else {
                $this->setMessage(\Craft::t('app', 'Statuses updated.'));
            }
        }

        return true;
    }
}
