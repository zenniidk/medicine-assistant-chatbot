import { Component, ElementRef, ViewChild } from '@angular/core';
import { Auth, ScanResponse } from '../services/auth';

interface TesseractGlobal {
  createWorker: (
    langs?: string | string[],
    oem?: number,
    options?: { logger?: (message: TesseractProgress) => void }
  ) => Promise<TesseractWorker>;
}

interface TesseractProgress {
  status?: string;
  progress?: number;
}

interface TesseractWorker {
  recognize: (image: string) => Promise<{ data: { text: string } }>;
  terminate: () => Promise<void>;
}

@Component({
  selector: 'app-scanner',
  templateUrl: './scanner.page.html',
  styleUrls: ['./scanner.page.scss'],
  standalone: false,
})
export class ScannerPage {
  @ViewChild('cameraVideo') cameraVideo?: ElementRef<HTMLVideoElement>;
  @ViewChild('captureCanvas') captureCanvas?: ElementRef<HTMLCanvasElement>;

  ocrText = 'Ibuprofeno 400 mg tabletas';
  loading = false;
  cameraActive = false;
  cameraLoading = false;
  ocrLoading = false;
  ocrProgress = '';
  result: ScanResponse | null = null;
  message = '';
  cameraMessage = '';
  capturedImage: string | null = null;
  private cameraStream: MediaStream | null = null;
  private ocrWorker: TesseractWorker | null = null;
  private tesseractLoader?: Promise<TesseractGlobal>;

  constructor(private auth: Auth) {}

  ionViewWillLeave(): void {
    this.stopCamera();
    void this.terminateOcrWorker();
  }

  useExample(text: string): void {
    this.ocrText = text;
    this.result = null;
    this.message = '';
  }

  async startCamera(): Promise<void> {
    if (!navigator.mediaDevices?.getUserMedia) {
      this.cameraMessage = 'Este navegador no permite abrir la camara desde aqui.';
      return;
    }

    this.cameraLoading = true;
    this.cameraMessage = '';
    try {
      this.cameraStream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: { ideal: 'environment' } },
        audio: false,
      });

      const video = this.cameraVideo?.nativeElement;
      if (video) {
        video.srcObject = this.cameraStream;
        await video.play();
      }
      this.cameraActive = true;
    } catch {
      this.cameraMessage = 'No se pudo abrir la camara. Revisa permisos del navegador.';
    } finally {
      this.cameraLoading = false;
    }
  }

  stopCamera(): void {
    this.cameraStream?.getTracks().forEach((track) => track.stop());
    this.cameraStream = null;
    this.cameraActive = false;
    const video = this.cameraVideo?.nativeElement;
    if (video) {
      video.srcObject = null;
    }
  }

  capturePhoto(): void {
    const video = this.cameraVideo?.nativeElement;
    const canvas = this.captureCanvas?.nativeElement;
    if (!video || !canvas || !this.cameraActive) {
      return;
    }

    canvas.width = video.videoWidth || 1280;
    canvas.height = video.videoHeight || 720;
    const context = canvas.getContext('2d');
    context?.drawImage(video, 0, 0, canvas.width, canvas.height);
    this.capturedImage = canvas.toDataURL('image/png');
    this.result = null;
    this.message = '';
  }

  chooseImage(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) {
      return;
    }

    if (!file.type.startsWith('image/')) {
      this.cameraMessage = 'Selecciona una imagen valida.';
      input.value = '';
      return;
    }

    const reader = new FileReader();
    reader.onload = () => {
      this.capturedImage = typeof reader.result === 'string' ? reader.result : null;
      this.cameraMessage = '';
      this.result = null;
      this.message = '';
    };
    reader.readAsDataURL(file);
  }

  async extractTextFromImage(): Promise<string> {
    if (!this.capturedImage) {
      this.cameraMessage = 'Primero captura o carga una imagen.';
      return '';
    }

    this.ocrLoading = true;
    this.ocrProgress = 'Preparando OCR...';
    this.cameraMessage = '';
    try {
      const tesseract = await this.loadTesseract();
      if (!this.ocrWorker) {
        this.ocrWorker = await tesseract.createWorker(['eng', 'spa'], 1, {
          logger: (message) => this.updateOcrProgress(message),
        });
      }

      const image = await this.prepareImageForOcr(this.capturedImage);
      const result = await this.ocrWorker.recognize(image);
      const text = this.cleanOcrText(result.data.text);
      if (!text) {
        this.cameraMessage = 'No se detecto texto claro. Intenta con mejor luz o acercando la etiqueta.';
        return '';
      }

      this.ocrText = text;
      return text;
    } catch {
      this.cameraMessage = 'No se pudo extraer texto de la imagen. Revisa tu conexion e intenta de nuevo.';
      return '';
    } finally {
      this.ocrLoading = false;
      this.ocrProgress = '';
    }
  }

  async analyzeImage(): Promise<void> {
    const text = await this.extractTextFromImage();
    if (text) {
      this.analyze();
    }
  }

  analyze(): void {
    const text = this.ocrText.trim();
    if (text.length < 3) {
      this.message = 'Escribe el texto detectado en la etiqueta.';
      return;
    }

    this.loading = true;
    this.message = '';
    this.result = null;
    this.auth.scanMedicine(text).subscribe({
      next: (response) => {
        this.loading = false;
        this.result = response;
      },
      error: (error) => {
        this.loading = false;
        this.message = error.error?.message ?? 'No se pudo analizar el escaneo.';
      },
    });
  }

  get levelLabel(): string {
    if (!this.result) {
      return '';
    }
    const labels = {
      safe: 'Sin coincidencia directa',
      warning: 'Revision recomendada',
      danger: 'Alerta de riesgo',
    };
    return labels[this.result.result_level];
  }

  private loadTesseract(): Promise<TesseractGlobal> {
    const windowWithTesseract = window as typeof window & { Tesseract?: TesseractGlobal };
    if (windowWithTesseract.Tesseract) {
      return Promise.resolve(windowWithTesseract.Tesseract);
    }

    if (!this.tesseractLoader) {
      this.tesseractLoader = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js';
        script.async = true;
        script.onload = () => {
          if (windowWithTesseract.Tesseract) {
            resolve(windowWithTesseract.Tesseract);
          } else {
            reject(new Error('Tesseract no disponible'));
          }
        };
        script.onerror = () => reject(new Error('No se pudo cargar Tesseract.js'));
        document.head.appendChild(script);
      });
    }

    return this.tesseractLoader;
  }

  private updateOcrProgress(message: TesseractProgress): void {
    const progress = typeof message.progress === 'number'
      ? ` ${Math.round(message.progress * 100)}%`
      : '';
    this.ocrProgress = `${message.status ?? 'Procesando'}${progress}`;
  }

  private async prepareImageForOcr(imageSource: string): Promise<string> {
    const image = await this.loadImage(imageSource);
    const canvas = document.createElement('canvas');
    const targetWidth = Math.max(image.width, 1400);
    const scale = targetWidth / image.width;
    canvas.width = targetWidth;
    canvas.height = Math.round(image.height * scale);
    const context = canvas.getContext('2d');
    if (!context) {
      return imageSource;
    }

    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.filter = 'grayscale(1) contrast(1.25)';
    context.drawImage(image, 0, 0, canvas.width, canvas.height);
    return canvas.toDataURL('image/png');
  }

  private loadImage(source: string): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
      const image = new Image();
      image.onload = () => resolve(image);
      image.onerror = () => reject(new Error('No se pudo leer la imagen'));
      image.src = source;
    });
  }

  private cleanOcrText(text: string): string {
    return text
      .split('\n')
      .map((line) => line.trim())
      .filter(Boolean)
      .join('\n');
  }

  private async terminateOcrWorker(): Promise<void> {
    if (this.ocrWorker) {
      await this.ocrWorker.terminate();
      this.ocrWorker = null;
    }
  }
}
