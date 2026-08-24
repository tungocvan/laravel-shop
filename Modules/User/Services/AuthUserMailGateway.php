<?php

namespace Modules\User\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use LogicException;
use Modules\User\Contracts\UserMailGateway;
use Modules\User\Data\UserMailMessage;
use Modules\User\Mail\UserMessageMail;

final class AuthUserMailGateway implements UserMailGateway
{
    public function sendToActive(int $userId, UserMailMessage $message): bool
    {
        if ($userId < 1) {
            return false;
        }

        $model = $this->newAuthModel();
        $query = $model->newQuery();
        if ($model->isFillable('is_active') || $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'is_active')) {
            $query->where('is_active', true);
        }
        $record = $query->find($userId);
        $email = $record instanceof Model ? $record->getAttribute('email') : null;
        if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        Mail::to($email)->send(new UserMessageMail($message));

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
