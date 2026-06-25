import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable, tap } from 'rxjs';
import { environment } from '../../environments/environment';

export interface User {
  id: number;
  email: string;
  full_name: string;
  username: string;
  age: number;
  sex: 'Femenino' | 'Masculino' | 'Otro' | 'Prefiero no decir';
  allergies: string;
  profile_photo_url: string | null;
}

export interface AuthResponse {
  ok: boolean;
  message: string;
  token: string;
  token_type: 'Bearer';
  expires_in_days: number;
  user: User;
}

export interface LoginPayload {
  username: string;
  password: string;
}

export interface SignupPayload {
  email: string;
  full_name: string;
  username: string;
  password: string;
  age: number | null;
  sex: string;
  allergies: string;
}

export interface ProfilePayload {
  email: string;
  full_name: string;
  username: string;
  age: number | null;
  sex: string;
  allergies: string;
  password?: string;
}

@Injectable({ providedIn: 'root' })
export class Auth {
  private readonly tokenKey = 'medicinescan_token';
  private readonly userKey = 'medicinescan_user';
  private readonly apiUrl = environment.apiUrl;
  private readonly userSubject = new BehaviorSubject<User | null>(this.loadUser());

  readonly user$ = this.userSubject.asObservable();

  constructor(private http: HttpClient) {}

  login(payload: LoginPayload): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/login.php`, payload).pipe(
      tap((response) => this.persistSession(response))
    );
  }

  signup(payload: SignupPayload): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/signup.php`, payload).pipe(
      tap((response) => this.persistSession(response))
    );
  }

  profile(): Observable<{ ok: boolean; user: User }> {
    return this.http.get<{ ok: boolean; user: User }>(`${this.apiUrl}/profile.php`, {
      headers: this.authHeaders(),
    }).pipe(tap((response) => this.persistUser(response.user)));
  }

  updateProfile(payload: ProfilePayload): Observable<{ ok: boolean; message: string; user: User }> {
    return this.http.put<{ ok: boolean; message: string; user: User }>(
      `${this.apiUrl}/profile.php`,
      payload,
      { headers: this.authHeaders() }
    ).pipe(tap((response) => this.persistUser(response.user)));
  }

  uploadProfilePhoto(file: File): Observable<{ ok: boolean; message: string; user: User }> {
    const formData = new FormData();
    formData.append('photo', file);
    return this.http.post<{ ok: boolean; message: string; user: User }>(
      `${this.apiUrl}/profile_photo.php`,
      formData,
      { headers: this.authHeaders() }
    ).pipe(tap((response) => this.persistUser(response.user)));
  }

  logout(): Observable<{ ok: boolean; message: string }> {
    return this.http.post<{ ok: boolean; message: string }>(`${this.apiUrl}/logout.php`, {}, {
      headers: this.authHeaders(),
    }).pipe(tap(() => this.clearSession()));
  }

  deleteAccount(): Observable<{ ok: boolean; message: string }> {
    return this.http.delete<{ ok: boolean; message: string }>(`${this.apiUrl}/profile.php`, {
      headers: this.authHeaders(),
    }).pipe(tap(() => this.clearSession()));
  }

  isAuthenticated(): boolean {
    return Boolean(this.token());
  }

  token(): string | null {
    return localStorage.getItem(this.tokenKey);
  }

  clearSession(): void {
    localStorage.removeItem(this.tokenKey);
    localStorage.removeItem(this.userKey);
    this.userSubject.next(null);
  }

  private authHeaders(): HttpHeaders {
    return new HttpHeaders({
      Authorization: `Bearer ${this.token() ?? ''}`,
      Accept: 'application/json',
    });
  }

  private persistSession(response: AuthResponse): void {
    localStorage.setItem(this.tokenKey, response.token);
    this.persistUser(response.user);
  }

  private persistUser(user: User): void {
    localStorage.setItem(this.userKey, JSON.stringify(user));
    this.userSubject.next(user);
  }

  private loadUser(): User | null {
    const rawUser = localStorage.getItem(this.userKey);
    return rawUser ? JSON.parse(rawUser) as User : null;
  }
}
