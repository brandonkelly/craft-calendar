<?php

namespace Solspace\Calendar\Library\Transformers;

use Carbon\Carbon;
use craft\i18n\Locale;
use Solspace\Calendar\Calendar;
use Solspace\Calendar\Elements\Event;
use Solspace\Calendar\Library\DateHelper;
use Solspace\Calendar\Library\RecurrenceHelper;
use Solspace\Calendar\Models\ExceptionModel;

class EventToUiDataTransformer
{
    /** @var Event */
    private $event;

    /**
     * EventToUiDataTransformer constructor.
     *
     * @param Event $event
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * @return array
     */
    public function transform(): array
    {
        $event = $this->event;

        return [
            'dates'         => [
                'start' => $event->getStartDate()->getTimestamp(),
                'end'   => $event->getEndDate()->getTimestamp(),
            ],
            'allDay'        => $event->isAllDay() ?? false,
            'interval'      => (int) ($event->getInterval() ?? 1),
            'multiDay'      => $event->isMultiDay() ?? false,
            'repeats'       => $event->isRepeating() ?? false,
            'freq'          => $event->getFreq() ?? 'DAILY',
            'byDayInterval' => $this->getByDayInterval(),
            'byDay'         => $this->getByDay(),
            'byMonthDay'    => $this->getByMonthDay(),
            'byMonth'       => $this->getByMonth(),
            'endRepeat'     => [
                'type'  => $event->getUntilType(),
                'date'  => $this->getUntilDate(),
                'count' => $event->getCount() ?? 1,
            ],
            'selectDates'   => $this->getSelectDateTimestamps(),
            'exceptions'    => $this->getExceptionTimestamps(),
        ];
    }

    /**
     * @return int
     */
    private function getUntilDate(): int
    {
        $event = $this->event;
        $date  = $event->getUntilDate() ?? Carbon::createFromTimestampUTC(time());
        $date->setTime(23, 59, 59);

        return $date->getTimestamp();
    }

    /**
     * @return int
     */
    private function getByDayInterval(): int
    {
        $weekDays = $this->event->getRepeatsByWeekDays();
        if (
            !empty($weekDays) &&
            \in_array(
                $this->event->getFrequency(),
                [RecurrenceHelper::MONTHLY, RecurrenceHelper::YEARLY],
                true
            )
        ) {
            $firstSymbol = substr($weekDays[0], 0, 1);
            if ($firstSymbol === '-') {
                return -1;
            }

            if (is_numeric($firstSymbol)) {
                return (int) $firstSymbol;
            }
        }

        return 0;
    }

    /**
     * @return array
     */
    private function getByDay(): array
    {
        $byDay = $this->event->getRepeatsByWeekDays() ?? [];

        if (empty($byDay)) {
            return [DateHelper::getCurrentWeekDay($this->event->getStartDate())];
        }

        return array_map(function ($value) {
            return preg_replace('/.*(\w{2})$/', '$1', $value);
        }, $byDay);
    }

    /**
     * @return array
     */
    private function getByMonth(): array
    {
        return $this->castArrayValuesToInt($this->event->getRepeatsByMonths() ?? [$this->event->getStartDate()->month]);
    }

    /**
     * @return array
     */
    private function getByMonthDay(): array
    {
        return $this->castArrayValuesToInt($this->event->getRepeatsByMonthDays() ?? [$this->event->getStartDate()->day]);
    }

    /**
     * @return int[]
     */
    private function getSelectDateTimestamps(): array
    {
        return array_map(function (\DateTime $date) {
            return $date->getTimestamp();
        }, $this->event->getSelectDatesAsDates());
    }

    /**
     * @return int[]
     */
    private function getExceptionTimestamps(): array
    {
        return array_map(function (ExceptionModel $exception) {
            return $exception->date->getTimestamp();
        }, $this->event->getExceptions());
    }

    /**
     * @param array $array
     *
     * @return array
     */
    private function castArrayValuesToInt(array $array): array
    {
        return array_map(function ($value) {
            return (int) $value;
        }, $array);
    }
}
