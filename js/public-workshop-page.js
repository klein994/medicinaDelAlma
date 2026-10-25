(function () {
    const requestedSlug = document.body.dataset.publicPage || new URLSearchParams(window.location.search).get("slug");
    const slug = requestedSlug && requestedSlug
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/([a-z0-9])([A-Z])/g, "$1-$2")
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "");
    if (!slug) return;

    const scriptUrl = document.currentScript && document.currentScript.src
        ? document.currentScript.src
        : window.location.href;
    const siteRoot = new URL("../", scriptUrl);
    const sitePath = (path) => new URL(path, siteRoot).href;
    const localHosts = ["localhost", "127.0.0.1", "::1"];
    const endpoints = localHosts.includes(window.location.hostname)
        ? [new URL("campus/public/index.php", siteRoot), new URL("http://127.0.0.1:8001/")]
        : [new URL("https://campus.medicinadelalma.com.ar/")];
    endpoints.forEach((endpoint) => {
        endpoint.searchParams.set("api", "public_page");
        endpoint.searchParams.set("slug", slug);
    });

    const make = (tag, className, value) => {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (value !== undefined && value !== null) element.textContent = value;
        return element;
    };

    const lines = (value) => (value || "").split(/\r?\n/).map((line) => line.trim()).filter(Boolean);

    function imagePath(value) {
        if (!value) return "";
        if (/^(https?:)?\/\//i.test(value)) return value;
        return sitePath(value.replace(/^(\.\.\/|\.\/|\/)+/, ""));
    }

    function appendStyles() {
        const hasBrandFonts = Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
            .some((link) => link.href.includes("family=DM+Sans"));
        if (!hasBrandFonts) {
            const fonts = make("link");
            fonts.rel = "stylesheet";
            fonts.href = "https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap";
            document.head.append(fonts);
        }
        const existingModern = Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
            .some((link) => link.href.includes("modern.css"));
        if (!existingModern) {
            const modern = make("link");
            modern.rel = "stylesheet";
            modern.href = sitePath("modern.css");
            document.head.append(modern);
        }
        const hasPageStyles = Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
            .some((link) => link.href.includes("workshop-pages.css"));
        if (!hasPageStyles) {
            const pageStyles = make("link");
            pageStyles.rel = "stylesheet";
            pageStyles.href = sitePath("workshop-pages.css");
            document.head.append(pageStyles);
        }
    }

    function addSectionHeading(container, kicker, title, copy) {
        const heading = make("div", "wkp-heading");
        if (kicker) heading.append(make("p", "wkp-kicker", kicker));
        if (title) heading.append(make("h2", "", title));
        if (copy) heading.append(make("p", "wkp-section-copy", copy));
        container.append(heading);
    }

    function renderCards(container, values, className) {
        if (!values.length) return;
        const grid = make("div", className);
        values.forEach((value, index) => {
            const card = make("article", "wkp-card");
            card.append(make("span", "wkp-card__number", String(index + 1).padStart(2, "0")));
            const separator = value.indexOf(":");
            if (separator > 0 && separator < 70) {
                card.append(make("h3", "", value.slice(0, separator).trim()));
                card.append(make("p", "", value.slice(separator + 1).trim()));
            } else {
                card.append(make("p", "wkp-card__text", value));
            }
            grid.append(card);
        });
        container.append(grid);
    }

    function render(page) {
        appendStyles();
        document.documentElement.lang = "es";
        document.title = (page.title || page.headline) + " | Medicina del Alma";
        let canonical = document.querySelector('link[rel="canonical"]');
        if (!canonical) {
            canonical = make("link");
            canonical.rel = "canonical";
            document.head.append(canonical);
        }
        canonical.href = sitePath("talleres/" + slug + "/");
        let description = document.querySelector('meta[name="description"]');
        if (!description) {
            description = make("meta");
            description.name = "description";
            document.head.append(description);
        }
        description.content = page.lead || page.headline || page.title;
        document.body.className = "workshop-cms-page";

        const header = make("header", "site-header is-scrolled");
        const headerInner = make("div", "site-header__inner");
        const brand = make("a", "site-brand");
        brand.href = sitePath("index.html#slider");
        brand.setAttribute("aria-label", "Medicina del Alma, inicio");
        const logo = make("img");
        logo.src = sitePath("images/sticky-logo.png");
        logo.alt = "Medicina del Alma";
        brand.append(logo);
        const toggle = make("button", "site-menu-toggle");
        toggle.type = "button";
        toggle.setAttribute("aria-label", "Abrir menú");
        toggle.setAttribute("aria-expanded", "false");
        toggle.append(make("span"), make("span"), make("span"));
        const nav = make("nav", "site-nav");
        nav.setAttribute("aria-label", "Navegación principal");
        [["Inicio", "index.html#slider"], ["Sobre mí", "index.html#service"], ["Talleres", "index.html#talleres"], ["Terapias", "index.html#price"], ["Tienda", "index.html#shop"], ["Contacto", "index.html#contact"]]
            .forEach(([label, href]) => {
                const link = make("a", "", label);
                link.href = sitePath(href);
                nav.append(link);
            });
        const campus = make("a", "", "Campus");
        campus.href = localHosts.includes(window.location.hostname)
            ? "http://127.0.0.1:8001/"
            : "https://campus.medicinadelalma.com.ar/";
        nav.append(campus);
        const navCta = make("a", "site-nav__cta", "Consultar");
        navCta.href = "#consulta";
        nav.append(navCta);
        headerInner.append(brand, toggle, nav);
        header.append(headerInner);
        toggle.addEventListener("click", () => {
            const open = header.classList.toggle("is-open");
            toggle.setAttribute("aria-expanded", String(open));
            toggle.setAttribute("aria-label", open ? "Cerrar menú" : "Abrir menú");
        });
        nav.addEventListener("click", (event) => {
            if (event.target.closest("a")) {
                header.classList.remove("is-open");
                toggle.setAttribute("aria-expanded", "false");
            }
        });

        const main = make("main", "wkp-main");
        const hero = make("section", "wkp-hero");
        const heroVisual = make("div", "wkp-hero__visual");
        if (page.image_url) {
            const heroImage = make("img");
            heroImage.src = imagePath(page.image_url);
            heroImage.alt = "";
            heroImage.fetchPriority = "high";
            heroVisual.append(heroImage);
        }
        const heroContent = make("div", "wkp-hero__content");
        const heroInner = make("div", "wkp-shell");
        if (page.eyebrow) heroInner.append(make("p", "wkp-eyebrow", page.eyebrow));
        heroInner.append(make("h1", "", page.headline || page.title));
        if (page.lead) heroInner.append(make("p", "wkp-lead", page.lead));
        const heroActions = make("div", "wkp-hero__actions");
        const primary = make("a", "wkp-button wkp-button--light", page.cta_label || "Consultar por WhatsApp");
        primary.href = "#consulta";
        const secondary = make("a", "wkp-text-link", "Conoce la propuesta ↓");
        secondary.href = "#propuesta";
        heroActions.append(primary, secondary);
        heroInner.append(heroActions);
        heroContent.append(heroInner);
        hero.append(heroVisual, heroContent);
        main.append(hero);

        const facts = [
            ["Modalidad", page.modality], ["Duración", page.duration],
            ["Próxima fecha", page.next_date], ["Inversión", page.price]
        ].filter(([, value]) => value);
        if (facts.length) {
            const factsSection = make("section", "wkp-facts wkp-shell");
            const factsGrid = make("dl", "wkp-facts__grid");
            facts.forEach(([label, value]) => {
                const fact = make("div", "wkp-fact");
                fact.append(make("dt", "", label), make("dd", "", value));
                factsGrid.append(fact);
            });
            factsSection.append(factsGrid);
            main.append(factsSection);
        }

        const intro = make("section", "wkp-section wkp-shell");
        intro.id = "propuesta";
        const introGrid = make("div", "wkp-intro-grid");
        const introCopy = make("div", "wkp-intro-copy");
        addSectionHeading(introCopy, "La propuesta", page.intro_title, "");
        lines(page.intro_body).forEach((paragraph) => introCopy.append(make("p", "", paragraph)));
        introGrid.append(introCopy);
        if (page.schedule) {
            const scheduleCard = make("aside", "wkp-schedule-card");
            scheduleCard.append(make("span", "wkp-card__number", "Tu recorrido"));
            scheduleCard.append(make("h3", "", page.schedule));
            if (page.duration) scheduleCard.append(make("p", "", "Duración: " + page.duration));
            if (page.modality) scheduleCard.append(make("p", "", "Modalidad: " + page.modality));
            introGrid.append(scheduleCard);
        }
        intro.append(introGrid);
        main.append(intro);

        const topics = lines(page.topics);
        if (topics.length) {
            const topicsSection = make("section", "wkp-section wkp-section--soft");
            const topicsInner = make("div", "wkp-shell");
            addSectionHeading(topicsInner, "Temas de exploración", page.topics_title, "");
            renderCards(topicsInner, topics, "wkp-card-grid");
            topicsSection.append(topicsInner);
            main.append(topicsSection);
        }

        const programItems = lines(page.program_details);
        if (programItems.length) {
            const programSection = make("section", "wkp-section wkp-shell");
            addSectionHeading(programSection, "Tu recorrido", page.program_title, "");
            const list = make("ul", "wkp-program-list");
            programItems.forEach((item) => list.append(make("li", "", item)));
            programSection.append(list);
            main.append(programSection);
        }

        const contact = make("section", "wkp-contact");
        contact.id = "consulta";
        const contactInner = make("div", "wkp-shell wkp-contact__inner");
        const contactCopy = make("div");
        contactCopy.append(make("p", "wkp-kicker", "Próxima cohorte"));
        contactCopy.append(make("h2", "", "¿Te gustaría conocer más?"));
        contactCopy.append(make("p", "", "Escríbenos y te compartimos información actualizada sobre fechas, modalidad y disponibilidad."));
        const message = "Hola, quisiera recibir información sobre " + (page.title || page.headline) + ".";
        const contactLink = make("a", "wkp-button", page.cta_label || "Consultar por WhatsApp");
        contactLink.href = "https://api.whatsapp.com/send?phone=584120744724&text=" + encodeURIComponent(message);
        contactLink.target = "_blank";
        contactLink.rel = "noopener noreferrer";
        contactCopy.append(contactLink);
        contactInner.append(contactCopy);
        if (page.disclaimer) contactInner.append(make("p", "wkp-disclaimer", page.disclaimer));
        contact.append(contactInner);
        main.append(contact);

        const footer = make("footer", "wkp-footer");
        const footerInner = make("div", "wkp-shell");
        const footerLogo = make("img");
        footerLogo.src = sitePath("images/slider-logo.svg");
        footerLogo.alt = "Medicina del Alma";
        footerInner.append(footerLogo, make("p", "", "Un espacio para acompañar tu camino."));
        footer.append(footerInner);
        document.body.replaceChildren(header, main, footer);
    }

    document.body.className = "workshop-cms-page";
    const loading = make("main", "wkp-unavailable");
    loading.append(make("p", "wkp-kicker", "Cargando formación"));
    loading.append(make("h1", "", "Estamos cargando la información actualizada."));
    document.body.replaceChildren(loading);
    appendStyles();

    async function fetchPublicPage() {
        const failures = [];
        let notFoundCount = 0;

        for (const endpoint of endpoints) {
            try {
                const response = await fetch(endpoint, { cache: "no-store", credentials: "omit" });
                const responseText = await response.text();
                let payload;
                try {
                    payload = JSON.parse(responseText);
                } catch (_) {
                    throw new Error(`El endpoint respondió con contenido no JSON (HTTP ${response.status}).`);
                }
                if (response.status === 404) {
                    notFoundCount += 1;
                    failures.push(`${endpoint.origin}: HTTP 404`);
                    continue;
                }
                if (!response.ok) {
                    const diagnostic = payload.diagnostic ? ` ${payload.diagnostic}.` : "";
                    throw new Error(`${payload.error || "No se pudo completar la consulta de la ficha."} (HTTP ${response.status}).${diagnostic}`);
                }
                if (!payload || typeof payload !== "object" || !payload.slug) {
                    throw new Error("El endpoint no devolvió los datos de una ficha.");
                }
                if (payload.slug !== slug) {
                    throw new Error(`La ficha devuelta tiene el slug “${payload.slug}” y se solicitó “${slug}”.`);
                }
                return payload;
            } catch (error) {
                failures.push(`${endpoint.origin}: ${error.message || "error de conexión"}`);
            }
        }

        if (notFoundCount === endpoints.length) {
            const error = new Error("La página no está publicada.");
            error.unpublished = true;
            throw error;
        }

        const error = new Error("No se pudo cargar la ficha desde el servicio de páginas públicas.");
        error.diagnostics = failures;
        console.error("Error al cargar la ficha pública:", failures);
        throw error;
    }

    fetchPublicPage()
        .then((page) => {
            if (page && page.slug === slug) render(page);
        })
        .catch((error) => {
            if (error.unpublished) {
                document.body.className = "workshop-cms-page";
                const notice = make("main", "wkp-unavailable");
                notice.append(make("p", "wkp-kicker", "Propuesta no disponible"));
                notice.append(make("h1", "", "Esta página no está publicada por el momento."));
                const home = make("a", "wkp-button", "Volver a Talleres y formaciones");
                home.href = sitePath("index.html#talleres");
                notice.append(home);
                document.body.replaceChildren(notice);
                appendStyles();
                return;
            }
            document.body.className = "workshop-cms-page";
            const notice = make("main", "wkp-unavailable");
            notice.append(make("p", "wkp-kicker", "Información temporalmente no disponible"));
            notice.append(make("h1", "", "No pudimos cargar la información actualizada de esta formación."));
            notice.append(make("p", "", "La home puede seguir cargando con normalidad: esta ficha usa la consulta pública de páginas de talleres. Vuelve a intentarlo en unos momentos."));
            if (localHosts.includes(window.location.hostname) && error.diagnostics && error.diagnostics.length) {
                const diagnostics = make("p", "wkp-diagnostics", error.diagnostics.join(" · "));
                diagnostics.setAttribute("role", "status");
                notice.append(diagnostics);
            }
            const retry = make("button", "wkp-button", "Reintentar");
            retry.type = "button";
            retry.addEventListener("click", () => window.location.reload());
            const home = make("a", "wkp-text-link", "Volver a Talleres y formaciones");
            home.href = sitePath("index.html#talleres");
            notice.append(retry, home);
            document.body.replaceChildren(notice);
            appendStyles();
        });
})();
