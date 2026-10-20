<?php

namespace App\Auth;

use Illuminate\Auth\RequestGuard;
use Illuminate\Http\Request;

class JwtGuard extends RequestGuard
{
    public function setRequest(Request $request): static
    {
        $this->forgetUser();
        parent::setRequest($request);

        return $this;
    }
}
