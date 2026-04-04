@extends('layouts.app')

@section('title', 'Agenda - CRM360')

@section('styles')
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
    <style>
        .fc { 
            --fc-button-bg-color: #3b82f6;
            --fc-button-border-color: #3b82f6;
            --fc-button-hover-bg-color: #2563eb;
            --fc-button-hover-border-color: #2563eb;
            --fc-button-active-bg-color: #1d4ed8;
            --fc-button-active-border-color: #1d4ed8;
            height: calc(100vh - 100px);
        }
        .dark .fc {
            --fc-page-bg-color: #0f172a;
            --fc-list-event-hover-bg-color: #1e293b;
            --fc-border-color: #334155;
        }
        .fc-event { cursor: pointer; padding: 2px 4px; border-radius: 4px; border: none !important; }
        .fc-toolbar-title { font-weight: 700 !important; color: inherit !important; }
        
        /* Custom Tooltip simple style */
        #calendar-tooltip {
            display: none;
            position: absolute;
            z-index: 1000;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
    </style>
@endsection

@section('content')
<div class="flex-1 bg-white dark:bg-slate-900 p-4 sm:p-6 lg:p-8 transition-colors overflow-y-auto">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
        <div id='calendar'></div>
    </div>
</div>

<!-- Modal para detalles del evento -->
<div id="eventModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900/75 dark:bg-slate-900/90 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="window.closeEventModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-slate-200 dark:border-slate-700">
            <div class="bg-white dark:bg-slate-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div id="modalIconContainer" class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/50 sm:mx-0 sm:h-10 sm:w-10">
                        <i id="modalIcon" class="fa-solid fa-graduation-cap text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-xl leading-6 font-bold text-slate-900 dark:text-white" id="modalEventTitle">
                            Detalles de la Actividad
                        </h3>
                        <div class="mt-4 space-y-3">
                            <div class="flex items-start gap-3">
                                <div class="w-24 text-sm font-semibold text-slate-500 dark:text-slate-400">Contacto:</div>
                                <div id="modalEventContact" class="text-sm text-slate-900 dark:text-slate-100 font-medium"></div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-24 text-sm font-semibold text-slate-500 dark:text-slate-400">Fecha:</div>
                                <div id="modalEventDate" class="text-sm text-slate-900 dark:text-slate-100"></div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-24 text-sm font-semibold text-slate-500 dark:text-slate-400">Estado:</div>
                                <div id="modalEventStatus" class="inline-flex"></div>
                            </div>
                            <div class="pt-2 border-t border-slate-100 dark:border-slate-700">
                                <label for="modalEventDescription" class="text-sm font-semibold text-slate-500 dark:text-slate-400 mb-1 block">Notas / Descripción:</label>
                                <textarea id="modalEventDescription" rows="4" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-3 text-sm bg-slate-50 dark:bg-slate-900/50 text-slate-700 dark:text-slate-300 italic focus:ring-2 focus:ring-blue-500 outline-none resize-none transition-all" placeholder="Detalles de la actividad..."></textarea>
                            </div>
                            <!-- Formulario de Reagendamiento -->
                            <div id="rescheduleFormContainer" class="pt-3 border-t border-slate-100 dark:border-slate-700 hidden">
                                <div class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2 flex items-center gap-2">
                                    <i class="fa-solid fa-calendar-plus text-blue-500"></i> Nueva Cita
                                </div>
                                <div class="space-y-3 bg-blue-50/50 dark:bg-blue-900/20 p-3 rounded-xl border border-blue-100 dark:border-blue-900/30">
                                    <div>
                                        <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Nueva Fecha y Hora:</label>
                                        <input type="datetime-local" id="rescheduleDate" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Motivo / Nota:</label>
                                        <textarea id="rescheduleDescription" rows="2" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none resize-none" placeholder="Opcional..."></textarea>
                                    </div>
                                    <button type="button" onclick="window.confirmReschedule()" id="btnConfirmReschedule" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-lg text-sm transition-colors shadow-sm">
                                        Confirmar Nuevo Agendamiento
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 dark:bg-slate-800/80 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-slate-200 dark:border-slate-700 gap-2">
                <button type="button" id="btnCompleteActivity" onclick="window.completeActivityFromCalendar()" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-emerald-600 text-base font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:w-auto sm:text-sm transition-colors hidden">
                    <i class="fa-solid fa-square-check mr-2"></i> Completar
                </button>
                <button type="button" id="btnRescheduleToggle" onclick="window.toggleRescheduleForm()" class="w-full inline-flex justify-center rounded-lg border border-slate-300 dark:border-slate-600 shadow-sm px-4 py-2 bg-white dark:bg-slate-700 text-base font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm transition-colors hidden">
                    <i class="fa-solid fa-calendar-plus mr-2"></i> Reagendar
                </button>
                <button type="button" onclick="window.closeEventModal()" class="w-full inline-flex justify-center rounded-lg border border-slate-300 dark:border-slate-600 shadow-sm px-6 py-2 bg-white dark:bg-slate-700 text-base font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm transition-colors">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <!-- FullCalendar JS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var selectedActivityId = null;

            var calendar = new FullCalendar.Calendar(calendarEl, {
                // ... (previous config)
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
                },
                locale: 'es',
                firstDay: 1,
                buttonText: {
                    today: 'Hoy',
                    month: 'Mes',
                    week: 'Semana',
                    day: 'Día',
                    list: 'Agenda'
                },
                events: "{{ route('api.events') }}",
                eventClick: function(info) {
                    showEventDetails(info.event);
                },
                eventContent: function(arg) {
                    const status = arg.event.extendedProps.status;
                    const isCompleted = status === 'completed';
                    
                    const statusIcon = isCompleted 
                        ? '<i class="fa-solid fa-square-check text-emerald-500"></i>' 
                        : '<i class="fa-solid fa-square-xmark text-rose-500"></i>';
                    
                    const weight = isCompleted ? 'font-normal text-slate-800 dark:text-slate-200' : 'font-bold text-slate-900 dark:text-slate-100';
                    
                    return {
                        html: `<div class="fc-content flex items-center gap-1.5 overflow-hidden py-0.5 px-1 rounded opacity-100" title="${arg.event.title}">
                                <div class="shrink-0 flex items-center gap-1">
                                    ${statusIcon}
                                </div>
                                <div class="truncate ${weight}">${arg.event.title}</div>
                               </div>`
                    };
                },
                height: 'auto',
                expandRows: true,
                dayMaxEvents: true
            });
            calendar.render();

            function showEventDetails(event) {
                selectedActivityId = event.id;
                const props = event.extendedProps;
                document.getElementById('modalEventTitle').innerText = event.title;
                document.getElementById('modalEventContact').innerText = props.contact_name;
                document.getElementById('modalEventDate').innerText = event.start.toLocaleString();
                
                const descInput = document.getElementById('modalEventDescription');
                descInput.value = props.notes || props.description || '';
                
                // Status translations and badge
                const statusEl = document.getElementById('modalEventStatus');
                const isFromReschedule = props.notes && props.notes.toUpperCase().includes('REAGENDADO');
                
                const translations = {
                    'scheduled': isFromReschedule ? 'PENDIENTE (REAGENDADO)' : 'Pendiente',
                    'completed': 'Completado',
                    'rescheduled': 'Reagendado'
                };
                const statusText = translations[props.status] || props.status;
                
                let badgeClass = 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400';
                if (isFromReschedule) badgeClass = 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400';
                if (props.status === 'completed') badgeClass = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400';
                
                statusEl.innerHTML = `<span class="px-2 py-0.5 rounded-full text-[11px] font-bold uppercase ${badgeClass}">${statusText}</span>`;

                // Show/Hide Reschedule Button
                const btnComplete = document.getElementById('btnCompleteActivity');
                const btnRescheduleToggle = document.getElementById('btnRescheduleToggle');
                const reschedContainer = document.getElementById('rescheduleFormContainer');

                // Reset views
                reschedContainer.classList.add('hidden');
                btnRescheduleToggle.classList.add('hidden');

                if (props.status === 'scheduled') {
                    btnComplete.classList.remove('hidden');
                    btnRescheduleToggle.classList.remove('hidden');
                    descInput.readOnly = false;
                    descInput.classList.remove('bg-slate-50', 'dark:bg-slate-900/50');
                    descInput.classList.add('bg-white', 'dark:bg-slate-900');
                } else {
                    btnComplete.classList.add('hidden');
                    descInput.readOnly = true;
                    descInput.classList.add('bg-slate-50', 'dark:bg-slate-900/50');
                    descInput.classList.remove('bg-white', 'dark:bg-slate-900');
                }

                // Icon and color
                const iconContainer = document.getElementById('modalIconContainer');
                const icon = document.getElementById('modalIcon');
                if (props.type === 'training') {
                    icon.className = 'fa-solid fa-graduation-cap text-blue-600 dark:text-blue-400';
                    iconContainer.className = 'mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/50 sm:mx-0 sm:h-10 sm:w-10';
                } else {
                    icon.className = 'fa-solid fa-headset text-orange-600 dark:text-orange-400';
                    iconContainer.className = 'mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-orange-100 dark:bg-orange-900/50 sm:mx-0 sm:h-10 sm:w-10';
                }

                document.getElementById('eventModal').classList.remove('hidden');
            }

            window.completeActivityFromCalendar = function() {
                if (!selectedActivityId) return;

                const notes = document.getElementById('modalEventDescription').value;

                if (!confirm('¿Estás seguro de marcar esta actividad como completada?')) return;

                const btn = document.getElementById('btnCompleteActivity');
                const originalContent = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin mr-2"></i> Procesando...';

                const url = "{{ route('contacts.agenda.complete', ['activityId' => ':id']) }}".replace(':id', selectedActivityId);

                fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        notes: notes
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        calendar.refetchEvents();
                        window.closeEventModal();
                    } else {
                        alert('Error al completar la actividad.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de red al intentar completar la actividad.');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                });
            };

            window.toggleRescheduleForm = function() {
                const container = document.getElementById('rescheduleFormContainer');
                const btnComplete = document.getElementById('btnCompleteActivity');
                const btnRescheduleToggle = document.getElementById('btnRescheduleToggle');

                container.classList.toggle('hidden');
                
                if (!container.classList.contains('hidden')) {
                    // Hide other actions
                    btnComplete.classList.add('hidden');
                    btnRescheduleToggle.classList.add('hidden');

                    // Set default date to today/now
                    const now = new Date();
                    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                    document.getElementById('rescheduleDate').value = now.toISOString().slice(0, 16);
                    document.getElementById('rescheduleDescription').value = 'REAGENDADO';
                    
                    // Scroll to form
                    container.scrollIntoView({ behavior: 'smooth' });
                } else {
                    // Restore actions
                    btnComplete.classList.remove('hidden');
                    btnRescheduleToggle.classList.remove('hidden');
                }
            };

            window.confirmReschedule = function() {
                if (!selectedActivityId) return;
                
                const event = calendar.getEventById(selectedActivityId);
                const props = event.extendedProps;
                const contactId = props.google_contact_id;
                
                const newDate = document.getElementById('rescheduleDate').value;
                const newNote = document.getElementById('rescheduleDescription').value;

                if (!newDate) {
                    alert('Por favor selecciona una fecha para el reagendamiento.');
                    return;
                }

                if (!confirm('¿Confirmas que deseas reagendar esta actividad para el ' + new Date(newDate).toLocaleString() + '?')) return;

                const btn = document.getElementById('btnConfirmReschedule');
                const originalText = btn.innerText;
                btn.disabled = true;
                btn.innerText = 'Procesando...';

                const url = "{{ route('contacts.agenda.store', ['id' => ':id']) }}".replace(':id', contactId);

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        type: props.type,
                        title: event.title, // Keep same title (it includes contact name)
                        scheduled_date: newDate,
                        description: newNote,
                        reschedule_activity_id: selectedActivityId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        calendar.refetchEvents();
                        window.closeEventModal();
                    } else {
                        alert('Error al reagendar la actividad.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de red al intentar reagendar.');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerText = originalText;
                });
            };

            window.closeEventModal = function() {
                document.getElementById('eventModal').classList.add('hidden');
            };
        });
    </script>
@endsection
