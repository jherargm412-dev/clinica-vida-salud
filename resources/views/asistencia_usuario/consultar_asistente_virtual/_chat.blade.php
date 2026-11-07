{{-- CU24 Consultar Asistente Virtual · UI_AsistenteVirtual (parcial compartido por Paciente y Visitante)
     Recibe: $conversacion (sesión), $prefijo ("asistente." o "asistente.publico.") y $categorias (nombre => ícono).
     Todo funciona con formularios normales (sin API): cada envío vuelve a esta misma pantalla. --}}

<style>
    .cvs-chat { height: 55vh; min-height: 320px; overflow-y: auto; }
    .cvs-burbuja { max-width: 85%; white-space: normal; word-wrap: break-word; }
    .cvs-burbuja-usuario { background: var(--bs-primary); color: #fff; border-bottom-right-radius: .25rem !important; }
    .cvs-burbuja-asistente { background: var(--bs-tertiary-bg); border: 1px solid var(--bs-border-color); border-bottom-left-radius: .25rem !important; }
    .cvs-sugerencia { text-align: left; }
</style>

@php
    // Botones de categorías (paso 2 y flujo 3a). Se usan arriba del cuadro de texto y dentro de algunas respuestas.
    $botonesCategorias = function () use ($categorias, $prefijo) {
        $html = '';
        foreach ($categorias as $nombre => $icono) {
            $html .= '<form method="POST" action="' . e(route($prefijo . 'categoria')) . '" class="d-inline">'
                . csrf_field()
                . '<input type="hidden" name="categoria" value="' . e($nombre) . '">'
                . '<button type="submit" class="btn btn-sm btn-outline-primary rounded-pill mb-1">'
                . '<i class="bi ' . e($icono) . ' me-1"></i>' . e($nombre) . '</button></form> ';
        }
        return $html;
    };
@endphp

<div class="card border-0 shadow-sm">
    {{-- Encabezado del chat --}}
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                  style="width: 36px; height: 36px"><i class="bi bi-robot"></i></span>
            <div>
                <div class="fw-semibold lh-sm">Asistente virtual</div>
                <small class="text-muted">Respuestas automáticas a preguntas frecuentes</small>
            </div>
        </div>
        {{-- "Limpiar conversación" --}}
        @if ($conversacion)
            <form method="POST" action="{{ route($prefijo . 'limpiar') }}" data-confirmar="¿Borrar la conversación?">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Limpiar conversación">
                    <i class="bi bi-trash3"></i><span class="d-none d-sm-inline ms-1">Limpiar</span>
                </button>
            </form>
        @endif
    </div>

    {{-- Conversación --}}
    <div class="card-body cvs-chat" id="cvs-chat">
        {{-- Saludo inicial (paso 2): siempre visible, no se guarda en la sesión --}}
        <div class="d-flex mb-3">
            <div class="cvs-burbuja cvs-burbuja-asistente rounded-4 px-3 py-2">
                Hola, soy el asistente de la Clínica Vida Salud. ¿En qué te ayudo?
                Escribe tu pregunta o elige una categoría.
            </div>
        </div>

        @foreach ($conversacion as $mensaje)
            @if ($mensaje['de'] === 'usuario')
                <div class="d-flex justify-content-end mb-3">
                    <div class="cvs-burbuja cvs-burbuja-usuario rounded-4 px-3 py-2">
                        {{ $mensaje['texto'] }}
                        <div class="small opacity-75 text-end">{{ $mensaje['hora'] }}</div>
                    </div>
                </div>
            @else
                <div class="d-flex mb-3">
                    <div class="cvs-burbuja cvs-burbuja-asistente rounded-4 px-3 py-2">
                        {{-- Pregunta frecuente que se usó para responder --}}
                        @if (! empty($mensaje['referencia']))
                            <div class="small fw-semibold text-primary mb-1">
                                <i class="bi bi-question-circle me-1"></i>{{ $mensaje['referencia'] }}
                            </div>
                        @endif

                        {{-- e() escapa el texto; nl2br respeta los saltos de línea de la respuesta --}}
                        {!! nl2br(e($mensaje['texto'])) !!}

                        {{-- Preguntas sugeridas (flujos 3a y 6a): al tocarlas se muestra su respuesta --}}
                        @if (! empty($mensaje['sugerencias']))
                            <div class="d-grid gap-1 mt-2">
                                @foreach ($mensaje['sugerencias'] as $sugerencia)
                                    <form method="POST" action="{{ route($prefijo . 'pregunta', $sugerencia['id']) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary w-100 cvs-sugerencia">
                                            <i class="bi bi-chat-left-text me-1"></i>{{ $sugerencia['pregunta'] }}
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @endif

                        @if (! empty($mensaje['categorias']))
                            <div class="mt-2">{!! $botonesCategorias() !!}</div>
                        @endif

                        <div class="small text-muted">{{ $mensaje['hora'] }}</div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Categorías + cuadro para escribir --}}
    <div class="card-footer bg-transparent">
        <div class="mb-2">{!! $botonesCategorias() !!}</div>

        <form method="POST" action="{{ route($prefijo . 'enviar') }}" class="needs-validation" novalidate>
            @csrf
            <div class="input-group has-validation">
                <input type="text" name="mensaje" id="cvs-mensaje" maxlength="200" required autocomplete="off"
                       value="{{ old('mensaje') }}" placeholder="Escribe tu pregunta…"
                       class="form-control @error('mensaje') is-invalid @enderror" aria-label="Tu pregunta">
                <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i><span class="d-none d-sm-inline ms-1">Enviar</span></button>
                {{-- Flujos 4a y 4b --}}
                <div class="invalid-feedback">@error('mensaje'){{ $message }}@else Escriba su pregunta @enderror</div>
            </div>
        </form>
    </div>
</div>

<script>
    // Muestra siempre el último mensaje y deja el cursor listo para escribir
    document.addEventListener('DOMContentLoaded', () => {
        const chat = document.getElementById('cvs-chat');
        if (chat) chat.scrollTop = chat.scrollHeight;
        document.getElementById('cvs-mensaje')?.focus();
    });
</script>
