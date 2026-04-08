// --- Navigation Logic ---
function nextStep(target) {
    if (validateCurrentStep()) {
        showStep(target);
    }
}

function prevStep(target) {
    showStep(target);
}

function showStep(stepNum) {
    // Hide all steps
    document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
    
    // Show target
    document.getElementById('step' + stepNum).classList.add('active');
    
    // Update indicator
    document.querySelector(`.step[data-step="${stepNum}"]`).classList.add('active');
    
    // Update progress bar
    const percent = stepNum * 20;
    document.getElementById('wizardProgress').style.width = percent + '%';
    
    // Scroll top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// --- Validation ---
function validateCurrentStep() {
    // Simple validation: check if visible required inputs are filled
    const activeStep = document.querySelector('.wizard-step.active');
    if (!activeStep) return true;

    const inputs = activeStep.querySelectorAll('input[required], select[required]');
    let valid = true;
    
    inputs.forEach(input => {
        if (input.type === 'radio') {
            const name = input.name;
            const group = activeStep.querySelectorAll(`input[name="${name}"]`);
            const checked = Array.from(group).some(r => r.checked);
            if (!checked) valid = false;
        } else {
            if (!input.value) valid = false;
        }
    });

    if (!valid) {
        alert('Por favor completa los campos requeridos para continuar.');
    }
    return valid;
}

// --- UI Interactions ---
function setSeverity(key, level, btn) {
    // Update input
    document.getElementById('input_' + key).value = level;
    
    // Update styles
    const parent = btn.parentElement;
    parent.querySelectorAll('.sev-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    
    // Show details if not 'no'
    const details = document.getElementById('details_' + key);
    if (level !== 'no') {
        details.style.display = 'block';
    } else {
        details.style.display = 'none';
    }
}

// --- Form Submit ---
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('planSaludForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default POST
            
            const btn = this.querySelector('.psm-btn-submit');
            const originalText = btn.innerHTML;
            
            if(btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
            }

            const formData = new FormData(this);

            fetch('ajax-generar-plan-mensual.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Check if response is OK
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                // Try to parse as JSON
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Server response:', text);
                        throw new Error('El servidor no devolvió JSON válido. Revisa la consola para más detalles.');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    // Manejo de alerta para usuarios FREE
                    if (data.free_alert) {
                        if(btn) {
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                        }
                        if (confirm(data.free_alert.title + "\n\n" + data.free_alert.message + "\n\n¿Quieres ver los planes Premium?")) {
                            window.location.href = 'upgrade-premium.php';
                        }
                        return;
                    }
                    // Success! Redirect
                    window.location.href = data.redirect_url || 'calendario.php';
                } else {
                    // Error
                    alert('Error: ' + (data.message || 'Ocurrió un error inesperado.'));
                    if(btn) {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                }
            })
            .catch(error => {
                console.error('Error completo:', error);
                alert('Error: ' + error.message + '\n\nRevisa la consola del navegador (F12) para más detalles.');
                if(btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            });
        });
    }
});