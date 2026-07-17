<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\UpdateReaderPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ReaderPasswordController extends Controller
{
    public function __invoke(UpdateReaderPasswordRequest $request): RedirectResponse
    {
        /** @var User $reader */
        $reader = $request->user();

        $reader->forceFill([
            'password' => Hash::make($request->string('password')->toString()),
            'remember_token' => Str::random(60),
        ])->save();

        $this->deleteOtherDatabaseSessions($request, $reader);

        return redirect()
            ->to(localized_route('reader.account'))
            ->with('status', __('reader_auth.password_updated'));
    }

    private function deleteOtherDatabaseSessions(UpdateReaderPasswordRequest $request, User $reader): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $configuredConnection = config('session.connection');
        $connectionName = is_string($configuredConnection) && $configuredConnection !== ''
            ? $configuredConnection
            : null;
        $table = (string) config('session.table', 'sessions');

        if (! Schema::connection($connectionName)->hasTable($table)) {
            return;
        }

        $currentSessionId = $request->hasSession()
            ? $request->session()->getId()
            : null;
        $query = DB::connection($connectionName)
            ->table($table)
            ->where('user_id', $reader->getKey());

        if (is_string($currentSessionId) && $currentSessionId !== '') {
            $query->where('id', '!=', $currentSessionId);
        }

        $query->delete();
    }
}
