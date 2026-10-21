import { Component } from '@angular/core';
import { ListadoPedidos } from './features/listado-pedidos/listado-pedidos';

@Component({
  selector: 'app-root',
  imports: [ListadoPedidos],
  templateUrl: './app.html',
  styleUrl: './app.css',
})
export class App {}
