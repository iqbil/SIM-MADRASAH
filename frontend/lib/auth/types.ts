export interface AuthRole {
  code: string;
  name: string;
}

export interface AuthUser {
  id: number;
  username: string;
  name: string;
  email: string | null;
  email_verified: boolean;
  must_change_password: boolean;
  roles: AuthRole[];
  permissions?: string[];
  navigation?: string[];
}

export interface TokenResponse {
  access_token: string;
  refresh_token: string;
  token_type: string;
  expires_in: number;
  refresh_expires_in: number;
  user: AuthUser;
}

export interface ApiError {
  message?: string;
  errors?: Record<string, string[]>;
}
