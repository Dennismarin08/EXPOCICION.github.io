document.addEventListener('DOMContentLoaded', () => {
    // ------------------------------------------
    // 1. Menú Hamburguesa y Backdrop
    // ------------------------------------------
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const closeBtn = document.getElementById('close-btn');
    const backdrop = document.getElementById('backdrop');

    function toggleMenu() {
        sidebar.classList.toggle('active');
        backdrop.classList.toggle('active');
        document.body.classList.toggle('no-scroll'); // Prevenir scroll al abrir
    }

    menuToggle.addEventListener('click', toggleMenu);
    closeBtn.addEventListener('click', toggleMenu);
    backdrop.addEventListener('click', toggleMenu);

    // Cerrar menú al hacer clic en un enlace
    document.querySelectorAll('#sidebar a').forEach(link => {
        link.addEventListener('click', () => {
            if (sidebar.classList.contains('active')) {
                toggleMenu();
            }
        });
    });

    // ------------------------------------------
    // 2. Animación de Scroll (Scroll-Reveal)
    // ------------------------------------------
    const observerOptions = {
        root: null, // viewport
        rootMargin: '0px',
        threshold: 0.1 // Aparecerá cuando el 10% del elemento sea visible
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const element = entry.target;
                const delay = parseInt(element.getAttribute('data-delay')) || 0;

                // Aplicar la visibilidad después del delay
                setTimeout(() => {
                    element.classList.add('is-visible');
                    // Dejar de observar el elemento una vez animado
                    observer.unobserve(element);
                }, delay);
            }
        });
    }, observerOptions);

    // Observar todos los elementos con la clase 'animate-on-scroll'
    document.querySelectorAll('.animate-on-scroll').forEach(element => {
        observer.observe(element);
    });

    // ------------------------------------------
    // 3. Efecto Parallax en Hero (Interactivo)
    // ------------------------------------------
    const hero = document.getElementById('hero');
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        // Ajusta la posición de fondo a medida que el usuario hace scroll
        // Esto crea el sutil efecto parallax.
        hero.style.backgroundPositionY = -(scrolled * 0.5) + 'px'; 
    });

    // ------------------------------------------
    // 4. Interacción para tarjetas '¿Sabías que?'
    // ------------------------------------------
    const factCards = document.querySelectorAll('.fact-card');
    factCards.forEach(card => {
        // Toggle visual 'expanded' state on click / keyboard
        function toggle(e) {
            // Permitimos Toggle con Enter/Espacio
            if (e.type === 'keydown') {
                if (!(e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar')) return;
                e.preventDefault();
            }
            card.classList.toggle('expanded');
        }
        card.addEventListener('click', toggle);
        card.addEventListener('keydown', toggle);
    });

        // Theme toggle button (dark/light)
        const themeToggle = document.getElementById('theme-toggle');
        function updateThemeIcon() {
            if (document.body.classList.contains('light')) {
                themeToggle.textContent = '🌙';
                themeToggle.setAttribute('title','Cambiar a tema oscuro');
            } else {
                themeToggle.textContent = '☀️';
                themeToggle.setAttribute('title','Cambiar a tema claro');
            }
        }
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('light');
            updateThemeIcon();
        });
        updateThemeIcon();

        // Interactive brain regions (look for overlay regions first)
        const regions = document.querySelectorAll('.diagram-overlay .region, .diagram-svg .region');
    const brainTitle = document.getElementById('brain-title');
    const brainDesc = document.getElementById('brain-desc');

        const regionData = {
            'cerebelo': {
                title: 'Cerebelo',
                desc: 'Coordina la postura y los movimientos finos; influencia el aprendizaje motor y el equilibrio.'
            },
            'hipotalamo': {
                title: 'Hipotálamo',
                desc: 'Regula las funciones vitales: hambre, sed, sueño y respuestas hormonales al estrés.'
            },
            'ganglios': {
                title: 'Ganglios basales',
                desc: 'Implicados en la planificación y ejecución del movimiento y en hábitos conductuales.'
            },
            'limbico': {
                title: 'Sistema límbico',
                desc: 'Centro de las emociones, memoria y conducta social; importante en la respuesta afectiva.'
            }
        };

        regions.forEach(r => {
            // Ensure the actual clickable target can be the group's child (ellipse/path)
            r.addEventListener('click', (ev) => { ev.preventDefault(); selectRegion(r); });
            r.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); selectRegion(r); }
            });
            r.addEventListener('pointerover', () => r.classList.add('hover'));
            r.addEventListener('pointerout', () => r.classList.remove('hover'));
        });

        function selectRegion(r) {
            regions.forEach(x => x.classList.remove('active'));
            r.classList.add('active');
            const key = r.dataset.region;
            const info = regionData[key];
            if (info) {
                if (brainTitle && brainDesc) {
                    brainTitle.textContent = info.title;
                    brainDesc.textContent = info.desc;
                }
            } else {
                if (brainTitle && brainDesc) {
                    brainTitle.textContent = 'Zona desconocida';
                    brainDesc.textContent = 'No hay información disponible para esta área.';
                }
            }
        }

    // ------------------------------------------
    // 5. Interactividad para la sección 'Integrantes'
    //    - Tilt en hover/pointermove
    //    - Click/Enter para expandir bio
    // ------------------------------------------
    const teamCards = document.querySelectorAll('.team-card');

    teamCards.forEach(card => {
        // pointer move tilt effect
        card.addEventListener('pointermove', (e) => {
            const rect = card.getBoundingClientRect();
            const px = (e.clientX - rect.left) / rect.width;
            const py = (e.clientY - rect.top) / rect.height;
            const rotateY = (px - 0.5) * 8; // +/- 4deg
            const rotateX = (0.5 - py) * 8;
            card.style.transform = `perspective(800px) translateZ(0) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-6px) scale(1.01)`;
        });
        card.addEventListener('pointerleave', () => {
            card.style.transform = '';
        });

        // keyboard / click to toggle bio
        function toggleBio(e) {
            if (e.type === 'keydown') {
                if (!(e.key === 'Enter' || e.key === ' ')) return;
                e.preventDefault();
            }
            card.classList.toggle('expanded');
        }
        card.addEventListener('click', toggleBio);
        card.addEventListener('keydown', toggleBio);
    });
});