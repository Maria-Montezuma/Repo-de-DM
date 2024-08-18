<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empleado;
use App\Models\RecepcionesMercancia;
use App\Models\Devolucione;
use App\Models\DetallesDevolucione;
use App\Models\Suministro;
use Illuminate\Support\Facades\DB;

class DevolucionController extends Controller
{
    public function index()
    {
        $empleados = Empleado::all();
        $recepciones = RecepcionesMercancia::with(['ordenes_compra.proveedore', 'detalles_recepciones_mercancias'])
            ->whereHas('detalles_recepciones_mercancias', function($query) {
                $query->where('status_recepcion', 0);
            })->get();
        $suministros = Suministro::all(); 
        $devoluciones = Devolucione::with(['detalles_devoluciones.suministro', 'empleado', 'recepciones_mercancia'])->get();

        return view('devolucion', compact('empleados', 'recepciones', 'devoluciones', 'suministros'));
    }

    public function store(Request $request)
{
    $validatedData = $request->validate([
        'Recepciones_mercancias_idRecepcion_mercancia' => 'required|exists:recepciones_mercancias,idRecepcion_mercancia',
        'Empleados_idEmpleados' => 'required|exists:empleados,idEmpleados',
        'fecha_devolucion' => 'required|date',
        'Suministros_idSuministro' => 'required|array',
        'Suministros_idSuministro.*' => 'exists:suministros,idSuministro',
        'cantidad_devuelta' => 'required|array',
        'cantidad_devuelta.*' => 'numeric|min:0',
        'status_devolucion' => 'required|array',
        'status_devolucion.*' => 'in:Sobrante,Faltante,Dañado,Otro',
        'motivo' => 'required|array',
        'motivo.*' => 'string',
    ]);

    try {
        DB::beginTransaction();

        // Crear la devolución
        $devolucion = new Devolucione();
        $devolucion->Recepciones_mercancias_idRecepcion_mercancia = $request->Recepciones_mercancias_idRecepcion_mercancia;
        $devolucion->Empleados_idEmpleados = $request->Empleados_idEmpleados;
        $devolucion->fecha_devolucion = $request->fecha_devolucion;
        $devolucion->save();

        // Guardar los detalles de la devolución
        foreach ($request->Suministros_idSuministro as $index => $suministroId) {
            $detalle = new DetallesDevolucione();
            $detalle->Devoluciones_idDevoluciones = $devolucion->idDevoluciones;
            $detalle->Suministros_idSuministro = $suministroId;
            $detalle->cantidad_devuelta = $request->cantidad_devuelta[$index];
            $detalle->status_devolucion = $request->status_devolucion[$index];
            $detalle->motivo = $request->motivo[$index];
            $detalle->save();
        }

        DB::commit();

        return redirect()->back()->with('success', 'Devolución guardada correctamente.');
    } catch (\Exception $e) {
        DB::rollback();
        return redirect()->back()->withErrors(['error' => 'Hubo un problema al guardar la devolución: ' . $e->getMessage()]);
    }
}
    

    public function getRecepcionDetails($id)
    {
        $recepcion = RecepcionesMercancia::with([
            'detalles_recepciones_mercancias.suministro', 
            'empleado',
            'ordenes_compra.detalles_ordenes_compras.suministro',
            'ordenes_compra.proveedore'
        ])->find($id);

        if ($recepcion) {
            return response()->json([
                'idRecepcion_mercancia' => $recepcion->idRecepcion_mercancia,
                'fecha_recepcion' => $recepcion->fecha_recepcion->format('d/m/Y'),
                'empleado' => $recepcion->empleado ? $recepcion->empleado->nombre_empleado . ' ' . $recepcion->empleado->apellido_empleado : 'Desconocido',
                'empleado_orden' => $recepcion->ordenes_compra->empleado ? $recepcion->ordenes_compra->empleado->nombre_empleado . ' ' . $recepcion->ordenes_compra->empleado->apellido_empleado : 'Desconocido',
                'proveedor' => $recepcion->ordenes_compra->proveedore ? $recepcion->ordenes_compra->proveedore->nombre_empresa : 'Desconocido',
                'detalles_recepciones_mercancias' => $recepcion->detalles_recepciones_mercancias->map(function ($detalle) {
                    return [
                        'suministro' => $detalle->suministro ? $detalle->suministro->nombre_suministro : 'Desconocido',
                        'cantidad_recibida' => $detalle->cantidad_recibida,
                        'estado' => $detalle->status_recepcion == 1 ? 'Aceptado' : 'Rechazado'
                    ];
                }),
                'detalles_ordenes_compras' => $recepcion->ordenes_compra->detalles_ordenes_compras->map(function ($detalle) {
                    return [
                        'suministro' => $detalle->suministro ? $detalle->suministro->nombre_suministro : 'Desconocido',
                        'cantidad_pedida' => $detalle->cantidad_pedida
                    ];
                })
            ]);
        }

        return response()->json(['error' => 'Recepción no encontrada'], 404);
    }

    public function cancel($id)
{
    $devolucion = Devolucione::findOrFail($id);
    $now = \Carbon\Carbon::now();
    $created_at = \Carbon\Carbon::parse($devolucion->created_at);
    $minutes_passed = $now->diffInMinutes($created_at);

    if ($minutes_passed <= 3) {
        try {
            DB::beginTransaction();

            // Actualizar el estado de la devolución
            $devolucion->status_devolucion = 'Cancelada';
            $devolucion->save();

            // Aquí podrías agregar lógica adicional si es necesario

            DB::commit();
            return redirect()->route('devolucion')->with('success', 'Devolución cancelada correctamente.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('devolucion')->withErrors(['error' => 'Hubo un problema al cancelar la devolución: ' . $e->getMessage()]);
        }
    } else {
        return redirect()->route('devolucion')->with('error', 'No se puede cancelar esta devolución porque ha pasado más de 3 minutos.');
    }
}
}
