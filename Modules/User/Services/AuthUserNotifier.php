<?php

namespace Modules\User\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use LogicException;
use Modules\User\Contracts\UserNotifier;

final class AuthUserNotifier implements UserNotifier
{
    public function notify(int $userId, Notification $notification): bool
    {
        if ($userId < 1) {
            return false;
        }

        $model = $this->newAuthModel();
        $record = $model->newQuery()->find($userId);
        $active = $record instanceof Model ? $record->getAttribute('is_active') : null;

        if (! $record instanceof Model || $active === false || $active === 0 || $active === '0') {
            return false;
        }

        if (! method_exists($record, 'notify')) {
            throw new LogicException('The admin auth identity must support Laravel notifications.');
        }

        $record->notify($notification);

        return true;
    }

    private function newAuthModel(): Model
    {
        $provider = (string) config('auth.guards.admin.provider', '');
        $driver = (string) config("auth.providers.{$provider}.driver", '');
        $modelClass = config("auth.providers.{$provider}.model");

        if ($provider === '' || $driver !== 'eloquent' || ! is_string($modelClass) || ! class_exists($modelClass)) {
            throw new LogicException('The admin auth provider must use a valid Eloquent model.');
        }

        $model = new $modelClass;

        if (! $model instanceof Model || ! $model instanceof Authenticatable) {
            throw new LogicException('The admin auth provider model must be an Eloquent authentication identity.');
        }

        return $model;
    }
}
