document.addEventListener('DOMContentLoaded', () => {
    let currentIndex = -1;
    const contacts = window.contactsData || [];
    const searchInput = document.getElementById('searchInput');
    const contactsList = document.getElementById('contactsList');
    const items = contactsList.getElementsByClassName('contact-item');
    const emptyState = document.getElementById('emptyState');
    const contentState = document.getElementById('contentState');
    const detailContent = document.getElementById('detailContent');
    const clearSearch = document.getElementById('clearSearch');

    // Make list focusable for keyboard events to work without explicit click
    contactsList.setAttribute('tabindex', '0');
    contactsList.focus();

    // 1. Selection Logic
    window.selectContact = (index) => {
        if (index < 0 || index >= items.length) return;

        // Remove previous selection
        Array.from(items).forEach(el => el.classList.remove('selected'));

        // Add new selection
        items[index].classList.add('selected');
        currentIndex = index;

        // Ensure visible in scroll area
        items[index].scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        // Update UI right pane
        renderContactDetails(contacts[index]);
    };

    // 2. Keyboard Navigation (Up / Down arrows)
    contactsList.addEventListener('keydown', (e) => {
        if (items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            const nextIndex = currentIndex + 1 < items.length ? currentIndex + 1 : currentIndex;
            selectContact(nextIndex);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            const prevIndex = currentIndex - 1 >= 0 ? currentIndex - 1 : 0;
            selectContact(prevIndex);
        } else if (e.key === 'Enter' && currentIndex >= 0) {
            e.preventDefault();
            // Could focus on the edit form if needed
        }
    });

    // 3. Search Filtering (Basic client-side)
    searchInput.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        
        if (term.length > 0) {
            clearSearch.classList.remove('hidden');
        } else {
            clearSearch.classList.add('hidden');
        }

        Array.from(items).forEach((item, index) => {
            const name = contacts[index].name?.toLowerCase() || '';
            const phone = contacts[index].phone_number?.toLowerCase() || '';

            if (name.includes(term) || phone.includes(term)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });

        // If searching, we reset selection logic
        currentIndex = -1;
        Array.from(items).forEach(el => el.classList.remove('selected'));
        showEmptyState();
    });

    // Handle Clear Search
    if (clearSearch) {
        clearSearch.addEventListener('click', () => {
            searchInput.value = '';
            clearSearch.classList.add('hidden');
            searchInput.focus();
            // Trigger input event to reset filter
            searchInput.dispatchEvent(new Event('input'));
        });
    }

    // 4. Render Details Pane
    function renderContactDetails(contact) {
        if (!contact) return;

        hideEmptyState();

        const initial = contact.name ? contact.name.charAt(0).toUpperCase() : '?';
        // Badges Calculation Logic
        let isTrained = false;
        let isTrainingScheduled = false;
        let trainingScheduledDate = null;
        let hasPendingSupport = false;

        let trainingsHtml = '';
        if (contact.trainings && contact.trainings.length > 0) {
            contact.trainings.forEach(t => {
                // Check Training Status
                if (t.type === 'training') {
                    if (t.status === 'completed') isTrained = true;
                    if (t.status === 'scheduled') {
                        isTrainingScheduled = true;
                        trainingScheduledDate = new Date(t.scheduled_date).toLocaleDateString();
                    }
                }
                // Check Support Status
                if (t.type === 'support' && t.status === 'scheduled') {
                    hasPendingSupport = true;
                }
            });

            trainingsHtml = contact.trainings.map(t => `
                <div class="px-4 py-3 bg-white dark:bg-slate-800/80 border-l-4 ${t.type === 'support' ? 'border-orange-500' : 'border-blue-500'} border border-y-slate-100 border-r-slate-100 dark:border-y-slate-700/50 dark:border-r-slate-700/50 rounded-r-xl shadow-sm mb-3 flex justify-between items-center transition-all hover:shadow-md cursor-pointer" onclick="window.showActivityDetail(${t.id}, ${contact.id})">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider ${t.type === 'support' ? 'text-orange-600 dark:text-orange-400' : 'text-blue-600 dark:text-blue-400'}">
                                ${t.type === 'support' ? '<i class="fa-solid fa-headset mr-1"></i> Soporte' : '<i class="fa-solid fa-graduation-cap mr-1"></i> Capacitación'}
                            </span>
                        </div>
                        <p class="font-bold text-sm text-slate-800 dark:text-slate-200">${t.title}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"><i class="fa-regular fa-calendar mr-1"></i> ${new Date(t.scheduled_date).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' })}</p>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        ${t.status === 'rescheduled' ? `
                            <span class="px-2.5 py-1 text-[10px] uppercase font-bold rounded-md w-full text-center bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-400">Reagendado</span>
                        ` : `
                            <span class="px-2.5 py-1 text-[10px] uppercase font-bold rounded-md w-full text-center ${
                                t.status === 'completed' ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400' :
                                t.status === 'cancelled' ? 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400' :
                                'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400'
                            }">${t.status === 'scheduled' ? 'Pendiente' : t.status === 'completed' ? 'Finalizado' : 'Cancelado'}</span>
                        `}
                        ${t.status === 'scheduled' ? `<button onclick="window.completeAgendaActivity(event, ${t.id}, ${contact.id})" class="mt-1 text-[10px] uppercase font-bold text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors flex items-center bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:hover:bg-blue-900/60 px-2 py-1 rounded w-full justify-center"><i class="fa-solid fa-check mr-1"></i> Finalizar</button>
                        ${t.type === 'training' ? `<button onclick="window.openRescheduleModal(event, ${t.id}, ${contact.id})" class="text-[10px] uppercase font-bold text-slate-600 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-300 transition-colors flex items-center bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 px-2 py-1 rounded w-full justify-center"><i class="fa-solid fa-calendar-plus mr-1"></i> Reagendar</button>` : ''}` : ''}
                    </div>
                </div>
            `).join('');
        } else {
            trainingsHtml = `
            <div class="text-center py-8 bg-white dark:bg-slate-800/20 border border-slate-200 border-dashed dark:border-slate-800 rounded-2xl">
                <div class="w-12 h-12 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-timeline text-slate-400"></i>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Línea de tiempo vacía</p>
                <p class="text-xs text-slate-400 mt-1">No hay tickets ni capacitaciones registradas.</p>
            </div>`;
        } // ... 

        // Badges Generation
        let badgesHtml = '';
        if (isTrained) {
            badgesHtml += `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50 shadow-sm"><i class="fa-solid fa-check-circle mr-1.5 opacity-70"></i> Capacitado</span>`;
        } else if (isTrainingScheduled) {
            badgesHtml += `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50 shadow-sm animate-pulse"><i class="fa-solid fa-clock mr-1.5 opacity-70"></i> Sin Capacitar | Agendado ${trainingScheduledDate}</span>`;
        } else {
            badgesHtml += `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400 border border-red-200 dark:border-red-800/50 shadow-sm"><i class="fa-solid fa-triangle-exclamation mr-1.5 opacity-70"></i> Sin Capacitar</span>`;
        }

        if (hasPendingSupport) {
            badgesHtml += `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-400 border border-orange-200 dark:border-orange-800/50 shadow-sm ml-2 animate-bounce"><i class="fa-solid fa-headset mr-1.5 opacity-70"></i> Soporte Solicitado</span>`;
        }

        const photoHtml = contact.photo_url
            ? `<div class="relative group">
                <img src="${contact.photo_url}" class="w-20 h-20 rounded-2xl border-2 border-slate-200 dark:border-slate-700 shadow-lg object-cover transition-all duration-300 group-hover:scale-105" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 border-2 border-slate-200 dark:border-slate-700 shadow-lg hidden items-center justify-center text-2xl text-white font-bold">${initial}</div>
               </div>`
            : `<div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 border-2 border-slate-200 dark:border-slate-700 shadow-lg flex items-center justify-center text-2xl text-white font-bold">${initial}</div>`;

        const html = `
            <div class="min-h-full flex flex-col">
                <!-- Compact Header Section -->
                <div class="p-8 pb-8 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 transition-colors">
                    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6">
                        <div class="flex items-center gap-5">
                            ${photoHtml}
                            <div class="space-y-2">
                                <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight leading-tight">${contact.name || 'Empresa Desconocida'}</h1>
                                <div class="flex items-center flex-wrap mt-1 mb-2 gap-2">
                                    ${badgesHtml}
                                </div>
                                <div class="flex flex-wrap items-center gap-4 text-slate-500 dark:text-slate-400 mt-2">
                                    <p class="text-base flex items-center font-medium">
                                        <i class="fa-solid fa-phone mr-2 text-blue-500/70"></i> 
                                        ${contact.phone_number || 'Sin número'}
                                    </p>
                                    <span class="hidden md:inline w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                                    <p class="text-xs font-mono bg-slate-100 dark:bg-slate-800/80 px-2 py-0.5 rounded text-slate-500 tracking-tighter">
                                        ID: ${contact.google_id.split('/').pop()}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap items-center gap-3">
                            <button class="flex-1 sm:flex-none bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-lg shadow-blue-500/20 flex items-center justify-center">
                                <i class="fa-solid fa-message mr-2 text-blue-200"></i> Mensaje
                            </button>
                            <button class="flex-1 sm:flex-none bg-slate-800 dark:bg-slate-800/50 hover:bg-slate-900 dark:hover:bg-slate-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-lg border border-transparent dark:border-slate-700 flex items-center justify-center">
                                <i class="fa-solid fa-briefcase mr-2 text-slate-400"></i> Datos
                            </button>
                            <button class="bg-slate-50 dark:bg-slate-800/20 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 p-2.5 rounded-xl text-sm font-bold transition-all border border-slate-200 dark:border-slate-800" title="Editar en Google">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="flex-1 p-8 grid grid-cols-1 lg:grid-cols-3 gap-8 bg-slate-50 dark:bg-slate-900 transition-colors">
                    <!-- Training Section -->
                    <div class="space-y-4 flex flex-col h-full">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-800">
                            <h3 class="text-lg font-extrabold text-slate-800 dark:text-slate-200 flex items-center">
                                <i class="fa-solid fa-list-check mr-2 text-blue-500"></i> Agenda y Tickets
                            </h3>
                            <button onclick="window.openAgendaModal(${contact.id})" class="text-xs font-bold bg-blue-100 hover:bg-blue-200 text-blue-700 dark:bg-blue-900/50 dark:hover:bg-blue-800/50 dark:text-blue-300 px-3 py-1.5 rounded-lg transition-colors shadow-sm flex items-center">
                                <i class="fa-solid fa-plus mr-1.5"></i> Nuevo
                            </button>
                        </div>
                        <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar space-y-3 max-h-[400px]">
                            ${trainingsHtml}
                        </div>
                    </div>

                    <!-- Ticket Detail Section -->
                    <div class="flex flex-col h-full" id="ticketDetailSection">
                        <div class="text-center py-8 bg-white dark:bg-slate-800/20 border border-slate-200 border-dashed dark:border-slate-800 rounded-2xl h-full flex flex-col items-center justify-center">
                            <div class="w-12 h-12 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="fa-regular fa-hand-pointer text-slate-400"></i>
                            </div>
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Selecciona un ticket</p>
                            <p class="text-xs text-slate-400 mt-1">Haz clic en un ticket de la lista para ver sus detalles aquí.</p>
                        </div>
                    </div>

                    <!-- Info Section / Notes -->
                    <div class="flex flex-col">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-slate-200 mb-3">Información de Empresa</h3>
                        <div class="relative flex-1 bg-white dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm focus-within:border-blue-500/50 focus-within:ring-1 focus-within:ring-blue-500/50 transition-all flex flex-col">
                            <textarea id="contactNotesInput" class="flex-1 w-full bg-transparent border-none focus:ring-0 text-sm text-slate-600 dark:text-slate-300 placeholder-slate-400 dark:placeholder-slate-500 resize-none" placeholder="Ingresa detalles o notas sobre la empresa...">${contact.notes || ''}</textarea>
                            <span id="saveStatusIndicator" class="absolute bottom-4 right-4 text-xs font-medium text-slate-400 opacity-0 transition-opacity">Guardado</span>
                        </div>
                    </div>
                </div>

                <!-- Footer: System Logs -->
                <div class="px-8 py-3 bg-slate-100/50 dark:bg-slate-800/30 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                    <div class="flex items-center gap-4">
                        <span><span class="font-bold text-slate-500 dark:text-slate-400">GOOGLE ID:</span> ${contact.google_id}</span>
                    </div>
                    <div>
                        <span><span class="font-bold text-slate-500 dark:text-slate-400">LAST SYNCED:</span> ${new Date(contact.synced_at).toLocaleString()}</span>
                    </div>
                </div>
            </div>
        `;

        detailContent.innerHTML = html;

        // Auto-save logic for Two-Way Sync
        const notesInput = document.getElementById('contactNotesInput');
        const saveStatus = document.getElementById('saveStatusIndicator');

        if (notesInput) {
            notesInput.addEventListener('input', function() {
                saveStatus.textContent = 'Guardando...';
                saveStatus.classList.remove('opacity-0', 'text-emerald-500', 'text-red-500');
                saveStatus.classList.add('text-blue-500');

                clearTimeout(window.notesDebounceTimer);
                window.notesDebounceTimer = setTimeout(() => {
                    const newNotes = this.value;
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    // Get base URL from current window path to support /crm360/public/ setups
                    const baseUrl = window.location.pathname.replace(/\/contacts\/?$/, '');
                    
                    fetch(`${baseUrl}/contacts/${contact.id}/notes`, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ notes: newNotes })
                    })
                    .then(async response => {
                        const contentType = response.headers.get("content-type");
                        if (contentType && contentType.indexOf("application/json") !== -1) {
                            const data = await response.json();
                            if(!response.ok) throw new Error(data.message || data.error || 'Error del servidor');
                            return data;
                        } else {
                            const text = await response.text();
                            console.error("Non-JSON Response:", text);
                            // Show a tiny bit of the HTML to help debug if it happens again
                            const snippet = text.replace(/<[^>]*>?/gm, '').substring(0, 60).trim();
                            throw new Error('Servidor retornó HTML: ' + (snippet || 'Sin detalles'));
                        }
                    })
                    .then(data => {
                        if(data.success) {
                            // Update local data array so it persists on UI clicks without reloading
                            const memContact = contacts.find(c => c.id === contact.id);
                            if (memContact) memContact.notes = newNotes;
                            
                            saveStatus.textContent = 'Guardado';
                            saveStatus.classList.remove('text-blue-500', 'text-red-500');
                            saveStatus.classList.add('text-emerald-500');
                            setTimeout(() => { 
                                // Only fade out if it still says "Guardado" (hasn't started typing again)
                                if (saveStatus.textContent === 'Guardado') {
                                    saveStatus.classList.add('opacity-0'); 
                                }
                            }, 2500);
                        } else {
                            saveStatus.textContent = 'Error al guardar';
                            saveStatus.classList.remove('text-blue-500', 'text-emerald-500');
                            saveStatus.classList.add('text-red-500');
                        }
                    })
                    .catch(err => {
                        console.error('Save error:', err);
                        saveStatus.textContent = 'Error: ' + err.message;
                        saveStatus.classList.remove('text-blue-500', 'text-emerald-500');
                        saveStatus.classList.add('text-red-500');
                    });
                }, 1500); // 1.5 second delay
            });
        }
    }

    window.showActivityDetail = (activityId, contactId) => {
        const _contacts = window.contactsData || [];
        const _contact = _contacts.find(c => c.id == contactId);
        if (!_contact || !_contact.trainings) return;
        
        const activity = _contact.trainings.find(t => t.id == activityId);
        if (!activity) return;

        const section = document.getElementById('ticketDetailSection');
        if (!section) return;

        const icon = activity.type === 'support' ? '<i class="fa-solid fa-headset text-orange-500"></i>' : '<i class="fa-solid fa-graduation-cap text-blue-500"></i>';
        const bgHeader = activity.type === 'support' ? 'bg-orange-50 dark:bg-orange-900/20' : 'bg-blue-50 dark:bg-blue-900/20';
        
        section.innerHTML = `
            <h3 class="text-lg font-bold text-slate-800 dark:text-slate-200 mb-3 flex items-center">
                ${icon} <span class="ml-2">Detalles del Ticket</span>
            </h3>
            <div class="relative flex-1 bg-white dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm focus-within:border-blue-500/50 focus-within:ring-1 focus-within:ring-blue-500/50 transition-all flex flex-col h-full">
                <div class="${bgHeader} p-3 rounded-xl mb-4 text-center border-b border-transparent">
                    <p class="font-bold text-slate-800 dark:text-slate-200">${activity.title}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"><i class="fa-regular fa-calendar mr-1"></i> ${new Date(activity.scheduled_date).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' })}</p>
                </div>
                
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Notas / Descripción</label>
                <textarea id="activityNotesInput" class="flex-1 w-full bg-transparent border border-slate-200 dark:border-slate-700 rounded-xl p-3 focus:ring-2 focus:ring-blue-500/50 text-sm text-slate-600 dark:text-slate-300 placeholder-slate-400 dark:placeholder-slate-500 resize-none outline-none" placeholder="Ingresa detalles sobre el ticket o capacitación...">${activity.notes || ''}</textarea>
                <span id="activitySaveStatusIndicator" class="absolute bottom-4 right-4 text-xs font-medium text-slate-400 opacity-0 transition-opacity">Guardado</span>
            </div>
        `;

        const notesInput = document.getElementById('activityNotesInput');
        const saveStatus = document.getElementById('activitySaveStatusIndicator');

        if (notesInput) {
            notesInput.addEventListener('input', function() {
                saveStatus.textContent = 'Guardando...';
                saveStatus.classList.remove('opacity-0', 'text-emerald-500', 'text-red-500');
                saveStatus.classList.add('text-blue-500');

                clearTimeout(window.activityNotesDebounceTimer);
                window.activityNotesDebounceTimer = setTimeout(() => {
                    const newNotes = this.value;
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const baseUrl = window.location.pathname.replace(/\/contacts\/?$/, '');
                    
                    fetch(`${baseUrl}/contacts/activities/${activity.id}/notes`, {
                        method: 'PATCH',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ notes: newNotes })
                    })
                    .then(async response => {
                        const contentType = response.headers.get("content-type");
                        if (contentType && contentType.indexOf("application/json") !== -1) {
                            const data = await response.json();
                            if(!response.ok) throw new Error(data.message || data.error || 'Error del servidor');
                            return data;
                        } else {
                            throw new Error('Servidor retornó un error de red');
                        }
                    })
                    .then(data => {
                        if(data.success) {
                            activity.notes = newNotes; 
                            saveStatus.textContent = 'Guardado';
                            saveStatus.classList.remove('text-blue-500', 'text-red-500');
                            saveStatus.classList.add('text-emerald-500');
                            setTimeout(() => { 
                                if (saveStatus.textContent === 'Guardado') {
                                    saveStatus.classList.add('opacity-0'); 
                                }
                            }, 2500);
                        } else {
                            throw new Error('No se guardó correctamente');
                        }
                    })
                    .catch(err => {
                        console.error('Save error:', err);
                        saveStatus.textContent = 'Error: ' + err.message;
                        saveStatus.classList.remove('text-blue-500', 'text-emerald-500');
                        saveStatus.classList.add('text-red-500');
                    });
                }, 1000); // 1 second delay
            });
        }
    };

    // Toggle States
    function hideEmptyState() {
        emptyState.classList.add('opacity-0');
        setTimeout(() => {
            emptyState.classList.add('hidden-pane');
            contentState.classList.remove('hidden-pane');
            contentState.classList.add('opacity-100');
        }, 150);
    }

    function showEmptyState() {
        contentState.classList.add('hidden-pane');
        contentState.classList.remove('opacity-100');
        emptyState.classList.remove('hidden-pane');
        setTimeout(() => {
            emptyState.classList.remove('opacity-0');
        }, 50);
    }
});

// Modal Logic
window.openAgendaModal = (contactId) => {
    document.getElementById('agendaContactId').value = contactId;
    document.getElementById('rescheduleActivityId').value = '';
    
    // Set default date to today, 1 hour from now
    const now = new Date();
    now.setHours(now.getHours() + 1);
    now.setMinutes(0);
    // Format to YYYY-MM-DDThh:mm for datetime-local
    const tzoffset = (new Date()).getTimezoneOffset() * 60000;
    const localISOTime = (new Date(now - tzoffset)).toISOString().slice(0,16);
    document.getElementById('agendaDate').value = localISOTime;
    
    document.getElementById('agendaModal').classList.remove('hidden');
};

window.openRescheduleModal = (e, activityId, contactId) => {
    e.stopPropagation();
    document.getElementById('agendaContactId').value = contactId;
    document.getElementById('rescheduleActivityId').value = activityId;
    
    // Set default date to today, 1 hour from now
    const now = new Date();
    now.setHours(now.getHours() + 1);
    now.setMinutes(0);
    // Format to YYYY-MM-DDThh:mm for datetime-local
    const tzoffset = (new Date()).getTimezoneOffset() * 60000;
    const localISOTime = (new Date(now - tzoffset)).toISOString().slice(0,16);
    document.getElementById('agendaDate').value = localISOTime;
    
    document.getElementById('agendaModal').classList.remove('hidden');
};

window.closeAgendaModal = () => {
    document.getElementById('agendaModal').classList.add('hidden');
    document.getElementById('agendaForm').reset();
    
    // Re-initialize select2 if used
    if ($.fn.select2) {
        $('.select2-instructor').val(null).trigger('change');
    }
};



window.submitAgendaForm = async (e) => {
    e.preventDefault();
    const form = e.target;
    const submitBtn = document.getElementById('agendaSubmitBtn');
    const contactId = document.getElementById('agendaContactId').value;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    const baseUrl = window.location.pathname.replace(/\/contacts\/?$/, '');

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin mr-2"></i> Guardando...';

    try {
        const response = await fetch(`${baseUrl}/contacts/${contactId}/agenda`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        
        if (!response.ok) throw new Error(result.message || result.error || 'Error del servidor');
        
        if (result.success) {
            // Update local state by pushing the new training
            const contacts = window.contactsData || [];
            const contact = contacts.find(c => c.id == contactId);
            if (contact) {
                if(!contact.trainings) contact.trainings = [];
                // Mark old ticket as rescheduled if applicable
                if (result.rescheduled_id) {
                    const oldTst = contact.trainings.find(t => t.id == result.rescheduled_id);
                    if (oldTst) {
                        oldTst.status = 'rescheduled';
                        oldTst.notes = oldTst.notes ? oldTst.notes + "\n\nREAGENDADO" : "REAGENDADO";
                    }
                }
                
                // API should return the fresh training object
                contact.trainings.unshift(result.training);
            }
            
            // Re-render the UI
            window.selectContact(contacts.findIndex(c => c.id == contactId));
            window.closeAgendaModal();
        }
    } catch (err) {
        console.error('Error saving agenda:', err);
        alert('Error: ' + err.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Agendar';
    }
};

window.completeAgendaActivity = async (e, activityId, contactId) => {
    e.stopPropagation();
    const btn = e.currentTarget;
    const originalText = btn.innerHTML;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const baseUrl = window.location.pathname.replace(/\/contacts\/?$/, '');

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';

    try {
        const response = await fetch(`${baseUrl}/contacts/activities/${activityId}/complete`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        const result = await response.json();
        
        if (!response.ok) throw new Error(result.message || result.error || 'Error del servidor');
        
        if (result.success) {
            // Update local state by mutating the existing array element
            const contacts = window.contactsData || [];
            const contact = contacts.find(c => c.id == contactId);
            if (contact && contact.trainings) {
                const training = contact.trainings.find(t => t.id == activityId);
                if (training) {
                    training.status = 'completed';
                }
            }
            
            // Re-render the right UI smoothly
            const listIndex = contacts.findIndex(c => c.id == contactId);
            if(listIndex !== -1) {
                window.selectContact(listIndex);
                
                // Auto-open detail right pane for notes input
                window.showActivityDetail(activityId, contactId);
                
                // Dynamically update the left sidebar list item badge
                const listItem = document.querySelector(`.contact-item[data-id="${contactId}"]`);
                if (listItem) {
                    // Find the container holding the badges in the list item
                    const badgeContainer = listItem.querySelector('.flex.items-center.gap-1\\.5');
                    if (badgeContainer) {
                        let isTrained = false;
                        let isTrainingSch = false;
                        let hasSupport = false;

                        if (contact.trainings) {
                            contact.trainings.forEach(t => {
                                if (t.type === 'training') {
                                    if (t.status === 'completed') isTrained = true;
                                    if (t.status === 'scheduled') isTrainingSch = true;
                                }
                                if (t.type === 'support' && t.status === 'scheduled') {
                                    hasSupport = true;
                                }
                            });
                        }

                        let bClass = 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400';
                        let bText = 'Sin Capacitar';
                        let bIcon = 'fa-triangle-exclamation';

                        if (isTrained) {
                            bClass = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400';
                            bText = 'Capacitado';
                            bIcon = 'fa-check-circle';
                        } else if (isTrainingSch) {
                            bClass = 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400';
                            bText = 'Agendado';
                            bIcon = 'fa-clock';
                        }
                        
                        let badgesHtml = `<span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider ${bClass} border border-transparent shadow-sm">
                                            <i class="fa-solid ${bIcon} mr-0.5"></i> ${bText}
                                        </span>`;
                        if (hasSupport) {
                            badgesHtml += `<span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400 border border-transparent shadow-sm" title="Soporte Pendiente">
                                                <i class="fa-solid fa-headset mr-0.5"></i> Soporte
                                            </span>`;
                        }
                        badgeContainer.innerHTML = badgesHtml;
                    }
                }
            }
        }
    } catch (err) {
        console.error('Error completing activity:', err);
        alert('Error: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
};

