import { provideHttpClient } from '@angular/common/http';
import {
  HttpTestingController,
  provideHttpClientTesting,
} from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { PedidosApi } from './pedidos-api';

const PAGINA = {
  data: [],
  links: { first: null, last: null, prev: null, next: null },
};

describe('PedidosApi', () => {
  let api: PedidosApi;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });

    api = TestBed.inject(PedidosApi);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => httpMock.verify());

  it('envía el filtro, la página y el tamaño de página', () => {
    api.listar({ estado: 'pendiente', pagina: 2, porPagina: 15 }).subscribe();

    const peticion = httpMock.expectOne(
      (req) => req.url === '/api/pedidos',
    );

    expect(peticion.request.method).toBe('GET');
    expect(peticion.request.params.get('estado')).toBe('pendiente');
    expect(peticion.request.params.get('page')).toBe('2');
    expect(peticion.request.params.get('por_pagina')).toBe('15');

    peticion.flush({
      ...PAGINA,
      meta: {
        current_page: 2,
        from: 16,
        last_page: 4,
        per_page: 15,
        to: 30,
        total: 48,
      },
    });
  });

  it('omite el parámetro estado cuando el filtro es "todos"', () => {
    api.listar({ estado: null }).subscribe();

    const peticion = httpMock.expectOne(
      (req) => req.url === '/api/pedidos',
    );

    expect(peticion.request.params.has('estado')).toBe(false);
    expect(peticion.request.params.get('page')).toBe('1');

    peticion.flush({
      ...PAGINA,
      meta: {
        current_page: 1,
        from: 1,
        last_page: 1,
        per_page: 15,
        to: 0,
        total: 0,
      },
    });
  });

  it('agrega los totales por estado en una sola estructura', () => {
    let resultado: Record<string, number> | undefined;

    api.contarPorEstado().subscribe((conteos) => (resultado = conteos));

    const pendiente = httpMock.expectOne(
      (req) => req.url === '/api/pedidos' && req.params.get('estado') === 'pendiente',
    );
    const despachado = httpMock.expectOne(
      (req) => req.url === '/api/pedidos' && req.params.get('estado') === 'despachado',
    );
    const entregado = httpMock.expectOne(
      (req) => req.url === '/api/pedidos' && req.params.get('estado') === 'entregado',
    );

    const paginaConTotal = (total: number) => ({
      ...PAGINA,
      meta: {
        current_page: 1,
        from: total > 0 ? 1 : null,
        last_page: 1,
        per_page: 1,
        to: total > 0 ? 1 : null,
        total,
      },
    });

    pendiente.flush(paginaConTotal(25));
    despachado.flush(paginaConTotal(9));
    entregado.flush(paginaConTotal(14));

    expect(resultado).toEqual({ pendiente: 25, despachado: 9, entregado: 14 });
  });
});
