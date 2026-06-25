import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { Subscription } from 'rxjs';
import { Auth, User } from '../services/auth';

@Component({
  selector: 'app-home',
  templateUrl: './home.page.html',
  styleUrls: ['./home.page.scss'],
  standalone: false,
})
export class HomePage {
  user: User | null = null;
  private userSubscription?: Subscription;

  constructor(private auth: Auth, private router: Router) {}

  ionViewWillEnter(): void {
    this.userSubscription = this.auth.user$.subscribe((user) => {
      this.user = user;
    });
    this.auth.profile().subscribe({
      error: () => {
        this.auth.clearSession();
        this.router.navigateByUrl('/auth');
      },
    });
  }

  ionViewWillLeave(): void {
    this.userSubscription?.unsubscribe();
  }

  get userInitials(): string {
    return (this.user?.full_name ?? 'US')
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((part) => part[0]?.toUpperCase())
      .join('') || 'US';
  }

  logout(): void {
    this.auth.logout().subscribe({
      next: () => this.router.navigateByUrl('/auth'),
      error: () => {
        this.auth.clearSession();
        this.router.navigateByUrl('/auth');
      },
    });
  }
}
