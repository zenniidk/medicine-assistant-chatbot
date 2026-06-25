import { Component } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { Auth, User } from '../services/auth';

@Component({
  selector: 'app-profile',
  templateUrl: './profile.page.html',
  styleUrls: ['./profile.page.scss'],
  standalone: false,
})
export class ProfilePage {
  user: User | null = null;
  loading = false;
  deletingAccount = false;
  uploadingPhoto = false;
  message = '';
  messageType: 'success' | 'error' = 'success';
  selectedPhotoName = '';
  previewUrl: string | null = null;

  profileForm = this.fb.group({
    full_name: ['', [Validators.required]],
    username: ['', [Validators.required, Validators.pattern(/^[a-zA-Z0-9_.-]{3,30}$/)]],
    email: ['', [Validators.required, Validators.email]],
    age: [null as number | null, [Validators.required, Validators.min(1), Validators.max(120)]],
    sex: ['', [Validators.required]],
    allergies: ['Ninguna'],
    password: [''],
  });

  constructor(private fb: FormBuilder, private auth: Auth, private router: Router) {}

  ionViewWillEnter(): void {
    this.auth.profile().subscribe({
      next: ({ user }) => this.applyUser(user),
      error: () => {
        this.auth.clearSession();
        this.router.navigateByUrl('/auth');
      },
    });
  }

  saveProfile(): void {
    if (this.profileForm.invalid) {
      this.profileForm.markAllAsTouched();
      this.showMessage('Revisa nombre, correo, usuario, edad y sexo.', 'error');
      return;
    }

    this.loading = true;
    const values = this.profileForm.getRawValue();
    this.auth.updateProfile({
      email: values.email ?? '',
      full_name: values.full_name ?? '',
      username: values.username ?? '',
      age: values.age,
      sex: values.sex ?? '',
      allergies: values.allergies || 'Ninguna',
      password: values.password || undefined,
    }).subscribe({
      next: ({ user, message }) => {
        this.loading = false;
        this.profileForm.patchValue({ password: '' });
        this.applyUser(user);
        this.showMessage(message, 'success');
      },
      error: (error) => {
        this.loading = false;
        this.showMessage(error.error?.message ?? 'No se pudo actualizar el perfil.', 'error');
      },
    });
  }

  choosePhoto(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) {
      return;
    }

    if (!file.type.startsWith('image/')) {
      this.showMessage('Selecciona una imagen valida.', 'error');
      input.value = '';
      return;
    }

    this.selectedPhotoName = file.name;
    this.previewUrl = URL.createObjectURL(file);
    this.uploadingPhoto = true;
    this.auth.uploadProfilePhoto(file).subscribe({
      next: ({ user, message }) => {
        this.uploadingPhoto = false;
        this.applyUser(user);
        this.previewUrl = null;
        this.showMessage(message, 'success');
      },
      error: (error) => {
        this.uploadingPhoto = false;
        this.showMessage(error.error?.message ?? 'No se pudo subir la foto.', 'error');
      },
    });
  }

  deleteAccount(): void {
    const confirmed = window.confirm(
      'Esta accion eliminara tu cuenta, tus tokens y tu historial. No se puede deshacer. ¿Quieres continuar?'
    );
    if (!confirmed) {
      return;
    }

    this.deletingAccount = true;
    this.auth.deleteAccount().subscribe({
      next: () => {
        this.deletingAccount = false;
        this.router.navigateByUrl('/auth');
      },
      error: (error) => {
        this.deletingAccount = false;
        this.showMessage(error.error?.message ?? 'No se pudo eliminar la cuenta.', 'error');
      },
    });
  }

  get photoUrl(): string | null {
    return this.previewUrl ?? this.user?.profile_photo_url ?? null;
  }

  get userInitials(): string {
    return (this.user?.full_name ?? 'US')
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((part) => part[0]?.toUpperCase())
      .join('') || 'US';
  }

  private applyUser(user: User): void {
    this.user = user;
    this.profileForm.patchValue({
      full_name: user.full_name,
      username: user.username,
      email: user.email,
      age: user.age,
      sex: user.sex,
      allergies: user.allergies,
      password: '',
    });
  }

  private showMessage(message: string, type: 'success' | 'error'): void {
    this.message = message;
    this.messageType = type;
  }
}
