import { httpResource, HttpErrorResponse } from '@angular/common/http';
import {
  Component,
  computed,
  effect,
  inject,
  resource,
  signal,
} from '@angular/core';
import { firstValueFrom } from 'rxjs';
import {
  ESTADOS,
  EstadoPedido,
  PaginaPedidos,
  PedidosApi,
  tituloEstado,
} from '../../core/pedidos-api';

const POR_PAGINA = 15;

@Component({
  selector: 'app-listado-pedidos',
  templateUrl: './listado-pedidos.html',
  styleUrl: './listado-pedidos.css',
})
export class ListadoPedidos {
  private readonly api = inject(PedidosApi);

  protected readonly estados = ESTADOS;
  protected readonly esqueleto = [1, 2, 3, 4, 5, 6, 7];

  /** Filtro activo. `null` = todos los pedidos. */
  protected readonly estado = signal<EstadoPedido | null>(null);
  protected readonly pagina = signal(1);

  /**
   * Listado reactivo: cada cambio de filtro o de página dispara la petición
   * y `httpResource` cancela la anterior si todavía está en vuelo.
   */
  protected readonly pedidos = httpResource<PaginaPedidos>(() => ({
    url: PedidosApi.URL,
    params: {
      ...(this.estado() ? { estado: this.estado()! } : {}),
      page: this.pagina(),
      por_pagina: POR_PAGINA,
    },
  }));

  /** Cuentas por estado para el filtro (una sola vez al cargar). */
  protected readonly conteos = resource({
    loader: () => firstValueFrom(this.api.contarPorEstado()),
  });

  protected readonly totalGeneral = computed(() => {
    const conteos = this.conteos.value();
    return conteos
      ? ESTADOS.reduce((suma, { valor }) => suma + conteos[valor], 0)
      : null;
  });

  protected readonly actualizadoEn = signal<Date | null>(null);

  private readonly formatoMoneda = new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  });
  private readonly formatoFecha = new Intl.DateTimeFormat('es-CO', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });
  private readonly formatoHora = new Intl.DateTimeFormat('es-CO', {
    hour: '2-digit',
    minute: '2-digit',
  });

  constructor() {
    effect(() => {
      if (this.pedidos.value()) {
        this.actualizadoEn.set(new Date());
      }
    });
  }

  protected cambiarEstado(valor: EstadoPedido | null): void {
    if (this.estado() === valor) {
      return;
    }

    this.estado.set(valor);
    this.pagina.set(1);
  }

  protected irA(pagina: number): void {
    const ultima = this.pedidos.value()?.meta.last_page ?? 1;
    this.pagina.set(Math.min(Math.max(pagina, 1), ultima));
  }

  protected actualizar(): void {
    this.pedidos.reload();
    this.conteos.reload();
  }

  protected id(valor: number): string {
    return String(valor).padStart(3, '0');
  }

  protected moneda(valor: number | null): string {
    return valor === null ? '—' : this.formatoMoneda.format(valor);
  }

  protected fecha(valor: string): string {
    return this.formatoFecha.format(new Date(`${valor}T00:00:00`));
  }

  protected hora(valor: Date): string {
    return this.formatoHora.format(valor);
  }

  protected etiqueta(estado: EstadoPedido): string {
    return tituloEstado(estado);
  }

  protected descripcionError(error: unknown): string {
    if (error instanceof HttpErrorResponse) {
      return error.status === 0
        ? 'No se pudo contactar al servidor. Revisa que el stack esté arriba.'
        : `El servidor respondió con un error ${error.status}.`;
    }

    return 'Ocurrió un error inesperado al consultar la API.';
  }
}
