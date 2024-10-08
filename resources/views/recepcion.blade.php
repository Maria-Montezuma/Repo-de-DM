@extends('layouts.layout')

@section('content')
<div class="container formulario-container mt-5">
    <h2 class="mb-4 text-center">Recepción de Mercancía</h2>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Detalles de la Orden de Compra -->
   <div id="infoOrdenCompra" class="mb-4"></div>
   
   @if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

    <form action="{{ route('recepcion.store') }}" method="POST">
    @csrf
    <div class="row mb-3">
    <!-- Modal para ver detalles de recepción -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #8B4513; color: white;">
                    <h5 class="modal-title" id="viewModalLabel">
                        <i class="fas fa-file-invoice me-2"></i>Detalles de Recepción de Mercancía
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="background-color: #FFF8DC;">
                    <div id="modalBody" class="p-3">
                        <!-- Los detalles se cargarán aquí dinámicamente -->
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #DEB887;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

        <!-- los detalles de como aparecen los datos en recepcion de orden de compra -->
        <div id="detallesOrdenCompra" style="display: none;">
            <h4>Detalles de la Orden de Compra</h4>
            <p><strong>Fecha de emisión:</strong> <span id="fechaEmision"></span></p>
            <p><strong>Fecha de entrega:</strong> <span id="fechaEntrega"></span></p>
            <p><strong>Proveedor:</strong> <span id="proveedor"></span></p>
            <p><strong>Total:</strong> <span id="total"></span></p>
            <div id="listaProductos"></div>
        </div>

        <!-- Orden de compra -->
        <div class="col-12 col-lg-4 mb-3 mb-lg-0">
            <label for="ordenCompra" class="form-label">N° Orden de compra</label>
            <select class="form-control" id="Ordenes_compras_idOrden_compra" name="Ordenes_compras_idOrden_compra" required>
                <option value="">Seleccionar Orden de Compra</option>
                @foreach($ordenesCompra as $ordenCompra)
                <option value="{{ $ordenCompra->idOrden_compra }}">
                    {{ $ordenCompra->idOrden_compra }} - {{ $ordenCompra->proveedore->nombre_empresa }}
                </option>
                @endforeach
            </select>
        </div>
        <!-- Empleado -->
        <div class="col-12 col-lg-4 mb-3 mb-lg-0">
            <label for="empleado" class="form-label">Empleado</label>
            <select class="form-control" id="Empleados_idEmpleados" name="Empleados_idEmpleados" required>
                <option value="">Seleccionar Empleado</option>
                @foreach($empleados as $empleado)
                <option value="{{ $empleado->idEmpleados }}" {{ old('Empleados_idEmpleados') == $empleado->idEmpleados ? 'selected' : '' }}>
                    {{ $empleado->nombre_empleado }} {{ $empleado->apellido_empleado }}
                </option>
                @endforeach
            </select>
        </div>
        <!-- Fecha de Recepcion de Mercancia  -->
        <div class="col-12 col-lg-4 mb-3 mb-lg-0">
        <label for="fecha_recepcion">Fecha de Emisión</label>
        <p class="form-control mt-2" id="fecha_recepcion" name="fecha_recepcion">{{ \Carbon\Carbon::now()->format('Y-m-d') }}</p>
        </div>
    </div>

    <table id="productTable" class="table table-bordered">
        <thead>
            <tr>
                <th>Suministro</th>
                <th>Cantidad Recibida</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <tr class="product-row">
                <td>
                <select class="form-control" name="suministro[]" required>
                    @foreach($suministros as $suministro)
                    <option value="{{ $suministro->idSuministro }}">{{ $suministro->nombre_suministro }}</option>
                    @endforeach
                </select>
                </td>
                <td>
                    <input type="number" class="form-control" name="cantidad_recibida[]" required>
                </td>
                <td>
                    <select class="form-select" name="status[]" required>
                        <option value="">Seleccionar...</option>
                        <option value="aceptar" {{ old('estado') == 'aceptar' ? 'selected' : '' }}>Aceptar</option>
                        <option value="rechazar" {{ old('estado') == 'rechazar' ? 'selected' : '' }}>Rechazar</option>
                    </select>
                </td>
            </tr>   
        </tbody>
    </table>
    <div>
        <button type="reset" class="btn btn-primary mt-2" title="Limpiar"> Limpiar <i class="fa-solid fa-broom"></i></button>
        <button type="button" id="addRow" class="btn btn-dark mt-2" title="Agregar Fila">Agregar Fila </button>
        <button type="submit" class="btn btn-success me-2 mt-2" title="Guardar"> Guardar <i class="fa-solid fa-box-archive"></i></button>
    </div>
</form>

</div>

<div class="container mt-5">
    <h3>Lista de Recepciones</h3>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID Recepción</th>
                    <th>ID Orden de Compra</th>
                    <th>Empleado</th>
                    <th>Fecha Recepción</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recepcionesMercancia as $recepcion)
                <tr>
                    <td>{{ $recepcion->idRecepcion_mercancia }}</td>
                    <td>{{ $recepcion->ordenes_compra->idOrden_compra }}</td>
                    <td>{{ $recepcion->empleado->nombre_empleado }} {{ $recepcion->empleado->apellido_empleado }}</td>
                    <td>{{ $recepcion->fecha_recepcion->format('d-m-Y') }}</td>
                    <td>
                        @php
                             $statusInfo = $recepcion->getStatus();
                        @endphp
                    <span class="badge {{ $statusInfo['badge'] }}">{{ $statusInfo['status'] }}</span>
                        @if ($statusInfo['status'] == 'Parcial')
                            <div class="text-muted mt-1">
                                Suministro Parcial.
                            </div>
                        @elseif ($statusInfo['status'] != 'Sin Estado')
                            <div class="text-muted mt-1">
                                Suministro {{ $statusInfo['status'] }}.
                            </div>
                        @endif
                    </td>
                    <td>
                    <a href="{{ route('recepcion.edit', $recepcion->idRecepcion_mercancia) }}" class="btn btn-sm btn-secondary me-1">Editar <i class="fas fa-edit"></i></a>

                    <a href="#" class="btn btn-sm btn-warning view-order" data-id="{{ $recepcion->idRecepcion_mercancia }}" data-bs-toggle="modal" data-bs-target="#viewModal" title="Ver">Ver<i class="fas fa-eye"></i></a>
                </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{ asset('js/recepcion.js') }}"></script>
@endsection