<?php

namespace Solspace\Calendar\Controllers;

use Solspace\Calendar\Calendar;
use Solspace\Calendar\Library\CodePack\CodePack;
use Solspace\Calendar\Library\CodePack\Exceptions\FileObject\FileObjectException;
use Solspace\Calendar\Library\Helpers\PermissionHelper;
use Solspace\Calendar\Resources\Bundles\CodePackBundle;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class CodePackController extends BaseController
{
    public const FLASH_VAR_KEY = 'codepack_prefix';

    /**
     * @throws ForbiddenHttpException
     */
    public function init(): void
    {
        PermissionHelper::requirePermission(Calendar::PERMISSION_SETTINGS);

        parent::init();
    }

    /**
     * Show CodePack contents
     * Provide means to prefix the CodePack.
     */
    public function actionListContents(): Response
    {
        $this->view->registerAssetBundle(CodePackBundle::class);

        $codePack = $this->getCodePack();

        $postInstallPrefix = \Craft::$app->session->getFlash(self::FLASH_VAR_KEY);
        if ($postInstallPrefix) {
            return $this->renderTemplate(
                'calendar/codepack/_post_install',
                [
                    'codePack' => $codePack,
                    'prefix' => CodePack::getCleanPrefix($postInstallPrefix),
                ]
            );
        }

        return $this->renderTemplate(
            'calendar/codepack',
            [
                'codePack' => $codePack,
                'prefix' => 'calendar-demo',
            ]
        );
    }

    /**
     * Perform the install feats.
     */
    public function actionInstall(): Response
    {
        $this->requirePostRequest();

        $codePack = $this->getCodePack();
        $prefix = \Craft::$app->request->post('prefix');

        $prefix = preg_replace('/[^a-zA-Z_0-9-\/]/', '', $prefix);

        try {
            $codePack->install($prefix);
            Calendar::getInstance()->settings->dismissDemoBanner();
        } catch (FileObjectException $exception) {
            return $this->renderTemplate(
                'calendar/codepack',
                [
                    'codePack' => $codePack,
                    'prefix' => $prefix,
                    'exceptionMessage' => $exception->getMessage(),
                ]
            );
        }

        \Craft::$app->session->setFlash('codepack_prefix', $prefix);

        return $this->redirectToPostedUrl();
    }

    private function getCodePack(): CodePack
    {
        return new CodePack(__DIR__.'/../codepack');
    }
}
