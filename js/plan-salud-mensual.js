/**
 * RUGAL - Plan de Salud Mensual
 * JavaScript simplificado y funcional
 */

const PlanSaludMensualApp = {
    currentStep: 1,
    totalSteps: 4,
    mascotaId: null,
    mascotaNombre: '',
    
    // Inicializar aplicación
    init() {
        this.bindEvents();
        this.updateProgress();
    },
    
    // Vincular eventos
    bindEvents() {
        // Selección de mascota - solo para mascotas sin plan
        document.querySelectorAll('.psm-pet-card-compact:not(.has-plan)').forEach(card => {
            card.addEventListener('click', (e) => this.selectPet(e));
        });

        // Navegación del formulario
        document.getElementById('nextBtn')?.addEventListener('click', () => this.nextStep());
        document.getElementById('prevBtn')?.addEventListener('click', () => this.prevStep());
        document.getElementById('submitBtn')?.addEventListener('click', (e) => this.submitForm(e));

        // BCS selection
        document.querySelectorAll('.psm-bcs-option').forEach(option => {
            option.addEventListener('click', (e) => this.selectBCS(e));
        });

        // Opciones médicas
        document.querySelectorAll('.psm-option-btn-compact').forEach(btn => {
            btn.addEventListener('click', (e) => this.selectMedicalOption(e));
        });

        // Comportamiento interactivo
        document.getElementById('frecuencia_comida')?.addEventListener('change', (e) => this.updateComportamiento(e));
        document.getElementById('nivel_energia')?.addEventListener('change', (e) => this.updateComportamiento(e));
        document.getElementById('frecuencia_ejercicio_comportamiento')?.addEventListener('change', (e) => this.updateComportamiento(e));

        // Enfermedades condicionales
        document.getElementById('enfermedades_recientes')?.addEventListener('change', (e) => this.toggleEnfermedadesFields(e));

        // Alimentación condicional
        document.getElementById('tipo_alimentacion')?.addEventListener('change', (e) => this.toggleAlimentacionFields(e));
    },
    
    // Seleccionar mascota
    selectPet(e) {
        const card = e.currentTarget;
        
        // Verificar si la mascota ya tiene plan
        if (card.classList.contains('has-plan')) {
            // Redirigir a ver el plan
            const mascotaId = card.dataset.mascotaId;
            window.location.href = `plan-salud-mensual.php?mascota_id=${mascotaId}`;
            return;
        }
        
        this.mascotaId = card.dataset.mascotaId;
        this.mascotaNombre = card.dataset.mascotaNombre;
        this.mascotaPeso = card.dataset.mascotaPeso;

        // Marcar como seleccionada
        document.querySelectorAll('.psm-pet-card-compact').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');

        // Llenar automáticamente el campo de peso si hay un peso registrado
        if (this.mascotaPeso && this.mascotaPeso !== '0') {
            const pesoInput = document.getElementById('peso');
            if (pesoInput) {
                pesoInput.value = this.mascotaPeso;
            }
        }

        // Mostrar información de la mascota seleccionada
        this.showPetInfo(card.dataset);

        // Mostrar formulario
        setTimeout(() => {
            document.getElementById('petSelection').style.display = 'none';
            const formContainer = document.getElementById('formContainer');
            formContainer.style.display = 'block';
            formContainer.classList.add('active');
        }, 300);
    },
    
    // Seleccionar BCS
    selectBCS(e) {
        const option = e.currentTarget;
        const value = option.dataset.value;
        
        document.querySelectorAll('.psm-bcs-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        
        option.classList.add('selected');
        
        const input = document.getElementById('bcs');
        if (input) input.value = value;
    },
    
    // Seleccionar opción médica
    selectMedicalOption(e) {
        const btn = e.currentTarget;
        const group = btn.closest('.psm-medical-options-compact');
        const card = btn.closest('.psm-medical-card-compact');

        group.querySelectorAll('.psm-option-btn-compact').forEach(b => {
            b.classList.remove('selected', 'selected-negative');
        });

        const value = btn.dataset.value;
        btn.classList.add(value === 'no' ? 'selected' : 'selected-negative');

        const inputName = card.dataset.inputName;
        const input = document.querySelector(`input[name="${inputName}"]`);
        if (input) input.value = value;
    },

    // Seleccionar card de apetito
    selectAppetiteCard(e) {
        const card = e.currentTarget;
        const value = card.dataset.value;

        document.querySelectorAll('.psm-appetite-card').forEach(c => {
            c.classList.remove('selected');
        });

        card.classList.add('selected');

        const input = document.getElementById('apetito');
        if (input) input.value = value;
    },

    // Seleccionar card de actividad
    selectActivityCard(e) {
        const card = e.currentTarget;
        const value = card.dataset.value;

        document.querySelectorAll('.psm-activity-card').forEach(c => {
            c.classList.remove('selected');
        });

        card.classList.add('selected');

        const input = document.getElementById('actividad');
        if (input) input.value = value;
    },
    
    // Ir al siguiente paso
    nextStep() {
        if (!this.validateCurrentStep()) {
            alert('Por favor completa todos los campos requeridos');
            return;
        }
        
        if (this.currentStep < this.totalSteps) {
            this.currentStep++;
            this.showStep(this.currentStep);
            this.updateProgress();
        }
    },
    
    // Ir al paso anterior
    prevStep() {
        if (this.currentStep > 1) {
            this.currentStep--;
            this.showStep(this.currentStep);
            this.updateProgress();
        }
    },
    
    // Ir a paso específico
    goToStep(step) {
        if (step <= this.currentStep) {
            this.currentStep = step;
            this.showStep(step);
            this.updateProgress();
        }
    },
    
    // Mostrar paso específico
    showStep(step) {
        document.querySelectorAll('.psm-step-content').forEach(s => {
            s.classList.remove('active');
        });
        
        const section = document.getElementById(`step${step}`);
        if (section) {
            section.classList.add('active');
        }
        
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const submitBtn = document.getElementById('submitBtn');

        if (prevBtn) prevBtn.style.display = step === 1 ? 'none' : 'inline-flex';
        if (nextBtn) nextBtn.style.display = step === this.totalSteps ? 'none' : 'inline-flex';
        if (submitBtn) submitBtn.style.display = step === this.totalSteps ? 'inline-flex' : 'none';
    },
    
    // Validar paso actual
    validateCurrentStep() {
        const section = document.getElementById(`step${this.currentStep}`);
        if (!section) return true;
        
        let valid = true;
        const required = section.querySelectorAll('[required]');
        
        required.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('error');
                valid = false;
            } else {
                field.classList.remove('error');
            }
        });
        
        return valid;
    },
    
    // Actualizar barra de progreso
    updateProgress() {
        const progress = (this.currentStep / this.totalSteps) * 100;
        const progressFill = document.getElementById('progressFill');
        if (progressFill) {
            progressFill.style.width = `${progress}%`;
        }
        
        document.querySelectorAll('.psm-step').forEach((step, index) => {
            step.classList.remove('active', 'completed');
            
            if (index + 1 < this.currentStep) {
                step.classList.add('completed');
            } else if (index + 1 === this.currentStep) {
                step.classList.add('active');
            }
        });
    },
    
    // Recopilar datos del formulario
    collectFormData() {
        const formData = new FormData();
        formData.append('mascota_id', this.mascotaId);
        
        // Paso 1
        formData.append('peso', document.getElementById('peso')?.value || '');
        formData.append('bcs', document.getElementById('bcs')?.value || '5');
        formData.append('temperamento', document.getElementById('temperamento')?.value || '');
        
        // Paso 2 - Términos médicos
        const terminos = ['claudicacion', 'disfagia', 'poliuria', 'prurito', 'alopecia', 
                         'otitis', 'epifora', 'estomatitis', 'dermatitis', 
                         'vomito_regurgitacion', 'diarrea_estrenimiento', 'tos_disnea'];
        
        terminos.forEach(termino => {
            const input = document.querySelector(`input[name="${termino}"]`);
            const value = input?.value || 'no';
            formData.append(termino, value);
        });
        
        // Paso 3
        formData.append('apetito', document.getElementById('apetito')?.value || 'normal');
        formData.append('actividad_comportamiento', document.getElementById('actividad')?.value || 'normal');
        
        // Paso 4
        formData.append('enfermedades_recientes', document.getElementById('enfermedades_recientes')?.value || '');
        formData.append('medicamentos', document.getElementById('medicamentos')?.value || '');
        formData.append('tipo_alimentacion', document.getElementById('tipo_alimentacion')?.value || '');
        formData.append('alergias', document.getElementById('alergias')?.value || '');
        formData.append('observaciones', document.getElementById('observaciones')?.value || '');
        
        return formData;
    },
    
    // Enviar formulario - SIMPLIFICADO
    async submitForm(e) {
        if (e) e.preventDefault();
        
        if (!this.validateCurrentStep()) {
            alert('Por favor completa todos los campos requeridos');
            return;
        }
        
        const btn = document.getElementById('submitBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
        
        try {
            const formData = this.collectFormData();
            
            const response = await fetch('ajax-generar-plan-mensual.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error('Error del servidor (HTTP ' + response.status + ')');
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess(result);
            } else {
                alert('Error: ' + (result.message || 'No se pudo generar el plan'));
            }
            
        } catch (error) {
            console.error('Error:', error);
            alert('Error: ' + error.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    },
    
    // Mostrar éxito - SIMPLIFICADO
    showSuccess(result) {
        document.getElementById('formContainer').style.display = 'none';
        document.querySelector('.psm-navigation').style.display = 'none';

        const successContainer = document.getElementById('successContainer');
        successContainer.style.display = 'block';

        // Actualizar información
        document.getElementById('successMascotaNombre').textContent = result.mascota?.nombre || 'tu mascota';
        document.getElementById('successTareasCount').textContent = result.tareas_creadas || 0;

        // Mostrar alerta si hay
        const alerta = result.alerta || {};
        if (alerta.nivel && alerta.nivel !== 'verde') {
            const alertDiv = document.getElementById('detectionAlert');
            const contentDiv = document.getElementById('detectionContent');

            const alertClass = alerta.nivel === 'rojo' ? 'urgent' : 'medium';
            const icon = alerta.nivel === 'rojo' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle';

            contentDiv.innerHTML = `
                <div class="psm-detection-item ${alertClass}">
                    <i class="fas ${icon}"></i>
                    <div>
                        <strong>${alerta.titulo || 'Atención'}</strong>
                        <p>${alerta.mensaje || ''}</p>
                        <small>${alerta.accion || ''}</small>
                    </div>
                </div>
            `;
            alertDiv.style.display = 'block';
        }

        // Mostrar veterinarias recomendadas
        if (result.recomendaciones && result.recomendaciones.veterinarias_recomendadas && result.recomendaciones.veterinarias_recomendadas.length > 0) {
            const vetsHtml = result.recomendaciones.veterinarias_recomendadas.slice(0, 3).map(vet => `
                <div class="psm-vet-card">
                    <div class="psm-vet-header">
                        <h4>${vet.nombre_local}</h4>
                        <span class="psm-vet-phone">${vet.telefono}</span>
                    </div>
                    <div class="psm-vet-address">
                        <i class="fas fa-map-marker-alt"></i> ${vet.direccion}
                    </div>
                    <div class="psm-vet-services">
                        ${vet.descripcion || 'Consulta General'}
                    </div>
                    <button type="button" class="psm-btn psm-btn-primary" onclick="PlanSaludMensualApp.agendarCita(${vet.id})">
                        <i class="fas fa-calendar-plus"></i> Agendar Cita
                    </button>
                </div>
            `).join('');

            document.getElementById('urgentContent').innerHTML = `
                <div class="psm-vets-section">
                    <p>Basado en los síntomas detectados, te recomendamos consultar con estas veterinarias especializadas:</p>
                    <div class="psm-vets-grid">
                        ${vetsHtml}
                    </div>
                    <p class="psm-vet-note">
                        <i class="fas fa-info-circle"></i>
                        Las veterinarias se muestran ordenadas por especialización en tus síntomas detectados.
                        Puedes agendar cita directamente haciendo clic en "Agendar Cita".
                    </p>
                </div>
            `;
            document.getElementById('urgentRecommendations').style.display = 'block';
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    // Mostrar información de la mascota seleccionada
    showPetInfo(data) {
        const display = document.getElementById('petInfoDisplay');
        if (display) {
            document.getElementById('infoRaza').textContent = data.mascotaRaza || '-';
            document.getElementById('infoEdad').textContent = `${data.mascotaEdadAnios || 0}a ${data.mascotaEdadMeses || 0}m`;
            document.getElementById('infoPeso').textContent = data.mascotaPeso && data.mascotaPeso !== '0' ? `${data.mascotaPeso} kg` : 'No registrado';
            display.style.display = 'block';
        }
    },

    // Actualizar comportamiento basado en preguntas directas
    updateComportamiento(e) {
        const frecuenciaComida = document.getElementById('frecuencia_comida')?.value || '';
        const nivelEnergia = document.getElementById('nivel_energia')?.value || '';
        const frecuenciaEjercicio = document.getElementById('frecuencia_ejercicio_comportamiento')?.value || '';

        // Mapear a los valores antiguos para compatibilidad
        let apetito = 'normal';
        let actividad = 'normal';

        // Determinar apetito basado en frecuencia de comida
        if (frecuenciaComida === '1') apetito = 'bajo';
        else if (frecuenciaComida === '3') apetito = 'alto';

        // Determinar actividad basada en nivel de energía y ejercicio
        if (nivelEnergia === 'muy_baja' || frecuenciaEjercicio === 'ninguno') actividad = 'muy_baja';
        else if (nivelEnergia === 'baja' || frecuenciaEjercicio === '1') actividad = 'baja';
        else if (nivelEnergia === 'alta' || frecuenciaEjercicio === '3') actividad = 'alta';
        else if (nivelEnergia === 'muy_alta') actividad = 'muy_alta';

        // Actualizar campos ocultos
        const apetitoInput = document.getElementById('apetito');
        const actividadInput = document.getElementById('actividad');
        if (apetitoInput) apetitoInput.value = apetito;
        if (actividadInput) actividadInput.value = actividad;
    },

    // Toggle campos de enfermedades
    toggleEnfermedadesFields(e) {
        const value = e.target.value;
        const container = document.getElementById('enfermedades_container');
        if (container) {
            container.style.display = (value === 'si_menor' || value === 'si_grave') ? 'block' : 'none';
        }
    },

    // Toggle campos de alimentación
    toggleAlimentacionFields(e) {
        const value = e.target.value;
        const container = document.getElementById('alimentacion_comercial_container');
        if (container) {
            container.style.display = (value === 'comida_comercial') ? 'block' : 'none';
        }
    },

    // Agregar método para agendar cita
    agendarCita: function(vetId) {
        // Redirigir a la página de agendar cita con la veterinaria preseleccionada
        window.location.href = `agendar-cita.php?vet_id=${vetId}`;
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    PlanSaludMensualApp.init();
});
