import { provideHttpClient } from '@angular/common/http';
import {
  HttpTestingController,
  provideHttpClientTesting,
} from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { App } from './app';
import { PaginaPedidos } from './core/pedidos-api';

const PAGINA_VACIA: PaginaPedidos = {
  data: [],
  links: { first: null, last: null, prev: null, next: null },
  meta: {
    current_page: 1,
    from: null,
    last_page: 1,
    per_page: 15,
    to: null,
    total: 0,
  },
};

describe('App', () => {
  let httpMock: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [App],
      providers: [provideHttpClient(), provideHttpClientTesting()],
    }).compileComponents();

    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => httpMock.verify());

  it('monta la barra de marca y el listado de pedidos', async () => {
    const fixture = TestBed.createComponent(App);
    fixture.detectChanges();

    httpMock.match(() => true).forEach((peticion) => peticion.flush(PAGINA_VACIA));
    await fixture.whenStable();

    const compilado = fixture.nativeElement as HTMLElement;

    expect(compilado.querySelector('.marca-nombre')?.textContent).toContain(
      'pedidos',
    );
    expect(compilado.querySelector('h1')?.textContent).toContain('Pedidos');
    expect(compilado.querySelector('.vacio-titulo')?.textContent).toContain(
      'No hay pedidos',
    );
  });
});
