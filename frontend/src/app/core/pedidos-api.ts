import { HttpClient, HttpParams } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { forkJoin, map, Observable } from 'rxjs';

export type EstadoPedido = 'pendiente' | 'despachado' | 'entregado';

export interface ClienteResumen {
  id: number;
  nombre: string;
  ciudad: string;
}

/** Un pedido tal como lo devuelve `GET /api/pedidos` (PedidoResource). */
export interface Pedido {
  id: number;
  cliente: ClienteResumen;
  total: number;
  costo_envio: number | null;
  costo_total: number;
  fecha: string;
  estado: EstadoPedido;
  despachado_at: string | null;
  entregado_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface PaginaPedidos {
  data: Pedido[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
  };
}

export const ESTADOS: readonly { valor: EstadoPedido; etiqueta: string }[] = [
  { valor: 'pendiente', etiqueta: 'Pendiente' },
  { valor: 'despachado', etiqueta: 'Despachado' },
  { valor: 'entregado', etiqueta: 'Entregado' },
];

export function tituloEstado(estado: EstadoPedido): string {
  return estado.charAt(0).toUpperCase() + estado.slice(1);
}

/**
 * Cliente del endpoint del punto 3. La URL es relativa a propósito: en
 * producción la sirve el mismo nginx que hace proxy a Laravel (sin CORS) y
 * en desarrollo la resuelve el proxy de `ng serve`.
 */
@Injectable({ providedIn: 'root' })
export class PedidosApi {
  static readonly URL = '/api/pedidos';

  private readonly http = inject(HttpClient);

  listar(
    opciones: {
      estado?: EstadoPedido | null;
      pagina?: number;
      porPagina?: number;
    } = {},
  ): Observable<PaginaPedidos> {
    let params = new HttpParams()
      .set('page', opciones.pagina ?? 1)
      .set('por_pagina', opciones.porPagina ?? 15);

    if (opciones.estado) {
      params = params.set('estado', opciones.estado);
    }

    return this.http.get<PaginaPedidos>(PedidosApi.URL, { params });
  }

  /**
   * Totales por estado para las cuentas del filtro. Se piden al mismo
   * endpoint con `por_pagina=1` y solo se lee `meta.total`, así que no
   * duplica lógica de negocio en el cliente.
   */
  contarPorEstado(): Observable<Record<EstadoPedido, number>> {
    return forkJoin(
      ESTADOS.map(({ valor }) =>
        this.listar({ estado: valor, pagina: 1, porPagina: 1 }).pipe(
          map((pagina) => [valor, pagina.meta.total] as const),
        ),
      ),
    ).pipe(
      map(
        (pares) =>
          Object.fromEntries(pares) as Record<EstadoPedido, number>,
      ),
    );
  }
}
