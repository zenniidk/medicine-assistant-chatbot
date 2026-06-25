import { Component } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { Auth } from '../services/auth';

@Component({
  selector: 'app-auth',
  templateUrl: './auth.page.html',
  styleUrls: ['./auth.page.scss'],
  standalone: false,
})
export class AuthPage {
  mode: 'login' | 'signup' = 'login';
  loading = false;
  message = '';
  messageType: 'success' | 'error' = 'error';

  loginForm = this.fb.nonNullable.group({
    username: ['', [Validators.required]],
    password: ['', [Validators.required]],
  });

  signupForm = this.fb.group({
    full_name: ['', [Validators.required]],
    username: ['', [Validators.required, Validators.pattern(/^[a-zA-Z0-9_.-]{3,30}$/)]],
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required, Validators.minLength(6)]],
    age: [null as number | null, [Validators.required, Validators.min(1), Validators.max(120)]],
    sex: ['', [Validators.required]],
    allergies: ['Ninguna'],
  });

  constructor(private fb: FormBuilder, private auth: Auth, private router: Router) {}

  ionViewWillEnter(): void {
    this.loading = false;
  }

  setMode(mode: 'login' | 'signup'): void {
    this.mode = mode;
    this.loading = false;
    this.message = '';
  }

  submitLogin(): void {
    if (this.loginForm.invalid) {
      this.showValidationMessage('Escribe tu usuario o correo y tu contrasena.');
      return;
    }
    this.loading = true;
    this.auth.login(this.loginForm.getRawValue()).subscribe({
      next: () => {
        this.loading = false;
        this.router.navigateByUrl('/home');
      },
      error: (error) => this.showError(error),
    });
  }

  submitSignup(): void {
    if (this.signupForm.invalid) {
      this.showValidationMessage('Revisa correo, contrasena, edad y sexo.');
      return;
    }
    this.loading = true;
    const values = this.signupForm.getRawValue();
    this.auth.signup({
      email: values.email ?? '',
      full_name: values.full_name ?? '',
      username: values.username ?? '',
      password: values.password ?? '',
      age: values.age,
      sex: values.sex ?? '',
      allergies: values.allergies || 'Ninguna',
    }).subscribe({
      next: () => {
        this.loading = false;
        this.router.navigateByUrl('/home');
      },
      error: (error) => this.showError(error),
    });
  }

  private showError(error: { error?: { message?: string } }): void {
    this.loading = false;
    this.messageType = 'error';
    this.message = error.error?.message ?? 'No se pudo completar la solicitud';
  }

  private showValidationMessage(message: string): void {
    this.loading = false;
    this.messageType = 'error';
    this.message = message;
  }
}
