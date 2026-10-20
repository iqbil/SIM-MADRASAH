<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthUserResource;
use App\Services\AuthenticationService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleAuthController extends Controller
{
    public static function enabled(): bool
    {
        return (bool) config('services.google.enabled')
            && filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    public function redirect(Request $request): RedirectResponse
    {
        abort_unless(self::enabled(), 404, 'Login Google belum dikonfigurasi.');
        $request->session()->regenerate();
        $request->session()->put('google_auth_started_at', now('UTC')->timestamp);

        return $this->provider()->with(['prompt' => 'select_account'])->redirect();
    }

    public function callback(Request $request, AuthenticationService $auth): JsonResponse
    {
        abort_unless(self::enabled(), 404, 'Login Google belum dikonfigurasi.');
        $started = $request->session()->pull('google_auth_started_at');
        if ($request->has('error') || ! is_int($started) || $started < now('UTC')->timestamp - 600 || $started > now('UTC')->timestamp) {
            $request->session()->forget(['state', 'code_verifier']);
            abort(401, 'Permintaan login Google tidak valid atau kedaluwarsa.');
        }
        $request->validate(['state' => ['required', 'string', 'max:512'], 'code' => ['required', 'string', 'max:4096']]);
        try {
            $google = $this->provider()->user();
        } catch (InvalidStateException|GuzzleException $exception) {
            $request->session()->forget(['state', 'code_verifier']);
            abort(401, 'Verifikasi login Google gagal. Silakan mulai kembali.');
        }
        $result = $auth->google($google, $request);
        $request->session()->forget(['state', 'code_verifier']);

        return response()->json(['data' => [...$result['tokens'], 'user' => new AuthUserResource($result['user'])]]);
    }

    private function provider(): GoogleProvider
    {
        return Socialite::buildProvider(GoogleProvider::class, config('services.google'))->enablePKCE();
    }
}
