<?php

namespace Solspace\Calendar\Widgets;

use craft\helpers\UrlHelper;
use Solspace\Calendar\Calendar;
use Solspace\Calendar\Resources\Bundles\WidgetMonthBundle;

class MonthWidget extends AbstractWidget
{
    /** @var string */
    public $title;

    /** @var string */
    public $view;

    /** @var array */
    public $calendars = '*';

    /** @var int */
    public $siteId;

    public static function displayName(): string
    {
        return Calendar::t('Mini Calendar');
    }

    public function init(): void
    {
        parent::init();

        if (null === $this->title) {
            $this->title = self::displayName();
        }
    }

    public function getBodyHtml(): ?string
    {
        if (!Calendar::getInstance()->isPro()) {
            return Calendar::t(
                "Requires <a href='{link}'>Pro</a> edition",
                ['link' => UrlHelper::cpUrl('plugin-store/calendar')]
            );
        }

        \Craft::$app->view->registerAssetBundle(WidgetMonthBundle::class);

        $calendarLocale = \Craft::$app->locale->id;
        $calendarLocale = str_replace('_', '-', strtolower($calendarLocale));
        $localeModulePath = __DIR__.'/../js/lib/fullcalendar/locale/'.$calendarLocale.'.js';
        if (!file_exists($localeModulePath)) {
            $calendarLocale = 'en';
        }

        return \Craft::$app->view->renderTemplate(
            'calendar/_widgets/month/body',
            [
                'locale' => $calendarLocale,
                'settings' => $this,
            ]
        );
    }

    public function getSettingsHtml(): ?string
    {
        $siteOptions = [];
        foreach (\Craft::$app->sites->getAllSites() as $site) {
            $siteOptions[$site->id] = $site->name;
        }

        return \Craft::$app->view->renderTemplate(
            'calendar/_widgets/month/settings',
            [
                'calendars' => Calendar::getInstance()->calendars->getAllCalendarTitles(),
                'settings' => $this,
                'siteOptions' => $siteOptions,
            ]
        );
    }

    public function rules(): array
    {
        return [
            [['calendars'], 'required'],
        ];
    }
}
