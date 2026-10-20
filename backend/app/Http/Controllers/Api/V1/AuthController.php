<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Resources\AuthUserResource;
use App\Services\AuthenticationService;
use App\Services\JwtTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function options(): JsonResponse
    {
        return response()->json(['data' => ['password' => true, 'google' => GoogleAuthController::enabled()]]);
    }

    public function login(LoginRequest $request, AuthenticationService $auth): JsonResponse
    {
        $result = $auth->login($request->validated('login'), $request->validated('password'), $request);

        return response()->json(['data' => [...$result['tokens'], 'user' => new AuthUserResource($result['user'])]]);
    }

    public function refresh(RefreshTokenRequest $request, JwtTokenService $tokens): JsonResponse
    {
        $result = $tokens->refresh($request->validated('refresh_token'), $request);

        return response()->json(['data' => [...$result['tokens'], 'user' => new AuthUserResource($result['user'])]]);
    }

    public function me(Request $request): AuthUserResource
    {
        return new AuthUserResource($request->user());
    }

    public function logout(Request $request, JwtTokenService $tokens): JsonResponse
    {
        $tokens->logout($request->user(), $request->attributes->get('auth_session'), $request);

        return response()->json(['message' => 'Logout berhasil.']);
    }

    public function logoutAll(Request $request, JwtTokenService $tokens): JsonResponse
    {
        $tokens->logoutAll($request->user(), $request);

        return response()->json(['message' => 'Semua sesi berhasil dicabut.']);
    }

    public function changePassword(ChangePasswordRequest $request, AuthenticationService $auth): JsonResponse
    {
        $auth->changePassword($request->user(), $request->validated('current_password'), $request->validated('password'), $request);

        return response()->json(['message' => 'Sandi berhasil diubah. Silakan login kembali.']);
    }
}
