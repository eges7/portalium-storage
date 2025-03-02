<?php

namespace portalium\storage\components;

use Yii;
use yii\base\BaseObject;
use portalium\site\models\Setting;
use portalium\storage\Module;
use portalium\workspace\models\WorkspaceUser;

class TriggerActions extends BaseObject
{
    public function init()
    {
        parent::init(); // TODO: Change the autogenerated stub
    }

    public function onWorkspaceAvailableRoleUpdateAfter($event)
    {
        ['deletedRoles' => $deletedRoles] = $event->payload;

        if (empty($deletedRoles)) {
            return;
        }

        $default_role = Setting::find()->where(['name' => 'storage::workspace::default_role'])->one();
        $admin_role = Setting::find()->where(['name' => 'storage::workspace::admin_role'])->one();
        $settingModel = Setting::findOne(['name' => 'workspace::available_roles']);
        
        foreach ($deletedRoles as $deletedRole) {
            if ($default_role->value == $deletedRole['role']) {
                $availableRoles = json_decode($settingModel->value, true);
                if (!in_array($deletedRole['role'], $availableRoles['storage'])) {
                    $availableRoles['storage'][] = $deletedRole['role'];
                    $settingModel->value = json_encode($availableRoles);
                    $settingModel->save();
                    Yii::$app->session->addFlash('error', Module::t('Default role is not available for storage module. Please check your settings.'));
                }
            }
        }
    }

    //onWorkspaceUserCreateAfter
    public function onWorkspaceUserCreateAfter($event)
    {
        ['id_user' => $id_user, 'id_workspace' => $id_workspace] = $event->payload;

        $default_role = Setting::find()->where(['name' => 'storage::workspace::default_role'])->one();
        $admin_role = Setting::find()->where(['name' => 'storage::workspace::admin_role'])->one();
        $auth = Yii::$app->authManager;
        /* if ($default_role->value == $auth->getRole($default_role->value)->name) {
            $auth->assign($auth->getRole($default_role->value), $id_user);

            $workspaceUser = WorkspaceUser::findOne(['id_user' => $id_user, 'id_workspace' => $id_workspace, 'role' => $default_role->value, 'id_module' => 'storage']);

            if (!$workspaceUser) {
                $workspaceUser = new WorkspaceUser();
                $workspaceUser->id_user = $id_user;
                $workspaceUser->id_workspace = $id_workspace;
                $workspaceUser->role = $default_role->value;
                $workspaceUser->id_module = 'storage';
                $workspaceUser->save();
            }
        } */

        $roles = [
            $default_role,
            $admin_role
        ];
        $activeWorkspaceId = Yii::$app->workspace->id;
        foreach ($roles as $role) {
            if (!$role->value)
                continue;
            if ($auth->getRole($role->value)) {
                //$auth->assign($auth->getRole($role->value), $id_user);
                $workspaceUser = WorkspaceUser::findOne(['id_user' => $id_user, 'id_workspace' => $id_workspace, 'role' => $role->value, 'id_module' => 'storage']);

                if (!$workspaceUser) {
                    $workspaceUser = new WorkspaceUser();
                    $workspaceUser->id_user = $id_user;
                    $workspaceUser->id_workspace = $id_workspace;
                    $workspaceUser->role = $role->value;
                    $workspaceUser->id_module = 'storage';
                    
                    $workspaceUser->status = $activeWorkspaceId == $id_workspace ? WorkspaceUser::STATUS_ACTIVE : WorkspaceUser::STATUS_INACTIVE;
                    $workspaceUser->save();
                }
            }
        }

    }

}