/**
 * RUGAL - Lógica de Agendamiento de Citas
 */

let currentStep = 1;
let selectedVet = null;
let selectedDate = null;
let selectedTime = null;
let currentMonth = new Date();

const bookingData = {
    veterinaria_id: null,
    veterinaria_nombre: '',
    precio_total: 0,
    anticipo_porcentaje: 0,
    fecha: '',
    hora: '',
    mascota_id: null,
    tipo_cita: 'consulta',
    motivo: ''
};

// Seleccionar veterinaria
function selectVet(id, nombre, precio, anticipo) {
    // Deseleccionar todas
    document.querySelectorAll('.vet-card').forEach(card => {
        card.classList.remove('selected');
    });

    // Seleccionar esta
    event.currentTarget.classList.add('selected');

    bookingData.veterinaria_id = id;
    bookingData.veterinaria_nombre = nombre;
    bookingData.precio_total = precio;
    bookingData.anticipo_porcentaje = anticipo;

    selectedVet = id;
    document.getElementById('btnNext').disabled = false;
}

// Navegación entre pasos
function nextStep() {
    if (currentStep === 1 && !selectedVet) {
        alert('Por favor selecciona una veterinaria');
        return;
    }

    if (currentStep === 2 && (!selectedDate || !selectedTime)) {
        alert('Por favor selecciona fecha y hora');
        return;
    }

    if (currentStep === 3) {
        // Guardar detalles
        bookingData.mascota_id = document.getElementById('mascotaSelect').value;
        bookingData.tipo_cita = document.getElementById('tipoConsulta').value;
        bookingData.motivo = document.getElementById('motivoConsulta').value;

        // Mostrar resumen
        updateSummary();
    }

    if (currentStep === 4) {
        // Confirmar y crear cita
        confirmarCita();
        return;
    }

    currentStep++;
    updateStepUI();

    if (currentStep === 2) {
        renderCalendar();
    }
}

function prevStep() {
    currentStep--;
    updateStepUI();
}

function updateStepUI() {
    // Ocultar todos los pasos
    document.querySelectorAll('.booking-step').forEach(step => {
        step.classList.remove('active');
    });

    // Mostrar paso actual
    document.getElementById('step' + currentStep).classList.add('active');

    // Actualizar indicadores
    document.querySelectorAll('.step').forEach((step, index) => {
        const stepNum = index + 1;
        if (stepNum < currentStep) {
            step.classList.add('completed');
            step.classList.remove('active');
        } else if (stepNum === currentStep) {
            step.classList.add('active');
            step.classList.remove('completed');
        } else {
            step.classList.remove('active', 'completed');
        }
    });

    // Botones de navegación
    document.getElementById('btnPrev').style.display = currentStep > 1 ? 'block' : 'none';

    const btnNext = document.getElementById('btnNext');
    if (currentStep === 4) {
        btnNext.innerHTML = '<i class="fas fa-check"></i> Confirmar Cita';
    } else {
        btnNext.innerHTML = 'Siguiente <i class="fas fa-chevron-right"></i>';
    }

    // Deshabilitar siguiente si falta info
    if (currentStep === 2) {
        btnNext.disabled = !selectedDate || !selectedTime;
    } else {
        btnNext.disabled = false;
    }
}

// Calendario
function renderCalendar() {
    const year = currentMonth.getFullYear();
    const month = currentMonth.getMonth();

    // Actualizar título
    const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    document.getElementById('currentMonth').textContent = `${monthNames[month]} ${year}`;

    // Limpiar calendario
    const grid = document.getElementById('calendarGrid');
    grid.innerHTML = '';

    // Días de la semana
    const dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    dayNames.forEach(day => {
        const dayHeader = document.createElement('div');
        dayHeader.style.textAlign = 'center';
        dayHeader.style.fontWeight = '600';
        dayHeader.style.color = '#64748b';
        dayHeader.style.padding = '10px 0';
        dayHeader.textContent = day;
        grid.appendChild(dayHeader);
    });

    // Primer día del mes
    const firstDay = new Date(year, month, 1).getDay();

    // Días del mes anterior (vacíos)
    for (let i = 0; i < firstDay; i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'calendar-day disabled';
        grid.appendChild(emptyDay);
    }

    // Días del mes
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = new Date();

    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(year, month, day);
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        dayElement.textContent = day;

        // Deshabilitar días pasados
        if (date < today.setHours(0, 0, 0, 0)) {
            dayElement.classList.add('disabled');
        } else {
            dayElement.onclick = () => selectDate(year, month, day);
        }

        grid.appendChild(dayElement);
    }
}

function changeMonth(delta) {
    currentMonth.setMonth(currentMonth.getMonth() + delta);
    renderCalendar();
}

function selectDate(year, month, day) {
    // Deseleccionar todos
    document.querySelectorAll('.calendar-day').forEach(d => {
        d.classList.remove('selected');
    });

    // Seleccionar este
    event.currentTarget.classList.add('selected');

    selectedDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    bookingData.fecha = selectedDate;

    // Cargar horarios disponibles
    loadTimeSlots(selectedDate);
}

function loadTimeSlots(fecha) {
    fetch(`ajax-disponibilidad.php?veterinaria_id=${selectedVet}&fecha=${fecha}`)
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('timeSlotsContainer');
            const slotsDiv = document.getElementById('timeSlots');

            if (data.slots && data.slots.length > 0) {
                container.style.display = 'block';
                slotsDiv.innerHTML = '';

                data.slots.forEach(slot => {
                    const slotElement = document.createElement('div');
                    slotElement.className = 'time-slot';
                    slotElement.textContent = slot.hora_formateada;

                    if (!slot.disponible) {
                        slotElement.classList.add('occupied');
                        slotElement.innerHTML += '<br><small>Ocupado</small>';
                    } else {
                        slotElement.onclick = () => selectTime(slot.hora, slot.hora_formateada);
                    }

                    slotsDiv.appendChild(slotElement);
                });
            } else {
                container.style.display = 'block';
                slotsDiv.innerHTML = '<p style="text-align: center; color: #64748b;">No hay horarios disponibles para este día</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar horarios');
        });
}

function selectTime(hora, horaFormateada) {
    // Deseleccionar todos
    document.querySelectorAll('.time-slot').forEach(slot => {
        slot.classList.remove('selected');
    });

    // Seleccionar este
    event.currentTarget.classList.add('selected');

    selectedTime = hora;
    bookingData.hora = hora;

    document.getElementById('btnNext').disabled = false;
}

function updateSummary() {
    document.getElementById('summaryVet').textContent = bookingData.veterinaria_nombre;
    document.getElementById('summaryFecha').textContent = new Date(bookingData.fecha).toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    document.getElementById('summaryHora').textContent = bookingData.hora;

    const mascotaSelect = document.getElementById('mascotaSelect');
    document.getElementById('summaryMascota').textContent = mascotaSelect.options[mascotaSelect.selectedIndex].text;

    const tipoSelect = document.getElementById('tipoConsulta');
    document.getElementById('summaryTipo').textContent = tipoSelect.options[tipoSelect.selectedIndex].text;

    document.getElementById('summaryPrecio').textContent = '$' + bookingData.precio_total.toLocaleString();

    const anticipo = bookingData.precio_total * (bookingData.anticipo_porcentaje / 100);
    document.getElementById('summaryAnticipo').textContent = '$' + anticipo.toLocaleString();
}

function confirmarCita() {
    const fechaHora = `${bookingData.fecha} ${bookingData.hora}`;

    const datos = {
        veterinaria_id: bookingData.veterinaria_id,
        mascota_id: bookingData.mascota_id,
        fecha_hora: fechaHora,
        tipo_cita: bookingData.tipo_cita,
        motivo: bookingData.motivo,
        precio_total: bookingData.precio_total,
        porcentaje_anticipo: bookingData.anticipo_porcentaje
    };

    fetch('ajax-crear-cita.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos)
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('✅ Cita agendada exitosamente!\n\nAhora debes subir el comprobante de pago del anticipo.');
                window.location.href = 'pagar-cita.php?id=' + data.cita_id;
            } else {
                alert('❌ Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al crear la cita');
        });
}
