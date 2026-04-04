@extends('layouts.app')

@section('title', 'CRM360 - Google Contacts Sync')

@section('styles')
    <style>
        /* Master List Items */
        .contact-item { transition: all 0.2s ease; border-left: 3px solid transparent; }
        .contact-item:hover { background-color: #f1f5f9; cursor: pointer; }
        .contact-item.selected { 
            background-color: #eff6ff; 
            border-left-color: #3b82f6; 
            box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.03);
        }

        .dark .contact-item:hover { background-color: #1e293b; }
        .dark .contact-item.selected { 
            background-color: #1e293b; 
            border-left-color: #3b82f6; 
            box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.2);
        }

        /* Detail Pane Transition */
        .detail-pane { transition: opacity 0.3s ease; }
        .hidden-pane { opacity: 0; pointer-events: none; position: absolute; }

        /* Select2 Dark Mode & Tailwind Fixes */
        .dark .select2-container--default .select2-selection--single { background-color: #334155; border-color: #475569; height: 38px; display: flex; align-items: center; border-radius: 0.5rem; }
        .dark .select2-container--default .select2-selection--single .select2-selection__rendered { color: #f1f5f9; line-height: normal; }
        .dark .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; top: 1px; }
        .dark .select2-dropdown { background-color: #1e293b; border-color: #475569; color: #f1f5f9; z-index: 10000; }
        .dark .select2-container--default .select2-results__option--selected { background-color: #3b82f6; color: white; }
        .dark .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable { background-color: #2563eb; color: white; }
        .dark .select2-search--dropdown .select2-search__field { background-color: #0f172a; border-color: #334155; color: #f1f5f9; outline: none; }
        
        .select2-container--default .select2-selection--single { height: 38px; display: flex; align-items: center; border-radius: 0.5rem; border-color: #cbd5e1; outline: none; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; top: 1px; }
        .select2-container { width: 100% !important; }
    </style>
@endsection

@section('content')
    <!-- Main Layout: Master-Detail -->
    <div class="flex-1 flex overflow-hidden w-full bg-white dark:bg-slate-900 transition-colors">
        
        <!-- Left Pane: Master List -->
        <div class="w-full md:w-1/3 lg:w-96 flex flex-col border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 z-10 relative transition-colors">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-900 z-20 shadow-sm transition-colors">
                <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-200">Contacts</h2>
                <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-bold px-2.5 py-1 rounded-full">{{ $contacts->count() }}</span>
            </div>
            
            <div class="p-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 transition-colors">
                <div class="relative flex items-center">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-slate-400 dark:text-slate-500"></i>
                    </div>
                    <input type="text" id="searchInput" class="block w-full pl-10 pr-10 py-2 border border-slate-200 dark:border-slate-800 rounded-lg leading-5 bg-white dark:bg-slate-800 placeholder-slate-400 dark:placeholder-slate-500 text-slate-900 dark:text-slate-100 focus:outline-none focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all shadow-sm" placeholder="Search contacts...">
                    <button type="button" id="clearSearch" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hidden transition-colors" title="Clear search">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>
            </div>

            <!-- Contacts List -->
            <ul id="contactsList" class="flex-1 overflow-y-auto" role="listbox" tabindex="0">
                @forelse($contacts as $index => $contact)
                    <li class="contact-item p-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 outline-none focus:bg-blue-50 dark:focus:bg-slate-800/50 {{ !$contact->is_active ? 'opacity-60 grayscale-[50%]' : '' }}" 
                        data-id="{{ $contact->id }}" 
                        data-index="{{ $index }}"
                        onclick="window.selectContact({{ $index }})">
                        
                        <div class="flex-shrink-0 relative">
                            @if($contact->photo_url)
                                <img class="h-11 w-11 rounded-full object-cover border-2 border-slate-200 dark:border-slate-700 shadow-sm" 
                                     src="{{ $contact->photo_url }}" 
                                     alt="" 
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="h-11 w-11 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 hidden items-center justify-center text-white font-bold shadow-sm text-sm uppercase">
                                    {{ substr($contact->name, 0, 1) }}
                                </div>
                            @else
                                <div class="h-11 w-11 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold shadow-sm text-sm uppercase">
                                    {{ substr($contact->name, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 truncate">{{ $contact->name ?: 'No Name' }}</p>
                            @php
                                $badgeClass = 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400';
                                $badgeText = 'Sin Capacitar';
                                $badgeIcon = 'fa-triangle-exclamation';
                                $hasSupport = false;
                                
                                if ($contact->trainings) {
                                    foreach($contact->trainings as $t) {
                                        if ($t->type === 'training') {
                                            if ($t->status === 'completed') {
                                                $badgeClass = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400';
                                                $badgeText = 'Capacitado';
                                                $badgeIcon = 'fa-check-circle';
                                            }
                                            if ($t->status === 'scheduled' && $badgeText !== 'Capacitado') {
                                                $badgeClass = 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400';
                                                $badgeText = 'Agendado';
                                                $badgeIcon = 'fa-clock';
                                            }
                                        }
                                        if ($t->type === 'support' && $t->status === 'scheduled') {
                                            $hasSupport = true;
                                        }
                                    }
                                }
                            @endphp
                            <div class="flex items-center flex-wrap gap-x-2 gap-y-1 mt-0.5">
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate flex items-center">
                                    <i class="fa-solid fa-phone mr-1.5 text-slate-400 dark:text-slate-500 text-[10px]"></i> 
                                    {{ $contact->phone_number ?: 'No phone' }}
                                </p>
                                <div class="flex items-center gap-1.5">
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider {{ $badgeClass }} border border-transparent shadow-sm">
                                        <i class="fa-solid {{ $badgeIcon }} mr-0.5"></i> {{ $badgeText }}
                                    </span>
                                    @if($hasSupport)
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400 border border-transparent shadow-sm" title="Soporte Pendiente">
                                            <i class="fa-solid fa-headset mr-0.5"></i> Soporte
                                        </span>
                                    @endif
                                    @if(!$contact->is_active)
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300 border border-transparent shadow-sm" title="Contacto eliminado en Google">
                                            <i class="fa-solid fa-box-archive mr-0.5"></i> Inactivo
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </li>
                @empty
                    <div class="flex flex-col items-center justify-center h-full text-center p-6">
                        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-4 text-slate-400 dark:text-slate-500">
                            <i class="fa-regular fa-address-book text-2xl"></i>
                        </div>
                        <p class="text-slate-500 dark:text-slate-400 font-medium">No contacts found.</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Connect your Google account to import.</p>
                    </div>
                @endforelse
            </ul>
        </div>

        <!-- Right Pane: Detail View -->
        <div class="hidden md:flex flex-col flex-1 bg-slate-50 dark:bg-slate-900 relative overflow-hidden transition-colors" id="detailContainer">
            <!-- Empty State -->
            <div id="emptyState" class="absolute inset-0 flex flex-col items-center justify-center text-center p-8 bg-slate-50 dark:bg-slate-900 z-10 opacity-100 transition-all duration-300">
                <div class="relative mb-8">
                    <div class="absolute inset-0 bg-blue-500/20 blur-3xl rounded-full scale-150 animate-pulse"></div>
                    <div class="relative w-32 h-32 bg-gradient-to-tr from-blue-600 to-indigo-600 rounded-3xl rotate-12 flex items-center justify-center shadow-2xl">
                        <i class="fa-regular fa-address-book text-6xl text-white -rotate-12"></i>
                    </div>
                </div>
                <h3 class="text-2xl font-bold text-slate-800 dark:text-slate-100 tracking-tight">Selecciona un contacto</h3>
                <p class="text-slate-500 dark:text-slate-400 mt-2 max-w-sm">Explora tu agenda y gestiona la información de tus empresas de forma rápida.</p>
            </div>

            <!-- Content State -->
            <div id="contentState" class="absolute inset-0 flex flex-col hidden-pane z-20 bg-white dark:bg-slate-900 overflow-y-auto transition-colors">
                <div id="detailContent" class="h-full">
                    <!-- Dynamic Content will be injected here via JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- Agenda Modal -->
    <div id="agendaModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/75 dark:bg-slate-900/90 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="window.closeAgendaModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-slate-200 dark:border-slate-700">
                <form id="agendaForm" onsubmit="window.submitAgendaForm(event)">
                    <input type="hidden" id="agendaContactId" name="contact_id">
                    <input type="hidden" id="rescheduleActivityId" name="reschedule_activity_id">
                    <div class="bg-white dark:bg-slate-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/50 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fa-solid fa-calendar-plus text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-bold text-slate-900 dark:text-white" id="modal-title">
                                    Agendar Actividad
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tipo de Actividad</label>
                                        <div class="grid grid-cols-2 gap-3">
                                            <label class="relative flex cursor-pointer rounded-lg border bg-white dark:bg-slate-900 p-3 shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-500 border-slate-200 dark:border-slate-700">
                                                <input type="radio" name="type" value="training" class="peer sr-only" checked>
                                                <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400 peer-checked:text-blue-600 dark:peer-checked:text-blue-400 font-medium">
                                                    <i class="fa-solid fa-graduation-cap"></i> Capacitación
                                                </div>
                                                <div class="absolute -inset-px rounded-lg border-2 border-transparent peer-checked:border-blue-500 pointer-events-none"></div>
                                            </label>
                                            <label class="relative flex cursor-pointer rounded-lg border bg-white dark:bg-slate-900 p-3 shadow-sm focus:outline-none focus:ring-1 focus:ring-orange-500 border-slate-200 dark:border-slate-700">
                                                <input type="radio" name="type" value="support" class="peer sr-only">
                                                <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400 peer-checked:text-orange-600 dark:peer-checked:text-orange-400 font-medium">
                                                    <i class="fa-solid fa-headset"></i> Soporte
                                                </div>
                                                <div class="absolute -inset-px rounded-lg border-2 border-transparent peer-checked:border-orange-500 pointer-events-none"></div>
                                            </label>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="agendaTitle" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Responsable</label>
                                        <select id="agendaTitle" name="title" required class="w-full outline-none select2-instructor">
                                            <option value="" disabled selected>Selecciona un responsable...</option>
                                            <option value="FER">FER</option>
                                            <option value="RONNY">RONNY</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="agendaDate" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Fecha y Hora</label>
                                        <input type="datetime-local" id="agendaDate" name="scheduled_date" required class="w-full rounded-lg border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none color-scheme-dark">
                                    </div>
                                    <div>
                                        <label for="agendaDescription" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Descripción / Notas</label>
                                        <textarea id="agendaDescription" name="description" rows="3" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-none" placeholder="Detalles de la capacitación o el problema reportado..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800/80 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-slate-200 dark:border-slate-700">
                        <button type="submit" id="agendaSubmitBtn" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                            Agendar
                        </button>
                        <button type="button" onclick="window.closeAgendaModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-slate-300 dark:border-slate-600 shadow-sm px-4 py-2 bg-white dark:bg-slate-700 text-base font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        window.contactsData = @json($contacts);
        
        $(document).ready(function() {
            if (!$('.select2-instructor').data('select2')) {
                $('.select2-instructor').select2({
                    placeholder: "Selecciona un responsable...",
                    dropdownParent: $('#agendaModal')
                });
            }
        });
    </script>
    <script src="{{ asset('js/contacts.js') }}?v={{ time() }}"></script>
@endsection
